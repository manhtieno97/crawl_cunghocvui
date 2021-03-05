<?php
/**
 * Crawl quizzes with url example https://hoc247.net/cau-hoi-qua-trinh-do-thi-hoa-nuoc-ta-hien-nay-co-dac-diem-la--qid111368.html
 * User: hocvt
 * Date: 2019-10-31
 * Time: 16:38
 */

namespace App\Sites\LoiGiaiHay;


use App\Crawler\Browsers\Guzzle;
use App\Libs\IdToPath;
use App\Models\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;

class QuizCrawler {

    const subject_default = ['Toán','Hóa','Sinh'];
    protected $prefix = "https://loigiaihay.com/de--a__id__.html";
    protected $client;
    protected $site;
    protected $force;

    /**
     * QuizCrawler constructor.
     *
     * @param $data_dir
     * @param bool $force
     *
     * @throws \Exception
     */
    public function __construct($site, $force = false) {
        $this->client = new Client( config( 'crawler.browsers.guzzle' ) );
        $this->site = trim( $site,' ');
        $this->force = $force;
    }

    public function process( $id ) {
        $data_file = config("crawl.".$this->site.".folder") . '/' . IdToPath::make( $id, "");
        $url = $this->makeUrl( $id );
        if ( $url ) {
            dump('Parsing ' . $url);
            $html = ( new Guzzle( $this->client ) )->getHtml( $url );
            $results = $this->processHtml( $html );
            if(!empty($results['content']) && !empty($results['answers'])
                                && (count($results['content']) > 1)
                                && (count($results['answers']) == count($results['content']) )
            ){
                $results['data']['url'] = $url;
                foreach ($results['content'] as $key => $result)
                {
                    $results['data']['content'] = $result[0];
                    unset($result[0]);
                    $results['data']['choices'] = $result;
                    $results['data']['answers'] = $results['answers'][$key];
                    if(Question::firstOrCreate(
                        ['question' => $results['data']['content']],
                        [
                            'album' => $results['data']['source_title'],
                            'link' => $url,
                            'type' => $results['data']['type'],
                            'disk' => config("crawl.".$this->site.".disk"),
                            'file' => $data_file.'c'.($key+1).'.quiz.json',
                            'site' => config("crawl.".$this->site.".site"),
                            'status' => Question::STATUS_QUESTION_DEFAULT
                        ]
                    )) {
                        $results['data']['url'] = $url;
                        \Storage::disk(config("crawl.".$this->site.".disk"))->put($data_file.'c'.($key+1).'.quiz.json', json_encode( $results['data'] ));
                    }
                }


            }
        } else {
            return false;
        }
    }

