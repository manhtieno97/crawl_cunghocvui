<?php
/**
 * Crawl quizzes with url example https://hoc247.net/cau-hoi-qua-trinh-do-thi-hoa-nuoc-ta-hien-nay-co-dac-diem-la--qid111368.html
 * User: hocvt
 * Date: 2019-10-31
 * Time: 16:38
 */

namespace App\Sites\Hoc24Vn;


use App\Crawler\Browsers\Guzzle;
use App\Libs\IdToPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Image;

class QuizCrawler {
    
    protected $prefix = "https://hoc24.vn/hoi-dap/quiz/__id__.html";
    protected $client;
    protected $data_dir;
    protected $force;
    
    /**
     * QuizCrawler constructor.
     *
     * @param $data_dir
     * @param bool $force
     *
     * @throws \Exception
     */
    public function __construct($data_dir, $force = false) {
        $this->client = new Client( config( 'crawler.browsers.guzzle' ) );
        if(!is_dir( $data_dir )){
            throw new \Exception($data_dir . " must be a directory ");
        }
        $this->data_dir = realpath( rtrim( $data_dir, "/" ) );
        $this->force = $force;
    }
    
    public function process( $id ) {
        $data_file = $this->data_dir . "/" . IdToPath::make( $id, "quiz.json");
        if(file_exists( $data_file ) && !$this->force){
            throw new \Exception("Id " . $id . " crawled at " . $data_file );
        }
        
        $old_mask = umask(0);
        @mkdir( preg_replace( "/\/[^\/]+$/", "", $data_file ), 0777, true );
        umask($old_mask);
        
        $url = $this->makeUrl( $id );
        if ( $url ) {
            dump('Parsing ' . $url);
            $html = ( new Guzzle( $this->client ) )->getHtml( $url );
            $data = $this->processHtml( $html, $url );
            if($data && $data['content']){
                $data['url'] = $url;
                file_put_contents( $data_file, json_encode( $data ) );
            }
        } else {
            return false;
        }
    }
    
    protected function processHtml( $html, $url = '' ) {
        if(mb_strpos( $html, "Không tìm thấy đường dẫn này")){
            dump("Not found " . $url);
            return false;
        }
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
        ];
        try {
            try{
                $activated_menu = $crawler->filter('.sidebar .sidebar-menu ul ul ul .active > a');
                $data['keywords'][] = trim( $activated_menu->text() );
            }catch (\Exception $ex){
            
            }
            try{
                $activated_menu = $crawler->filter('.sidebar .sidebar-menu ul ul .active > a');
                $data['keywords'][] = trim( $activated_menu->text() );
                $activated_menu = $activated_menu->parents()->parents()->parents()->filterXPath( '//li/a[1]');
                $data['keywords'][] = trim( $activated_menu->text() );
            }catch (\Exception $ex){
            
            }
            $tags = $crawler->filter( 'div.tag-small span a');
            $tags->each( function (Crawler $crawler) use(&$data){
                $data['keywords'][] = trim( $crawler->text() );
            });
        } catch ( \Exception $ex ) {
            dump("Can not get keywords " . $ex->getMessage());
        }
    
        $data['keywords'] = array_unique( $data['keywords'] );
        
        try {
            $data['source_title'] = Arr::first( $data['keywords'] );
        } catch ( \Exception $ex ) {
        
        }
    
        $images = $this->download_images( $crawler->filterXPath( '//div[@id="question-panel"]') );
        
        try {
            $question_element = $crawler->filterXPath( '//div[@id="question-panel"]/div[@id="question"]' );
    
            $content = '';
            $stop_content = false;
            $question_element->children()->each( function(Crawler $crawler) use (&$content, &$stop_content){
                if($crawler->nodeName() == 'ol' || $crawler->attr( 'class') == 'exp'){
                    $stop_content = true;
                }
                if(!$stop_content){
                    $content .= trim( $crawler->html() ) . "\n";
                }
            });
            $data['content'] = $this->src_to_base64_image( $content, $images);
    
            try {
                $answers = $question_element->filterXPath( '//ol[@class="quiz-list"]/li' );
                $answers->each( function ( Crawler $li ) use ( &$data, $images ) {
                    $data['choices'][] = $this->src_to_base64_image( trim($li->html()), $images);
                    if(strpos( $li->attr( 'class'), 'correctAnswer') !== false){
                        $data['answers'][] = count( $data['choices'] ) - 1;
                    }
                });
            } catch ( \Exception $ex ) {
        
            }
    
            try{
                $suggestion = trim( $question_element->filterXPath( '//div[@class="exp"]' )->html() );
                $suggestion = mb_substr( $suggestion, strpos( $suggestion, "</h2>"));
                $data['suggestion'] = $this->src_to_base64_image( $suggestion, $images);
            }catch (\Exception $ex){
//                dd($url);
                dump("Get suggestion error " . $ex->getMessage());
            }
            
        } catch ( \Exception $ex ) {
            dump("Get content error " . $ex->getMessage());
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
            return $response->getStatusCode() == 200 ? $url : false;
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