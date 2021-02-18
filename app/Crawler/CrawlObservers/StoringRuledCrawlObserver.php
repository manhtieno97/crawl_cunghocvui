<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-24
 * Time: 16:06
 */

namespace App\Crawler\CrawlObservers;


use App\Services\SiteManager;
use Vuh\CliEcho\CliEcho;

class StoringRuledCrawlObserver extends RuledCrawlObserver {
    
    protected function processLink( array $link ) {
        try{
            SiteManager::addRawDownloadLink( $this->selector->getSiteId(), $link);
        }catch (\Exception $ex){
            CliEcho::errornl( $ex->getMessage() );
        }
    }
    
}