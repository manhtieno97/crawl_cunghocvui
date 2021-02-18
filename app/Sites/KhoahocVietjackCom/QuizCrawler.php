<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-13
 * Time: 21:45
 */

namespace App\Sites\KhoahocVietjackCom;


use App\Crawler\Browsers\Guzzle;
use App\Libs\IdToPath;
use App\Models\Question;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

class QuizCrawler {

    protected $prefix = "https://khoahoc.vietjack.com/question/__id__/slug-cua-cau-hoi-o-day";
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
        if(file_exists( $data_file ) && !$this->force){
            throw new \Exception("Id " . $id . " crawled at " . $data_file );
        }

        $url = $this->makeUrl( $id );
        if ( $url ) {
            dump('Parsing ' . $url);
            $html = ( new Guzzle( $this->client ) )->getHtml( $url );
            $data = $this->processHtml( $html );
            if((!empty($data['content'])) && (!empty($data['source_title']))){
                if(Question::firstOrCreate(
                    ['question' => $data['content']],
                    [
                        'album' => $data['source_title'],
                        'link' => $url,
                        'type' => $data['type'],
                        'disk' => config("crawl.".$this->site.".disk"),
                        'file' => $data_file,
                        'site' => config("crawl.".$this->site.".site"),
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

        $breadcrumb = $crawler->filter( 'ol.breadcrumb li' );

        $breadcrumb->each( function ( Crawler $li, $i ) use(&$data) {
            if($i == 0 ){
                return;
            }
            if(strpos( $li->attr( 'class'), "active") !== false){
                $data['source_title'] = trim( $li->text() );
                return;
            }
            $data['keywords'][] = trim( $li->text() );
        });
        if(isset($data['keywords'][0]))
        {
            $data['grade'] = $data['keywords'][0];
        }
        if(isset($data['keywords'][1]))
        {
            $data['subject'] = $data['keywords'][1];
        }

        try {
            $data['source_title'] = trim( $crawler->filter( '.title-exam a' )->text() );
        }catch ( \Exception $ex ) {
            dump("Get source_title error " . $ex->getMessage());
        }

        $images = $this->download_images( $crawler->filterXPath( '//div[@class="main-study"]') );

        try {
            $data['content'] = trim( $crawler->filter( '.title-qa p span:nth-child(1)' )->html() );
            $data['content'] = $this->src_to_base64_image( $data['content'], $images);
        } catch ( \Exception $ex ) {
            dump("Get content error " . $ex->getMessage());
        }

        try {
            $answers = $crawler->filter( '.answer-check label p:nth-child(1)' );
            $answers->each( function ( Crawler $li ) use ( &$data, $images ) {
                if(trim($li->text()) == ''){
                    return;
                }
                $data['choices'][] = $this->src_to_base64_image( trim($li->html()), $images);
            });
        } catch ( \Exception $ex ) {

        }
        try {
            $suggestion = trim( $crawler->filter( '.question .result' )->html() );
            $data['suggestion'] = $this->src_to_base64_image( $suggestion, $images);
        } catch ( \Exception $ex ) {
            dump("Get suggestion error " . $ex->getMessage());
        }

        try {
            $suggestion_text = strip_tags( $data['suggestion'] );
            if(preg_match( "/(Đáp án|Chọn|Chọn)\s+([ABCDEF])/u", $suggestion_text, $matches)){
                $data['answers'][] = $matches[2];
            }
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
