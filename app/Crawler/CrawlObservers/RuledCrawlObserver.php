<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 14:32
 */

namespace App\Crawler\CrawlObservers;


use App\Crawler\LinkProcessor\CommonLinkFilter;
use App\Crawler\LinkProcessor\DownloadableLinkFilter;
use App\Crawler\Selector\SiteSelectors;
use App\Crawler\Traits\HasDomCrawler;
use App\Crawler\Traits\HasSiteSelector;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlObserver;
use Spatie\Crawler\CrawlUrl;
use Vuh\CliEcho\CliEcho;

abstract class RuledCrawlObserver extends CrawlObserver {
    
    use HasSiteSelector;
    use HasDomCrawler;
    
    /**
     * Called when the crawler has crawled the given url successfully.
     *
     * @param CrawlUrl $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param int $parent_id
     */
    public function crawled( CrawlUrl $url, ResponseInterface $response, $parent_id = 0 ) {
        
        $response->getBody()->rewind();
        $this->prepare( $response->getBody()->getContents() );
        
        CliEcho::infonl( "[" . $url->getStep() . "] " . $url . " from " . $parent_id);
        CliEcho::infonl( "\t Data \n");dump($url->getData());
    
        CliEcho::infonl( "\t Get data");
        $page_data = $this->tryToGetData($url->getStep(), true);
        CliEcho::infonl( "\t Found \n");dump($page_data);
        if(count($page_data['document_name'])){
            $url->setData( 'document_name', $page_data['document_name'][0]);
        }
        if($page_data['keywords']){
            $url->setData( 'keywords', $page_data['keywords']);
        }
        $this->queue->updateData( $url );
        
        CliEcho::infonl( "\t Get download link");
        $links = $this->tryToGetDownloadLinks( $url->getStep() );
        CliEcho::infonl( "\t Found " . count( $links ));
        foreach ($links as $link){
            dump( $link );
        }
        
        CliEcho::infonl( "\t Check links ");
        $this->checkLinks( $links, $url, $page_data);
        
    }
    
    protected function checkLinks($links, CrawlUrl $url, array $page_data){
        foreach ($links as $index => $link){
            $link['href'] = $this->selector->assertFullUrl( $link['href'] );
            // check common type
            $check_result = (new CommonLinkFilter)->check( $link['href'], true);
            if($check_result['check'] == true){
                $link['type'] = $check_result['type'];
            }elseif((new DownloadableLinkFilter())->check( $link['href'])){
                $link['type'] = 'downloadable';
            }else{
                continue;
            }
            // chuẩn hoá link text nếu có extension
            $link['text'] = preg_replace( "/\.(pdf|doc|docx|ppt|pptx|xls|xlsx|pptm)$/ui", "", $link['text']);
            // get document name nếu có từ parent
            $parent_url = $url->getParentId() ? $this->queue->getUrlById( $url->getParentId() ) : false;
            if($parent_url){
                $link['document_name'] = $link['document_name'] ?? $url->getData('document_name') ?? $parent_url->getData('document_name');
                $link['keywords'] = $link['keywords'] ?? $url->getData('keywords') ?? $parent_url->getData('keywords');
            }
            $link['page_url'] = $url->url->__toString();
            $link['page_title'] = $page_data['page_title'];
            $link['page_description'] = $page_data['page_description'];
            $link['index'] = $index + 1;
            $this->processLink($link);
        }
    }
    
    abstract protected function processLink(array $link);
    
    /**
     * Called when the crawler had a problem crawling the given url.
     *
     * @param CrawlUrl $url
     * @param \GuzzleHttp\Exception\RequestException $requestException
     * @param int $parent_id
     */
    public function crawlFailed( CrawlUrl $url, RequestException $requestException, $parent_id = 0 ) {
        dump('Can not crawl ' . $url);
    }
    
}