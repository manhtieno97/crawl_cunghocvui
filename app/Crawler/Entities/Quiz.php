<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-01
 * Time: 23:17
 */

namespace App\Crawler\Entities;


use Illuminate\Support\Arr;

class Quiz {
    
    const TYPE_CHOICES = 'choices'; // trắc nghiệm
    const TYPE_ESSAY = 'essay'; // tự luận
    const TYPE_FILLING = 'filling'; // điền từ
    const TYPE_SORTING = 'sorting'; // điền từ
    const TYPE_GROUP = 'group'; //Nhóm các câu có chung yêu cầu
    
    protected $type;
    protected $keywords = [];
    protected $pre_content;
    protected $content;
    
    protected $source_title;
    protected $url; // source url
    protected $choices = []; // các lựa chọn
    protected $answers = []; // các lựa chọn đúng, hoặc các cặp vị trí và từ cần điền đối với loại filling
    protected $suggestion; // hướng dẫn
    protected $solution; // câu trả lời đầy đủ, hoặc lời giải đối với loại tự luận
    
    protected $quizz_count = 1;
    
    /**
     * @var array
     * các loại attachment image|media(audio/video)|url|other
     */
    protected $attachments = []; /** @todo chưa có trường hợp dùng cụ thể */
    
    protected $sub_quizzes = [];
    
    /**
     * Quiz constructor.
     *
     * @param string $type
     *
     * @throws \Exception
     */
    public function __construct( $type = 'choices' ) {
        $this->setType( $type );
    }
    
    
    ////////////// GETTER/SETTER //////////////
    
    /**
     * @return string
     */
    public function getType(): string {
        return $this->type;
    }
    
    /**
     * @param string $type
     *
     * @throws \Exception
     */
    public function setType( string $type ): void {
        if(!in_array( $type, [
            self::TYPE_CHOICES,
            self::TYPE_ESSAY,
            self::TYPE_FILLING,
            self::TYPE_SORTING,
            self::TYPE_GROUP,
        ])){
            throw new \Exception("Wrong quiz type :: " . $type );
        }
        $this->type = $type;
    }
    
    /**
     * @return array
     */
    public function getKeywords(): array {
        return $this->keywords;
    }
    
    /**
     * @param array $keywords
     * @param bool $appending
     *
     * @return Quiz
     */
    public function setKeywords( array $keywords, $appending = false ): void {
        $this->keywords = $appending ? array_merge( $this->keywords, $keywords) : $keywords;
    }
    
    /**
     * @return mixed
     */
    public function getPreContent() {
        return $this->pre_content;
    }
    
    /**
     * @param mixed $pre_content
     */
    public function setPreContent( $pre_content ): void {
        $this->pre_content = $pre_content;
    }
    
    /**
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }
    
    /**
     * @param mixed $content
     */
    public function setContent( $content ): void {
        $this->content = $content;
    }
    
    /**
     * @return mixed
     */
    public function getSourceTitle() {
        return $this->source_title;
    }
    
    /**
     * @param mixed $source_title
     */
    public function setSourceTitle( $source_title ): void {
        $this->source_title = $source_title;
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
     * @return array
     */
    public function getChoices(): array {
        return $this->choices;
    }
    
    /**
     * @param array $choices
     */
    public function setChoices( array $choices ): void {
        $this->choices = $choices;
    }
    
    public function addChoice($choice, $key = null){
        if($key){
            $this->choices[$key] = $choice;
        }else{
            $this->choices[] = $choice;
        }
        
    }
    
    /**
     * @return array
     */
    public function getAnswers(): array {
        return $this->answers;
    }
    
    /**
     * @param array $answers
     */
    public function setAnswers( array $answers ): void {
        $this->answers = $answers;
    }
    
    public function addAnswer($answer){
        $this->answers[] = $answer;
    }
    
    /**
     * @return mixed
     */
    public function getSuggestion() {
        return $this->suggestion;
    }
    
    /**
     * @param mixed $suggestion
     */
    public function setSuggestion( $suggestion ): void {
        $this->suggestion = $suggestion;
    }
    
    /**
     * @return mixed
     */
    public function getSolution() {
        return $this->solution;
    }
    
    /**
     * @param mixed $solution
     */
    public function setSolution( $solution ): void {
        $this->solution = $solution;
    }
    
    /**
     * @return array
     */
    public function getAttachments(): array {
        return $this->attachments;
    }
    
    /**
     * @param array $attachments
     */
    public function setAttachments( array $attachments ): void {
        $this->attachments = $attachments;
    }
    
    /**
     * @return mixed
     */
    public function getSubQuizzes() {
        return $this->sub_quizzes;
    }
    
    /**
     * @param mixed $sub_quizzes
     */
    public function setSubQuizzes( array $sub_quizzes ): void {
        $this->sub_quizzes = [];
        $this->quizz_count = 0;
        foreach ($sub_quizzes as $quiz){
            $this->addSubQuiz( $quiz );
        }
    }
    
    public function addSubQuiz(Quiz $quiz){
        $this->sub_quizzes[] = $quiz;
        $this->quizz_count++;
    }
    
    
    ////////////// END GETTER/SETTER //////////////
    
    
    ////////////// IMPORT/EXPORT //////////////
    
    public static function fromArray(array $data) : self {
        if(empty( $data['type'])){
            throw new \Exception("Not valid type");
        }
        $quiz = new self($data['type']);
        $quiz->setContent( Arr::get( $data, 'content'));
        $quiz->setSourceTitle( Arr::get( $data, 'source_title'));
        $quiz->setUrl( Arr::get( $data, 'url'));
        $quiz->setKeywords( Arr::get( $data, 'keywords', []));
        $quiz->setChoices( Arr::get( $data, 'choices', []));
        $quiz->setAnswers( Arr::get( $data, 'answers', []));
        $quiz->setSuggestion( Arr::get( $data, 'suggestion'));
        $quiz->setSolution( Arr::get( $data, 'solution'));
        if(!empty( $sub_quizzes = Arr::get( $data, 'sub_quizzes', []))){
            foreach ($sub_quizzes as $quiz_data){
                $sub_quiz = Quiz::fromArray( $quiz_data );
                $quiz->addSubQuiz( $sub_quiz );
            }
        }
        return $quiz;
    }
    
    public static function fromFile($path) : self {
        $data = \GuzzleHttp\json_decode( file_get_contents( $path ), true);
        return self::fromArray( $data );
    }
    
    public function toArray() : array {
        return [
            'type' => $this->type,
            'source_title' => $this->source_title,
            'url' => $this->url,
            'keywords' => $this->keywords,
            'pre_content' => $this->pre_content,
            'content' => $this->content,
            'choices' => $this->choices,
            'answers' => $this->answers,
            'suggestion' => $this->suggestion,
            'solution' => $this->solution,
            'sub_quizzes' => array_map( function(Quiz $quiz){
                return $quiz->toArray();
            }, $this->sub_quizzes),
        ];
    }
    
    public function toFile($path) : bool {
    
    }
    
    public function toHtml($path = null) {
    
    }
    
    ////////////// END IMPORT/EXPORT //////////////
    
}