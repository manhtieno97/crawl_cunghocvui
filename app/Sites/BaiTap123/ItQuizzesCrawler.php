<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-30
 * Time: 02:45
 */

namespace App\Sites\BaiTap123;


use App\Crawler\Browsers\Guzzle;
use App\Crawler\Entities\Exam;
use App\Crawler\Entities\Quiz;
use App\Crawler\MediaHelper;
use App\Libs\IdToPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ItQuizzesCrawler {
    
    protected $prefix = "https://www.baitap123.com/trac-nghiem-vui/ket-qua/72-tin-hoc/__id__-trac-nghiem-tin-hoc-excel-de-1.html";
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
            $response = $this->client->post( $url , [
                'form_params' => [
                    'ds_dapan' => '4687-17988*ntson1009*'
                ]
            ]);
            $html = $response->getBody()->getContents();
            $data = $this->processHtml( $html, $url );
            if($data && $data['url']){
                file_put_contents( $data_file, json_encode( $data ) );
            }
        } else {
            return false;
        }
    }
    
    protected function processHtml( $html, $url = '' ) {
        $crawler = new Crawler();
        $crawler->addHtmlContent( $html );
        if(empty(trim($crawler->filter( 'div.lb_bailam')->text()))){
            dump($url . " is not exam.");
            return false;
        };
    
        $exam = new Exam();
        
        $exam->setUrl( $url );
        
        try{
            $title = trim($crawler->filter( 'div.kq_info div.kq_level')->text());
            if(!preg_match( "/(cao đẳng|đại học|IQ|tin học|giao thông|THPT|đề thi)/ui", $title)){
                return false;
            }
            
            if(preg_match( "/\(\.+\)/", $title, $matches)){
                $exam->addKeyword( $matches[1]);
            }
            $keyword = Str::replaceFirst( "Trắc nghiệm", "", $title);
            $keyword = preg_replace( "/[\(\-].*$/", "", $keyword);
            $keyword = preg_replace( "/đề.*$/ui", "", $keyword);
            $exam->addKeyword( trim( $keyword ));
            $exam->setTitle( $title );
        }catch (\Exception $ex){
            dump("Can not get title " . $ex->getMessage());
        }
        
        $images = MediaHelper::download_images_from_crawler( $crawler->filter( 'div.lb_bailam') );
        
        $quiz_elements = $crawler->filter( 'div.lb_bailam div.lb_question_item');
        $quiz_elements->each( function ( Crawler $quz_element ) use ($exam, $images) {
            $quiz = new Quiz();
            try{
                $content = trim($quz_element->filter('div.lb_cauhoi')->html());
                $content = MediaHelper::src_to_base64_image( $content, $images);
                $quiz->setContent( $content );
            }catch (\Exception $ex){
        
            }
    
            //lb_question
            try {
                $answers = $quz_element->filter( 'div.lb_question div.lb_q_row' );
                $answers->each( function ( Crawler $div, $i ) use ( $quiz, $images ) {
                    $choice = trim($div->filter( 'span.lb_question_dapan_item span.lb_q_text span' )->html());
                    $quiz->addChoice( MediaHelper::src_to_base64_image( $choice, $images ));
                    try{
                        if($div->filter( 'img.imgTick')->count()){
                            $quiz->addAnswer( $i );
                        }
                    }catch (\Exception $ex){
                    
                    }
                });
            } catch ( \Exception $ex ) {
                dump("Can not get choices and answer");
            }
            
            $exam->addQuiz( $quiz );
        });
        
        return $exam->toArray();
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