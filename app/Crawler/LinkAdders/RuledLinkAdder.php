<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 14:30
 */

namespace App\Crawler\LinkAdders;


use App\Crawler\Traits\HasDomCrawler;
use App\Crawler\LinkProcessor\Ignore;
use App\Crawler\Traits\HasSiteSelector;
use App\Libs\PhpUri;
use GuzzleHttp\Psr7\Uri;
use Spatie\Crawler\Crawler;
use Spatie\Crawler\CrawlUrl;
use Spatie\Crawler\LinkAdderAbstract;

class RuledLinkAdder extends LinkAdderAbstract {
    
    use HasSiteSelector;
    use HasDomCrawler;
    
    protected $ignoreLinkFilter;
    
    /**
     * RuledLinkAdder constructor.
     *
     * @param Crawler|null $crawler
     */
    public function __construct(?Crawler $crawler = null)
    {
        parent::__construct($crawler);
        $this->ignoreLinkFilter = new Ignore();
    }
    
    
    public function addFromHtml( string $html, CrawlUrl $foundOnUrl ) {
        
        $this->prepare( $html );
    
        $from_step = $foundOnUrl->getStep();
        
        // get child links
        $links = $this->addChildFromLinkSelector( $from_step, $foundOnUrl);
    
    }
    
    protected function addChildFromLinkSelector($from_step, $foundOnUrl){
        $child_selectors = $this->selector->getChildren( $from_step, 'link');
        foreach ($child_selectors as $child_selector){
            $links = $this->getElementBySelector( $child_selector['selector'], 'link', $child_selector['multiple']);
            
            /** @var \DOMNode $link */
            foreach ($links as $link){
                $link_info = $this->getDomNodeLinkInfo($link);
                $crawlUrl = $this->addLinkToQueue( $link_info, $child_selector['id'], $foundOnUrl);
//                dump($crawlUrl ? $crawlUrl->toArray() : $crawlUrl);
            }
        }
    }
    
    protected function addLinkToQueue($link_info, $step, $foundOnUrl){
        // full link
        $link_info['href'] = $this->selector->assertFullUrl( $link_info['href'] );
        // ignored links
        if($this->ignoreLinkFilter->check( $link_info['href'])){
            return false;
        }
        // check ignore external link
        if(!$this->selector->isMatchStartDomain( $link_info['href'] )){
            return false;
        }
        
        // standardize url
        if(strpos("//", $link_info['href']) === false){ // relative url
            $url = PhpUri::parse( $this->selector->getStartUrl())->join( $link_info['href']);
        }else{
            $url = $link_info['href'];
        }
        $url = new Uri($url);
        
        // duplicated
        if($this->crawler->getCrawlQueue()->has( $url )){
            return false;
        }
        
        // add to queue
        $crawlUrl = $this
            ->crawler
            ->getCrawlQueue()
            ->add( CrawlUrl::create( $url,
                $step, $foundOnUrl,
                [ 'text' => $link_info['text'], 'title' => $link_info['title'] ] )
            );
        
        return $crawlUrl;
    }
    
}