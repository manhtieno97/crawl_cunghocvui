<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-22
 * Time: 11:52
 */

namespace App\SimpleSpider\CrawlQueue;


use App\Libs\Hash;
use Psr\Http\Message\UriInterface;
use App\SimpleSpider\CrawlUrl;
use App\SimpleSpider\Exception\InvalidUrl;
use App\SimpleSpider\Exception\UrlNotFoundByIndex;

class MysqlCrawlQueue implements CrawlQueue {
    
    /**
     * @var int
     */
    protected $site;
    protected $connection;
    
    /**
     * CrawlSqliteQueue constructor.
     */
    public function __construct($site, $connection = null) {
        
        $this->site = $site;
        $this->connection = $connection ?? \DB::connection();
        
    }
    
    /**
     * @param null $status
     * @param int $page
     * @param int $limit
     *
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function paginate($status = null, $page = 1, $limit = 20){
        $builder = $this->connection->table( $this->getTableName())
                                    ->where( 'site_id', $this->site);
        if($status == CrawlUrl::CRAWL_FAIL){
            $builder->where('status', '>', 200);
        }elseif($status !== null){
            $builder->where('status', $status);
        }
        return $builder->simplePaginate($limit, ['*'], 'page', $page);
    }
    
    public function reset(){
        return $this->connection
            ->table( $this->getTableName())
            ->where( 'site_id', $this->site)
            ->delete();
    }
    
    public function resume(){
        return $this->connection->table( $this->getTableName())
                         ->where( 'site_id', $this->site)
                         ->where('status', CrawlUrl::CRAWL_VISITING)
                         ->update( ['status' => CrawlUrl::CRAWL_INIT]);
    }
    
    public function getTableName(){
        return "stacks";
    }
    
    public function getByUrl($url){
        $hash = Hash::hashUrl($url);
        return $this->connection->table( $this->getTableName() )
            ->where( 'site_id', $this->site)
            ->where( 'url_hash', $hash)->first();
    }
    
    public function add( CrawlUrl $url ) {
        if($this->has( $url )){
            return false;
        }
        
        $inserted = $this->connection->table( $this->getTableName())
//            ->where( 'site_id', $this->site)
            ->insertGetId( [
            'parent_id' => $url->getParentId(),
            'site_id' => $this->site,
            'step' => $url->getStep(),
            'url' => $url->url,
            'url_hash' => Hash::hashUrl($url->url),
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
                                ->where( 'url_hash', Hash::hashUrl($url) )
                                ->exists();
    }
    
    public function hasPendingUrls(): bool {
        
        return $this->connection->table( $this->getTableName() )
                                ->where( 'site_id', $this->site)
                                ->where( 'status', CrawlUrl::CRAWL_INIT )
                                ->exists();
        
    }
    
    public function getUrlById( $id ): CrawlUrl {
        $first = $this->connection->table( $this->getTableName() )
                                  ->where( 'site_id', $this->site)
                                  ->where( 'id', $id )
                                  ->first();
        if($first){
            return CrawlUrl::fromObject($first);
        }else{
            throw new UrlNotFoundByIndex("#{$id} crawl url not found in collection");
        }
    }
    
    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl() {
        $first = $this->connection->table( $this->getTableName() )
                                  ->where( 'site_id', $this->site)
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
                                ->where( 'site_id', $this->site)
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
                                ->where( 'site_id', $this->site)
                                ->where( 'id', $crawlUrl->getId() )
                                ->update( ['data' => $crawlUrl->getData(null, null, true) ] );
    }
    
    
    protected function markProcessCode(CrawlUrl $crawlUrl, $code){
        $data = [ 'status' => $code ];
        if ( $code == CrawlUrl::CRAWL_VISITING ) {
            $data ['visited'] = \DB::raw( 'visited + 1' );
        }
        return $this->connection->table( $this->getTableName() )
                                ->where( 'site_id', $this->site)
                                ->where( 'id', $crawlUrl->getId() )
                                ->update( $data );
    }
    
}