<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-24
 * Time: 16:02
 */

namespace App\Crawler\CrawlObservers;


use Illuminate\Support\Arr;
use Vuh\CliEcho\CliEcho;

class PreviewRuledCrawlObserver extends RuledCrawlObserver {
    
    protected function processLink(array $link){
        CliEcho::infonl( "\t Download link : " . Arr::get( $link, 'href'));
        CliEcho::infonl( "\t\t Type : " . Arr::get( $link, 'type'));
        CliEcho::infonl( "\t\t document_name : " . Arr::get( $link, 'document_name'));
        CliEcho::infonl( "\t\t keywords : " . Arr::get( $link, 'keywords'));
        CliEcho::infonl( "\t\t page_url : " . Arr::get( $link, 'page_url'));
        CliEcho::infonl( "\t\t index : " . Arr::get( $link, 'index'));
    }
    
}