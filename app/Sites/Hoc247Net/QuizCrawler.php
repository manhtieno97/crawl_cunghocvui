<?php
/**
 * Crawl quizzes with url example https://hoc247.net/cau-hoi-qua-trinh-do-thi-hoa-nuoc-ta-hien-nay-co-dac-diem-la--qid111368.html
 * User: hocvt
 * Date: 2019-10-31
 * Time: 16:38
 */

namespace App\Sites\Hoc247Net;


use App\Crawler\Browsers\Guzzle;
use App\Libs\IdToPath;
use App\Models\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;

class QuizCrawler {

    protected $prefix = "https://hoc247.net/cau-hoi--qid__id__.html";
    protected $client;
    protected $site;
    protected $force;

    /**
     * QuizCrawler constructor.
     *
     * @param $site
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
        $data_file = config("crawl.".$this->site.".folder") . '/' . IdToPath::make( $id, "quiz.json");
        $url = $this->makeUrl( $id );
        if ( $url ) {
            dump('Parsing ' . $url);
            $html = ( new Guzzle( $this->client ) )->getHtml( $url );
            $data = $this->processHtml( $html );
            if((!empty($data['content']))
                && (!empty($data['source_title']))
                && (!empty($data['grade']))
                && (!empty($data['subject']))
                && (!empty($data['answers']) || !empty($data['suggestion']))
            ){
                if(Question::firstOrCreate(
                    ['question' => $data['content']],
                    [
                        'album' => $data['source_title'],
                        'link' => $url,
                        'type' => $data['type'],
                        'disk' => config("crawl.".$this->site.".disk"),
                        'file' => $data_file,
                        'site' => config("crawl.".$this->site.".site"),
                        'status' => Question::STATUS_QUESTION_DEFAULT
                    ]
                )) {
                    $data['url'] = $url;
                    \Storage::disk(config("crawl.".$this->site.".disk"))->put($data_file, json_encode( $data ));
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
        try {
            $data['keywords'][] = trim( $crawler->filter( 'ul.tlmenu li.act' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['keywords'][] = trim( $crawler->filterXPath( '//strong()' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['keywords'][] = trim( $crawler->filterXPath( '//p/strong[text()="Chủ đề :"]/following-sibling::*' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['keywords'][] = trim( $crawler->filterXPath( '//p/strong[text()="Loại bài:"]/following-sibling::*' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['keywords'][] = trim( $crawler->filterXPath( '//p/strong[text()="Môn học:"]/following-sibling::*' )->text() );
        } catch ( \Exception $ex ) {

        }
        $data['keywords'] = array_filter( $data['keywords'] );

        try {
            $data['grade'] = trim( $crawler->filter( 'ul.tlmenu li.act' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['subject'] = trim( $crawler->filterXPath( '//p/strong[text()="Môn học:"]/following-sibling::*' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['question_type'] = trim( $crawler->filterXPath( '//p/strong[text()="Loại bài:"]/following-sibling::*' )->text() );
        } catch ( \Exception $ex ) {

        }
        try {
            $data['source_title'] = trim( $crawler->filter( '.list-content-cauhoi .i-head .i-title' )->text() );
        } catch ( \Exception $ex ) {

        }

        $images = $this->download_images( $crawler->filterXPath( '//ul[@id="itvc20player"]') );

        try {
            $data['content'] = trim( $crawler->filterXPath( '//ul[@id="itvc20player"]/li[@class="lch"]/strong[text()="Câu hỏi:"]/following-sibling::p' )->html() );
            $i = $crawler->filterXPath( '//ul[@id="itvc20player"]/li[@class="lch"]/strong[text()="Câu hỏi:"]/following-sibling::p[2]' );
            if($i->count()){
                $data['content'] .= trim($i->html());
            }
            $data['content'] = $this->src_to_base64_image( $data['content'], $images);
        } catch ( \Exception $ex ) {
            dump("Get content error " . $ex->getMessage());
        }

        try {
            $answers = $crawler->filterXPath( '//ul[@id="itvc20player"]/li[@class="lch"]/ul[contains(@class, "dstl")]/li' );
            $answers->each( function ( Crawler $li ) use ( &$data, $images ) {
                $data['choices'][] = $this->src_to_base64_image( trim($li->text()), $images);
            });
        } catch ( \Exception $ex ) {

        }

        try {
            $suggestion = trim( $crawler->filterXPath( '//ul[@id="itvc20player"]/li[@class="lch"]//div[@class="loigiai"]' )->html() );
            $data['suggestion'] = $this->src_to_base64_image( $suggestion, $images);
        } catch ( \Exception $ex ) {
            dump("Get suggestion error " . $ex->getMessage());
        }

        try {
            $right_answers = trim( $crawler->filterXPath( '//ul[@id="itvc20player"]/li[@class="lch"]/div[contains(@class, "_traloi")]/p[2]' )->text() );
            $data['answers'][] = trim(str_replace( "Đáp án đúng:", "", $right_answers));
        } catch ( \Exception $ex ) {

        }

        return $data;
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
