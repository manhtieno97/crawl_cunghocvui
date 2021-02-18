<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-30
 * Time: 13:28
 */

namespace App\Crawler\Entities;


use Illuminate\Support\Arr;

class Exam {
    
    protected $quizzes = [];
    protected $keywords = [];
    protected $url;
    protected $title;
    
    /**
     * Exam constructor.
     *
     * @param array $quizzes
     */
    public function __construct( array $quizzes = [], $keywords = [], $title = '', $url = '' ) {
        $this->setTitle( $title );
        $this->setKeywords( $keywords );
        $this->setUrl( $url );
        $this->setQuizzes( $quizzes );
    }
    
    /**
     * @return array
     */
    public function getQuizzes(): array {
        return $this->quizzes;
    }
    
    /**
     * @param array $quizzes
     */
    public function setQuizzes( array $quizzes ): void {
        $this->quizzes = [];
        foreach ($quizzes as $quiz){
            $this->addQuiz( $quiz );
        }
    }
    
    public function addQuiz(Quiz $quiz){
        
        $this->quizzes[] = $quiz;
        
    }
    
    /**
     * @return array
     */
    public function getKeywords(): array {
        return $this->keywords;
    }
    
    /**
     * @param array $keywords
     */
    public function setKeywords( array $keywords ): void {
        $this->keywords = $keywords;
    }
    
    public function addKeyword($keyword){
        $keyword = trim( $keyword );
        if($keyword && !in_array( $keyword, $this->keywords)){
            $this->keywords[] = $keyword;
        }
    }
    
    /**
     * @return mixed
     */
    public function getUrl() {
        return $this->url;
    }
    
    /**
     * @param mixed $url
     */
    public function setUrl( $url ): void {
        $this->url = $url;
    }
    
    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * @param mixed $title
     */
    public function setTitle( $title ): void {
        $this->title = $title;
    }
    
    
    public function toArray(){
        return [
            'title' => $this->title,
            'url' => $this->url,
            'keywords' => $this->keywords,
            'quizzes' => array_map( function ( $quiz ){
                /** @var Quiz $quiz */
                return $quiz->toArray();
            }, $this->quizzes)
        ];
    }
    
    public static function fromArray(array  $data){
        $quizzes_data = Arr::get( $data, 'quizzes', []);
        $exam = new Exam(
            [],
            Arr::get( $data, 'keywords', []),
            Arr::get( $data, 'title'),
            Arr::get( $data, 'url')
            );
        foreach ($quizzes_data as $quiz_data){
            $exam->addQuiz( Quiz::fromArray( $quiz_data ));
        }
        return $exam;
    }
    
}