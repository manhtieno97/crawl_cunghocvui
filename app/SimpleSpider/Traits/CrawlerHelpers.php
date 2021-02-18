<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-12-01
 * Time: 01:33
 */

namespace App\SimpleSpider\Traits;


use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

trait CrawlerHelpers {
    
    /**
     * @param $filter
     * @param Crawler|null $crawler
     *
     * @return null|Crawler
     */
    protected function filter( $filter, Crawler $crawler = null ) {
    
        if(!$crawler && $this->crawler){
            return $this->filter( $filter, $this->crawler );
        }
        
        try {
            if(Str::startsWith( "/", $filter)){
                return $crawler->filterXpath( $filter );
            }else{
                return $crawler->filter( $filter );
            }
        } catch ( \Exception $ex ) {
            return null;
        }
    }
    
    /**
     * @param $filterXpath
     * @param Crawler $crawler
     *
     * @return null|Crawler
     */
    protected function filterXpath( $filterXpath, Crawler $crawler ) {
        try {
            return $crawler->filter( $filterXpath );
        } catch ( \Exception $ex ) {
            return null;
        }
    }
    
    protected function filterLinks( Crawler $crawler ) {
        $result = new Collection();
        if ( $crawler->count() == 1 ) {
            $elements = [ $crawler->getNode( 0 ) ];
        }else{
            $elements = $crawler;
        }
        /** @var \DOMNode $element */
        foreach ( $elements as $element ) {
            if ( strtolower( $element->nodeName ) != 'a' ) {
                try {
                    $links = $crawler->filterXPath( "//a" );
                    foreach ( $links as $link ) {
                        $result->push( $this->getDomNodeLinkInfo( $link ) );
                    }
                } catch ( \Exception $ex ) {
                }
            } else {
                $result->push( $this->getDomNodeLinkInfo( $element ) );
            }
        }
        
        return $result;
    }
    
    /**
     * @param \DOMNode $a
     *
     * @return array ['href', 'title', 'text']
     */
    protected function getDomNodeLinkInfo( \DOMNode $a ) {
        
        $href = '';
        $title = '';
        $text = '';
        
        try {
            if ( $a->nodeName == 'iframe' ) {
                $href = $this->safeGetNodeValue( $a, 'src' );
            } else {
                $href = $this->safeGetNodeValue( $a, 'href' );
                $title = $this->safeGetNodeValue( $a, 'title' );
                $text = trim( $a->textContent );
            }
        } catch ( \Exception $ex ) {
        }
        
        if ( $href == '#' || ! $href || $href == 'javascript:void(0);' ) {
            $onclick = $this->safeGetNodeValue( $a, 'onclick' );
            $matches = [];
            $matched = preg_match( '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $onclick, $matches );
            if ( $matched ) {
                $href = $matches[0];
            }
        }
        
        return compact( 'href', 'title', 'text' );
        
    }
    
    protected function safeGetNodeValue( \DOMNode $node, $attribute ) {
        if ( $nodeItem = $node->attributes->getNamedItem( $attribute ) ) {
            return $nodeItem->nodeValue;
        } else {
            return null;
        }
    }
    
    protected function getContent($filter, $content = 'text', Crawler $crawler = null){
        if(!$crawler && $this->crawler){
            return $this->getContent( $filter, $filter, $this->crawler );
        }
        
        if(Str::startsWith( "/", $filter)){
            $element = $this->filterXpath( $filter, $crawler );
        }else{
            $element = $this->filter( $filter, $crawler );
        }
        
        if($element){
            switch ($content){
                case 'html':
                    return trim( $element->html() );
                default:
                    return trim( $element->text() );
            }
        }
        
    }
    
}