<?php

namespace App\SimpleSpider;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Arr;
use Psr\Http\Message\UriInterface;

class CrawlUrl
{
    
    const CRAWL_INIT = 0;
    const CRAWL_VISITING = 10;
    const CRAWL_DONE = 200; // default success code
    const CRAWL_FAIL = 1000; // default error code, or response code for specific error
    
    
    /** @var \Psr\Http\Message\UriInterface */
    public $url;

    /** @var \Psr\Http\Message\UriInterface */
    public $foundOnCrawlUrl;

    /** @var mixed */
    protected $id;
    protected $parent_id = 0;
    protected $step;
    protected $data = [];
    protected $visited = 1;
    protected $status;

    public static function create(UriInterface $url, $step, ?CrawlUrl $foundOnCrawlUrl = null, $data = null,  $id = null)
    {
        $static = new static($url, $step, $foundOnCrawlUrl);

        if ($id !== null) {
            $static->setId($id);
        }
        if($data){
            $static->data = is_array( $data ) ? $data : json_decode( $data, true);
        }

        return $static;
    }

    protected function __construct(UriInterface $url, $step, ?CrawlUrl $foundOnCrawlUrl = null)
    {
        $this->url = $url;
        $this->step = $step;
        $this->foundOnCrawlUrl = $foundOnCrawlUrl;
        if($foundOnCrawlUrl){
            $this->parent_id = $foundOnCrawlUrl->getId();
        }
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
    
    /**
     * @return int
     */
    public function getVisited()
    {
        return $this->visited;
    }

    public function setVisited($visited)
    {
        $this->visited = $visited;
    }
    
    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * @param mixed $status
     */
    public function setStatus( $status ): void {
        $this->status = $status;
    }
    
    /**
     * @return mixed
     */
    public function getParentId() {
        return $this->parent_id;
    }
    
    /**
     * @param mixed $parent_id
     */
    public function setParentId( $parent_id ) {
        $this->parent_id = $parent_id;
    }
    
    /**
     * @return mixed
     */
    public function getStep() {
        return $this->step;
    }
    
    /**
     * @param mixed $step
     */
    public function setStep( $step ) {
        $this->step = $step;
    }
    
    public function getData($key = null, $default = null, $encoded = false){
        $data = array_filter( $this->data );
        if(!$key){
            return $encoded ? json_encode( $data ) : $data;
        }else{
            $data_by_key = Arr::get( $data, $key, $default);
            return $encoded ? json_encode( $data_by_key ) : $data_by_key;
        }
    }
    
    public function setData($key, $value = null){
        if(is_array($key)){
            $this->data = array_merge( $this->data, $key);
        }else{
            $this->data[$key] = $value;
        }
    }
    
    public static function fromArrayRaw(array $data, array $parent_data = []){
        if(count($parent_data)){
            $parent = self::fromArrayRaw( $parent_data );
        }else{
            $parent = null;
        }
        $crawlUrl = self::create(
            new Uri($data['url']),
            $data['step'],
            $parent,
            Arr::get( $data, 'data', null),
            Arr::get( $data, 'id')
        );
        $crawlUrl->setVisited( Arr::get( $data, 'visited', 0));
        return $crawlUrl;
    }
    
    public static function fromObject( $data, $parent_data = null ){
        if($parent_data){
            $parent = self::fromObject( $parent_data );
        }else{
            $parent = null;
        }
        $crawlUrl = self::create(
            new Uri($data->url),
            $data->step,
            $parent,
            $data->data,
            $data->id
        );
        if(isset($data->visited)){
            $crawlUrl->setVisited( $data->visited);
        }
        if(isset($data->status)){
            $crawlUrl->setStatus( $data->status);
        }
        if(!$parent && isset( $data->parent_id )){
            $crawlUrl->setParentId( $data->parent_id );
        }
        return $crawlUrl;
    }
    
    public function toArray($encode_data = false){
        $data = [
            'url' => $this->url->__toString(),
            'url_hash' => md5( $this->url->__toString() ),
            'parent_id' => $this->parent_id,
            'step' => $this->step,
            'data' => $encode_data ? json_encode( $this->data ) : $this->data,
            'visited' => $this->visited,
            'status' => $this->status,
        ];
        if($this->id){
            $data['id'] = $this->id;
        }
        return $data;
    }
    
    public function __toString() {
        return $this->url->__toString();
    }
    
}
