<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 00:19
 */

namespace App\Crawler\CrawlObservers;


use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlUrl;

class GetDataObserver extends CrawlObserver {
    
    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param CrawlUrl $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param int $parent_id
     */
    public function crawled( CrawlUrl $url, ResponseInterface $response, $parent_id = 0 ) {
        // TODO: Implement crawled() method.
    }
    
    /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param CrawlUrl $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param int $parent_id
     */
    public function crawlFailed( CrawlUrl $url, RequestException $requestException, $parent_id = 0 ) {
        // TODO: Implement crawlFailed() method.
    }
}