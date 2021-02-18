<?php
/**
 * Mỗi node có các hàm validNodeName, và processDataNodeName
 * User: hocvt
 * Date: 2019-11-30
 * Time: 16:42
 */

namespace App\Sites\TuHocOnline;


use App\Crawler\Browsers\Guzzle;
use App\Crawler\Browsers\PhantomJsLocal;
use App\Libs\IdToPath;
use App\SimpleSpider\Traits\CrawlerHelpers;
use GuzzleHttp\Psr7\Uri;
use App\SimpleSpider\CrawlQueue\SqliteCrawlQueue;
use App\SimpleSpider\CrawlUrl;
use Illuminate\Support\Arr;
use Symfony\Component\DomCrawler\Crawler;

class Spider {
    
    use CrawlerHelpers;
    
    protected $stack;
    protected $force;
    protected $data_dir;
    protected $start_urls = [
        'https://tuhoconline.net/category/luyen-thi-tieng-nhat/luyen-thi-n5/de-thi-tieng-nhat-n5',
        'https://tuhoconline.net/category/luyen-thi-tieng-nhat/luyen-thi-n4/de-thi-n4',
        'https://tuhoconline.net/category/luyen-thi-tieng-nhat/luyen-thi-n3/de-thi-n3',
        'https://tuhoconline.net/category/luyen-thi-tieng-nhat/luyen-thi-n2/de-thi-n2',
        'https://tuhoconline.net/category/luyen-thi-tieng-nhat/luyen-thi-n1/de-thi-n1',
    ];
    protected $rules = [
        'root'   => [
            'next' => [
                'filter' => 'div.wp-pagenavi a.nextpostslink',
//                'filterXpath' => ''.
            ],
        ],
        'next'   => [
            'detail' => [
                'filter' => '.article-container article h2.entry-title a',
            ],
            'next' => [
                'filter' => 'div.wp-pagenavi a.nextpostslink',
            ]
        ],
        'detail' => [
           // 'validator' => 'validUrl',// pass $crawlUrl, $html/Crawler,
           // 'on_valid'   => 'whenValid',// pass $crawlUrl, $html/Crawler,
//            'on_invalid' => 'whenInvalid',// pass $crawlUrl, $html/Crawler,
            'on_valid' => 'getExam',// pass $crawlUrl, $html/Crawler,
            //'on_error'   => 'whenError',// pass $crawlUrl
        ]
    ];
    
    /**
     * Spider constructor.
     *
     * @param $stack_db
     */
    public function __construct( $data_dir, $force = false ) {
        $this->force = $force;
        $this->data_dir = rtrim( $data_dir, "/");
        $db = $this->data_dir . "/stack.sqlite";
        if(!file_exists( $db )){
            touch( $db );
        }
        $this->stack = new SqliteCrawlQueue( $this->data_dir . "/stack.sqlite" );
    }
    
    public function run( $reset = false, $resume = false ) {
        $initialized = $this->stack->initIfNotExists();
        if ( $initialized ) {
            foreach ( $this->start_urls as $url ) {
                $this->stack->add( CrawlUrl::create( new Uri($url), 'root' ) );
            }
        }
        if ( $reset ) {
            $this->stack->reset();
            foreach ( $this->start_urls as $url ) {
                $this->stack->add( CrawlUrl::create( new Uri($url), 'root' ) );
            }
        }
        if ( $resume ) {
            $this->stack->resume();
        }
        while ($this->stack->hasPendingUrls()) {
            
            $url = $this->stack->getFirstPendingUrl();
            $this->stack->markAsProcessing( $url );
            dump("Processing " . $url->url);
            
            $html = self::getHtml( $url->url );
            if(!$html){
                $this->runStepCallBack('on_error', $url->getStep(), true, $url);
                $this->onError($url);
                continue;
            }
            
            $crawler = new Crawler();
            $crawler->addHtmlContent( $html );
            
            // valid
            if(!$this->runStepCallBack('validator', $url->getStep(), true, $url, $crawler)){
                $this->runStepCallBack('on_invalid', $url->getStep(), true, $url, $crawler);
                continue;
            }else{
                $this->runStepCallBack('on_valid', $url->getStep(), true, $url, $crawler);
            }
            
            // get children
            $this->addChildrenByRules($url, $crawler);
            
            // callback
            
            $this->stack->markAsProcessed( $url );
        }
    }
    
    public static function getHtml( $url, $javascript = false ) {
        if ( $javascript ) {
            return ( new PhantomJsLocal() )->getHtml( $url );
        } else {
            return ( new Guzzle() )->getHtml( $url );
        }
    }
    
    public function processHtml( $html ) {
    
    }
    
    protected function addChildrenByRules(CrawlUrl $url, Crawler $crawler){
        if(!isset( $this->rules[$url->getStep()] )){
            return;
        }
        foreach ($this->rules[$url->getStep()] as $next_step => $options){
            $filter = Arr::get( $options, 'filter');
            $filterXpath = Arr::get( $options, 'filterXpath');
            
            if($filter && $filtered_elements = $this->filter( $filter, $crawler )){
                $links = $this->filterLinks( $filtered_elements );
            }elseif( $filterXpath && $filtered_elements = $this->filterXpath( $filterXpath, $crawler)){
                $links = $this->filterLinks( $filtered_elements );
            }else{
                return;
            }
            if($links){
                foreach ($links as $link_info){
                    $this->stack->add( CrawlUrl::create( new Uri($link_info['href']), $next_step ) );
                }
            }
        }
    }
    
    protected function onError(CrawlUrl $url){
        dump("Can not access " . $url);
    }
    
    protected function runStepCallBack($callback_name, $step, $default = null, ...$parameters){
        $function = Arr::get( $this->rules, $step . "." . $callback_name);
        if($function){
            $this->call( $function, ...$parameters );
        }else{
            return $default;
        }
    }
    
    protected function call($method, ...$parameters){
        if(is_string( $method )){
            return call_user_func_array( [ $this, $method ], $parameters);
        }elseif (is_array( $method )){
            return call_user_func_array( $method, $parameters);
        }
    }
    
    protected function getExam(CrawlUrl $url, Crawler $crawler){
        dump("Getting exam info");
        $parser = new ExamInfoParser( $crawler );
        $exam = $parser->getExam();
        $exam->setUrl( $url->url->__toString() );
        $id = $this->filter( 'div.entry-content div.wpProQuiz_content', $crawler);
        if(!$id || $id->count() == 0){
            dump("No exam ...");
            return false;
        }
        $id = $id->attr( 'id');
        $id = intval(str_replace( "wpProQuiz_", "", $id));
        if(count( $exam->getQuizzes() )){
            $this->saveData( $id, $exam->toArray());
        }else{
            dump("No exam ...");
        }
    }
    
    protected function saveData($id, $data){
        $data_file = $this->data_dir . "/" . IdToPath::make( $id, "exam.json");
        if(file_exists( $data_file ) && !$this->force){
            throw new \Exception("Id " . $id . " crawled at " . $data_file );
        }
    
        $old_mask = umask(0);
        @mkdir( preg_replace( "/\/[^\/]+$/", "", $data_file ), 0777, true );
        umask($old_mask);
    
        file_put_contents( $data_file, json_encode( $data ) );
    }
    
}