    protected function processHtml( $html, $url = '' ) {
        $crawler = new Crawler();
        $crawler->addHtmlContent( $html );
        $data = [
            'type' => 'choices',
            'keywords' => [],
            'pre_content' => '',
            'source_title' => '',
            'content' => '',
            'choices' => [],
            'suggestion' => '',
            'answers' => [],
            'grade' => '',
            'subject' => '',
            'question_type' => '',
        ];
        $keywords = '';
        try {
            $keywords = trim( $crawler->filter( '.top-title a' )->text() );
        } catch ( \Exception $ex ) {

        }
        if(!empty($keywords))
        {
            if($keywords == 'Toán 11 nâng cao'){
                $data['subject'] = 'Toán nâng cao';
                $data['grade'] = 'Lớp 11';
            }else{
                try {
                    $data['subject'] = trim(preg_replace('/lớp.+/', '$1', $keywords));
                } catch ( \Exception $ex ) {

                }
                try {
                    $data['grade'] = trim( str_replace( $data['subject'], '', $keywords ));
                } catch ( \Exception $ex ) {

                }
                if(in_array($data['subject'],self::subject_default))
                {
                    $data['subject'] = $data['subject'].' học';
                }
            }
        }
        try {
            $data['keywords'][] = $data['grade'];
            $data['keywords'][] = $data['subject'];
        } catch ( \Exception $ex ) {

        }

        /*try {
            $data['question_type'] = trim( $crawler->filter( '.top-title a' )->text() );
        } catch ( \Exception $ex ) {

        }*/
        try {
            $data['source_title'] = trim( $crawler->filter( '.box_content .box .content_box h1 a' )->text() );
        } catch ( \Exception $ex ) {

        }
        $questions = [];
        $check = true;
        $check_answers = false;
        try {
            $content = [];
            $answers = [];
            $crawler->filter('#box-content p')->each(function (Crawler $node , $i) use (&$content, &$question, &$check, &$answers, &$check_answers) {
                if(preg_match( "/<img+/", $node->html()) && $check)
                {
                    $images = $this->download_images( $node );
                    $image = $this->src_to_base64_image( $node->html(), $images);
                    $content[] = $image;
                }
                if((preg_match( "/^Question [0-9]+/", $node->text()) || preg_match( "/^Câu [0-9]:+/", $node->text()) || preg_match( "/^Câu [0-9]\(NB|TH|VD|VDC\)+/", $node->text())  || preg_match( "/^Câu [0-9][0-9]:+/", $node->text())  || preg_match( "/^[A-D]\.+/", $node->text())) && $check)  {
                    $content[] = $node->text();
                }
                if(preg_match( "/^Lời giải chi tiết|ĐÁP ÁN|Lời giải chi tiết+/", $node->text()))
                {
                    $check = false;
                }
                if(preg_match( "/<img+/", $node->html()) && !$check)
                {
                    $images = $this->download_images( $node );
                    $image = $this->src_to_base64_image( $node->html(), $images);
                    $answers[] = $image;
                }
                if ((preg_match( "/^Question [0-9]+/", $node->text()) || preg_match( "/^Câu [0-9]+/", $node->text())  || preg_match( "/^Câu [0-9][0-9]+/", $node->text()) || $check_answers) && !$check && !preg_match( "/^Nguồn: Sưu tầm|Loigiaihay.com+/", $node->text()))  {
                    $answers[] = $node->text();
                    $check_answers = true;
                }
            });
            $question = [];
            for ($i = 0; $i < count($content); $i++) {
                $question[] = $content[$i];
                if ( $i == count($content) - 1 || preg_match( "/^Question [0-9]+/", $content[$i+1]) || preg_match( "/^Câu [0-9]+/", $content[$i+1])) {
                    $questions[] = $question;
                    $question = [];
                }
            }
            $answer = [];
            $kq = [];
            for ($i = 0; $i < count($answers); $i++) {
                $answer[] = $answers[$i];
                if ( $i == count($answers) - 1 || preg_match( "/^Question [0-9]+/", $answers[$i+1]) || preg_match( "/^Câu [0-9]+/", $answers[$i+1]) || preg_match( "/^Câu [0-9][0-9]+/", $answers[$i+1])) {
                    $kq[] = $answer;
                    $answer = [];
                }
            }
        } catch ( \Exception $ex ) {
            dump("Get content error " . $ex->getMessage());
        }
        return [
            'data' => $data,
            'content' => $questions,
            'answers' => $kq
        ];
    }

    protected function makeUrl( $id ) {
        $url = str_replace( "__id__", $id, $this->prefix );
        dump('Checking ' . $url);
        try {
            $response = $this->client->head( $url, [
                'timeout'         => 10,
                'allow_redirects' => false,
            ] );
            if ( count( $response->getHeader( 'Location' ) ) ) {
                return $response->getHeader( 'Location' )[0];
            } else {
                return false;
            }
        } catch ( RequestException $exception ) {
            if ( ! $exception->getResponse() ) {
                throw $exception;
            } else {
                return false;
            }
        }
    }

    protected function download_images(Crawler $crawler){
        $images = $crawler->filter('img');
        $downloaded = [];
        /** @var \DOMElement $image */
        foreach ($images as $image){
            try{
                $src = $image->getAttribute( 'src');
                $downloaded[] = [
                    'url' => $src,
                    'data-url' => (string)(app('image')->make($src)->encode('data-url')),
                ];
            }catch (\Exception $ex){

            }
        }
        return $downloaded;
    }

    protected function src_to_base64_image($content, $images){
        foreach ($images as $image){
            $content = str_replace( $image['url'], $image['data-url'], $content);
        }
        return $content;
    }


}
