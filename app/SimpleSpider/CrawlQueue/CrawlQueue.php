<?php

namespace App\SimpleSpider\CrawlQueue;

use App\SimpleSpider\CrawlUrl;

interface CrawlQueue
{
    
    /**
     * @param CrawlUrl $url
     *
     * @return CrawlUrl|bool fail when existed or CrawlUrl with inserted id
     */
    public function add(CrawlUrl $url);

    public function has($crawlUrl): bool;

    public function hasPendingUrls(): bool;

    public function getUrlById($id): CrawlUrl;

    /** @return \Spatie\Crawler\CrawlUrl|null */
    public function getFirstPendingUrl();

    public function hasAlreadyBeenProcessed(CrawlUrl $url): bool;

    public function markAsProcessing(CrawlUrl $crawlUrl);
    
    public function markAsProcessed(CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_DONE);
    
    public function markAsProcessedFail(CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_FAIL);
    
    public function updateData(CrawlUrl $crawlUrl);
}
