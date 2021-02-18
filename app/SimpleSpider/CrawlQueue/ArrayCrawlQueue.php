<?php

namespace App\SimpleSpider\CrawlQueue;

use App\SimpleSpider\CrawlUrl;
use Psr\Http\Message\UriInterface;
use App\SimpleSpider\Exception\InvalidUrl;
use App\SimpleSpider\Exception\UrlNotFoundByIndex;

class ArrayCrawlQueue implements CrawlQueue
{
    /**
     * All known URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $urls = [];

    /**
     * Pending URLs, indexed by URL string.
     *
     * @var CrawlUrl[]
     */
    protected $pendingUrls = [];

    public function add(CrawlUrl $url)
    {
        $urlString = (string) $url->url;

        if (! isset($this->urls[$urlString])) {
            $url->setId($urlString);

            $this->urls[$urlString] = $url;
            $this->pendingUrls[$urlString] = $url;
        }else{
            return false;
        }

        return $url;
    }

    public function hasPendingUrls() : bool
    {
        return (bool) $this->pendingUrls;
    }

    public function getUrlById($id) : CrawlUrl
    {
        if (! isset($this->urls[$id])) {
            throw new UrlNotFoundByIndex("Crawl url {$id} not found in collection.");
        }

        return $this->urls[$id];
    }

    public function hasAlreadyBeenProcessed(CrawlUrl $url) : bool
    {
        $url = (string) $url->url;

        if (isset($this->pendingUrls[$url])) {
            return false;
        }

        if (isset($this->urls[$url])) {
            return true;
        }

        return false;
    }

    public function markAsProcessed(CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_DONE)
    {
        $url = (string) $crawlUrl->url;

        unset($this->pendingUrls[$url]);
    }

    /**
     * @param CrawlUrl|UriInterface $crawlUrl
     *
     * @return bool
     */
    public function has($crawlUrl) : bool
    {
        if ($crawlUrl instanceof CrawlUrl) {
            $url = (string) $crawlUrl->url;
        } elseif ($crawlUrl instanceof UriInterface) {
            $url = (string) $crawlUrl;
        } else {
            throw InvalidUrl::unexpectedType($crawlUrl);
        }

        return isset($this->urls[$url]);
    }

    public function getFirstPendingUrl() : ?CrawlUrl
    {
        foreach ($this->pendingUrls as $pendingUrl) {
            return $pendingUrl;
        }

        return null;
    }
    
    public function markAsProcessing( CrawlUrl $crawlUrl ) {
        $this->markAsProcessed( $crawlUrl );
    }
    
    public function markAsProcessedFail( CrawlUrl $crawlUrl, $code = CrawlUrl::CRAWL_FAIL ) {
        $this->markAsProcessed( $crawlUrl );
    }
    
    public function updateData( CrawlUrl $crawlUrl ) {
        // TODO: Implement updateData() method.
    }
    
    
}
