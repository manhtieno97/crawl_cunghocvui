<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 8/7/17
 * Time: 19:11
 */

namespace App\Crawler\LinkProcessor;


class DontCrawlUrlFilter extends LinkFilter {
	
	protected $type = 'dont_crawl_url';
    
    /**
     * DontCrawlUrlFilter constructor.
     *
     * @param string $prefix
     * @param string $base
     *
     * @throws \Exception
     */
	public function __construct($prefix = '', $base = '') {
		parent::__construct($prefix, $base);
		
		$ignoreFilter = config( 'crawler.link_filter.dont_crawl_filter', [] );
		
		$this->patterns = array_get( $ignoreFilter, 'patterns', [] );
		$this->contains = array_get( $ignoreFilter, 'contains', [] );
		$this->starts   = array_get( $ignoreFilter, 'starts', [] );
		$this->ends     = array_get( $ignoreFilter, 'ends', [] );
	}
	
	
}