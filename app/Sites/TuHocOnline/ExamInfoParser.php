<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-12-01
 * Time: 02:28
 */

namespace App\Sites\TuHocOnline;


use App\Crawler\Entities\Exam;
use App\Crawler\Entities\Quiz;
use App\Crawler\MediaHelper;
use App\SimpleSpider\Traits\CrawlerHelpers;
use Symfony\Component\DomCrawler\Crawler;

class ExamInfoParser {
    
    use CrawlerHelpers;
    
    protected $crawler;
    
    /**
     * ExamInfoParser constructor.
     *
     * @param $crawler
     */
    public function __construct( $crawler ) {
        $this->crawler = $crawler;
    }
    
    public function getExam(){
        $exam = new Exam();
        
        // title
        $title = trim( $this->getContent( '.entry-content h1') );
        $exam->setTitle( $title );
        
        // keywords
        $keyword_elements = $this->filter( '#content div.article-content div.above-entry-meta span.cat-links a');
        if($keyword_elements){
            $keyword_elements->each( function ( Crawler $crawler ) use ( $exam ) {
                $exam->addKeyword( trim( $crawler->text() ));
            });
        }
        // download image
        $images = MediaHelper::download_images_from_crawler( $this->filter( 'ol.wpProQuiz_list') );
        dump("Found " . count( $images ) . ' image on this exam');
        
        // quesstions
        $question_elements = $this->filter( 'ol.wpProQuiz_list .wpProQuiz_listItem' );
        $question_elements->each( function ( Crawler $crawler, $i ) use ($exam, $images) {
            $quiz = new Quiz();
            $content_elements = $this->filter( '.wpProQuiz_question_text', $crawler);
            
            // content
            if($content_elements
//               && $content_elements->count() == 2
            ){
//                $pre_content = MediaHelper::cleanHtml($content_elements->eq( 0 )->html(), ['class', 'data\-[a-z\-]+', 'href', 'style']);
//                $pre_content = MediaHelper::src_to_base64_image( $pre_content, $images );
//                $content = MediaHelper::cleanHtml($content_elements->eq( 1 )->html(), ['class', 'data\-[a-z\-]+', 'href', 'style']);
//                $content = MediaHelper::src_to_base64_image( $content, $images );
//                $quiz->setPreContent( $pre_content );
//                $quiz->setContent( $content );
                $content = MediaHelper::cleanHtml($content_elements->html(), ['class', 'data\-[a-z\-]+', 'href', 'style']);
                $content = MediaHelper::src_to_base64_image( $content, $images );
                $content = preg_replace( "/\<\/?noscript\>/ui", "", $content);
                $quiz->setContent( $content );
            }
            
            // choices
            $choice_elements = $this->filter( 'ul.wpProQuiz_questionList li.wpProQuiz_questionListItem', $crawler);
            if($choice_elements){
                $choices = [];
                $choice_elements->each( function ( Crawler $choice_element, $i ) use (&$choices, $images, $quiz) {
                    $choices[$i] = MediaHelper::cleanHtml($choice_element->html());
                    $choices[$i] = MediaHelper::src_to_base64_image( $choices[ $i ], $images);
                    if(intval( $choice_element->attr( 'data-pos') ) === 0){
                        $quiz->addAnswer( $i );
                    }
                });
                $quiz->setChoices( $choices );
            }
            
            $exam->addQuiz( $quiz );
        });
        
        return $exam;
    }
    
}