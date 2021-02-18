<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 06:22
 */

namespace App\SimpleSpider\CrawlQueue;


use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Psr\Http\Message\UriInterface;
use App\SimpleSpider\CrawlUrl;
use App\SimpleSpider\Exception\InvalidUrl;
use App\SimpleSpider\Exception\QueueErrorException;
use App\SimpleSpider\Exception\UrlNotFoundByIndex;

class SqliteCrawlQueue implements CrawlQueue {
    
    /**
     * @var int
     */
    protected $site;
    protected $connection;
    
    /**
     * CrawlSqliteQueue constructor.
     */
    public function __construct($db, $site = 0) {
        $this->site = $site;
        if(!file_exists( $db )){
            touch( $db );
        }
        
        $this->connection = self::makeConnection( $db, $site);
        
        $this->initIfNotExists();
        
    }
    
    public static function makeConnection($db, $site){
        $connection_name = str_replace( ".", "_", basename( $db )) . "_" . $site;
    
        $capsule = new Capsule();
    
        $capsule->addConnection([
            'driver'    => 'sqlite',
            'host'      => 'localhost',
            'database'  => $db,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ], $connection_name);
    
        return $capsule->getConnection($connection_name);
    }
    
    /**
     * @param null $status
     * @param int $page
     * @param int $limit
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate($status = null, $page = 1, $limit = 20){
        $builder = $this->connection->table( $this->getTableName());
        if($status == CrawlUrl::CRAWL_FAIL){
            $builder->where('status', '>', 200);
        }elseif($status !== null){
            $builder->where('status', $status);
        }
        return $builder->simplePaginate($limit, ['*'], 'page', $page);
    }
    
    /**
     * @return bool true when initialization was run, false when no need and throw exception when error
     */
    public function initIfNotExists(){
        if(!$this->connection->getSchemaBuilder()->hasTable( $this->getTableName())){
            $this->connection->getSchemaBuilder()->create($this->getTableName(), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('parent_id')->index()->default(0);
                $table->unsignedBigInteger('site_id')->index()->default(0);
                $table->string('step');
                $table->string('url');
                $table->string('url_hash')->index();
                $table->json('data')->nullable();
                $table->integer('status');
                $table->integer('visited')->default( 0 );
            });
            return true;
        }
        return false;
    }
    
    public function reset(){
        $this->connection->getSchemaBuilder()->dropIfExists( $this->getTableName());
        $this->initIfNotExists();
    }
    
    public function resume(){
        $this->connection->table( $this->getTableName())
               ->where('status', CrawlUrl::CRAWL_VISITING)
               ->update( ['status' => CrawlUrl::CRAWL_INIT]);
    }
    
    public function getTableName(){
        return "stack" . ($this->site ? "_" . $this->site : "");
    }
    
    public static function makeTableName($site_id){
        return "stack" . ($site_id ? "_" . $site_id : "");
    }
    
    public function getByUrl($url){
        $hash = md5( $url );
        return $this->connection->table( $this->getTableName() )->where( 'url_hash', $hash)->first();
    }
    
    public function add( CrawlUrl $url ) {
        if($this->has( $url )){
            return false;
        }
        
        $inserted = $this->connection->table( $this->getTableName())->insertGetId( [
            'parent_id' => $url->getParentId(),
            'site_id' => $this->site,
            'step' => $url->getStep(),
            'url' => $url->url,
            'url_hash' => md5( $url->url ),
            'status' => CrawlUrl::CRAWL_INIT,
            'data' => $url->getData(null, null, true),
        ]);
        
        if($inserted){
            $url->setId( $inserted );
        }
        
        return $url;
    }
    
    public function has( $crawlUrl ): bool {
    
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }
        
        return $this->connection->table( $this->getTableName() )
                      ->where( 'site_id', $this->site )
                      ->where( 'url_hash', md5($url) )
                      ->exists();
    }
    
    public function hasPendingUrls(): bool {
        
        return $this->connection->table( $this->getTableName() )
                      ->where( 'status', CrawlUrl::CRAWL_INIT )
                      ->exists();
        
    }
    
    public function getUrlById( $id ): CrawlUrl {
        $first = $this->connection->table( $this->getTableName() )
                        ->where( 'id', $id )
                        ->first();
        if($first){
            return CrawlUrl::fromObject($first);
        }else{
            throw new UrlNotFoundByIndex("#{$id} crawl url not found in collection");
        }
    }
    
    /** @return \App\SimpleSpider\CrawlUrl|null */
    public function getFirstPendingUrl() {
        $first = $this->connection->table( $this->getTableName() )
                        ->where( 'status', CrawlUrl::CRAWL_INIT )
                        ->first();
        if($first){
            $crawlUrl = CrawlUrl::fromObject($first);
            $crawlUrl->setId( $first->id );
            return $crawlUrl;
        }else{
            return null;
        }
    }
    
    public function hasAlreadyBeenProcessed( CrawlUrl $url ): bool {
        return $this->connection->table( $this->getTableName() )
                      ->where( 'id', $url->getId() )
                      ->where( 'status', CrawlUrl::CRAWL_DONE )
                      ->exists();
    }
    
    public function markAsProcessing( CrawlUrl $crawlUrl ) {
        return $this->markProcessCode( $crawlUrl, CrawlUrl::CRAWL_VISITING);
    }
    
    public function markAsProcessed( CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_DONE ) {
        return $this->markProcessCode( $crawlUrl, $code);
    }
    
    public function markAsProcessedFail( CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_FAIL ) {
        return $this->markProcessCode( $crawlUrl, $code);
    }
    
    public function updateData( CrawlUrl $crawlUrl ) {
        return $this->connection->table( $this->getTableName() )
                                ->where( 'id', $crawlUrl->getId() )
                                ->update( ['data' => $crawlUrl->getData(null, null, true) ] );
    }
    
    protected function markProcessCode(CrawlUrl $crawlUrl, $code){
        $data = [ 'status' => $code ];
        if ( $code == CrawlUrl::CRAWL_VISITING ) {
            $data ['visited'] = \DB::raw( 'visited + 1' );
        }
        return $this->connection->table( $this->getTableName() )
                                ->where( 'id', $crawlUrl->getId() )
                                ->update( $data );
    }
    
}