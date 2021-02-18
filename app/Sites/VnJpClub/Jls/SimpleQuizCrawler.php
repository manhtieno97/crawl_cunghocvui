<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-16
 * Time: 23:16
 */

namespace App\Sites\VnJpClub\Jls;


use App\Crawler\Browsers\Guzzle;
use App\Crawler\DomHelper;
use App\Crawler\Entities\Quiz;
use App\Crawler\MediaHelper;
use App\Libs\PhpUri;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class SimpleQuizCrawler {
    
    protected $default_keywords = [];
    protected $url;
    protected $force;
    protected $pages = [];
    protected $client;
    protected $letters = [
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
        4 => 'E',
    ];
    
    /**
     * SimpleQuizCrawler constructor.
     *
     * @param array $default_keywords
     */
    public function __construct( $url, array $default_keywords = [], $force = false ) {
        $this->default_keywords = $default_keywords;
        $this->url = $url;
        $this->force = $force;
        $this->client = new Client( config( 'crawler.browsers.guzzle' ) );
    }
    
    protected function getPages(){
        $html = ( new Guzzle( $this->client ) )->getHtml( $this->url );
        $crawler = new Crawler();
        $crawler->addHtmlContent( $html );
        $els = $crawler->filter( '.baihoc table ul li a');
        $els->each( function ( Crawler $el ) {
            $url = $el->attr( 'href');
            $this->pages[] = PhpUri::parse( $this->url )->join( $url );
        });
    }
    
    public function startCrawling($data_dir = null){
        dump("Getting children pages");
        $this->getPages();
        dump("Got " . count( $this->pages ) . " pages");
        if($data_dir){
            if(!is_dir( $data_dir )){
                throw new \Exception($data_dir . " must be a directory ");
            }
            $data_dir = realpath( rtrim( $data_dir, "/" ) );
            $data_dir = $data_dir . "/" . md5($this->url);
            if(file_exists( $data_dir )){
                if(!$this->force){
                    throw new \Exception("Page " . $this->url . " crawled at " . $data_dir );
                }
            }else{
                $old_mask = umask(0);
                @mkdir( $data_dir, 0777, true );
                umask($old_mask);
            }
            
        }
        $this->getAllQuizzes($data_dir);
        
    }
    
    protected function getAllQuizzes($data_dir = ''){
        foreach ($this->pages as $page){
            dump( "Getting quizzes from " . $page);
            $quizzes = $this->getQuizzesFromPage( $page );
            if($data_dir){
                $prefix = $data_dir . "/" . md5($page);
                foreach ($quizzes as $i => $quiz){
                    $data = json_encode( $quiz->toArray());
                    file_put_contents( $prefix . "_" . (string)$i . ".json", $data);
                }
            }else{
                dump($quizzes);
            }
        }
    }
    
    protected function getQuizzesFromPage($url){
        $html = ( new Guzzle( $this->client ) )->getHtml( $url );
        $crawler = new Crawler();
        $crawler->addHtmlContent( $html );
        $source_title = $crawler->filter( 'title')->text();
        $question_elements = $crawler->filter( '#khungtracnghiem');
        $questions = [];
        $question_elements->each( function ( Crawler $question_element ) use(&$questions, $url, $source_title) {
            $quiz = new Quiz();
            
            // url
            $quiz->setUrl( $url );
            
            // source title
            $quiz->setSourceTitle( $source_title );
            
            // keywords
            $quiz->setKeywords( $this->default_keywords );
            
            // content
            $content_element = $question_element->filter( '#question');
            $content_element = DomHelper::removeByFilter( $content_element, 'div.bai_stt');
            $quiz->setContent( trim($content_element->html()) );
            
            // choice
            $choice_elements = $question_element->filter( 'td#table_tracnghiem');
            $choice_elements->each( function ( Crawler $choice_element, $i ) use ($quiz){
                $quiz->addChoice( trim(DomHelper::removeInputs( $choice_element )->html()), $this->letters[$i] );
            });
    
            $questions[] = $quiz;
        });
        
        if(preg_match_all( "/answer_id\[\d+\] \= new Array\((\d+),(\d+),(\d+),(\d+)\);/", $html, $js_questions)
        && preg_match_all( "/answer_correct\[\d+\] \= (\d+);/", $html, $js_answers)
        ){
            $questions_count = count($questions);
            for($i = 0; $i < $questions_count; $i++){
                for($j = 1; $j < 5; $j++){
                    if($js_questions[$j][$i] == $js_answers[1][$i]){
                        $questions[$i]->setAnswers([$this->letters[$j - 1]]);
                        break;
                    }
                }
            }
        }else if(count( $questions )){
            throw new \Exception("Not match answers from " . $url);
        }
        
        return $questions;
        
    }
    
}