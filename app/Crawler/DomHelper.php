<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-17
 * Time: 00:25
 */

namespace App\Crawler;


use Symfony\Component\DomCrawler\Crawler;

class DomHelper {
    
    /**
     * @param Crawler $content
     *
     * @return Crawler
     */
    public static function removeInputs(Crawler $content){
        return self::removeByFilter( $content, 'input' );
    }
    
    /**
     * Remove descendant by css selector
     *
     * @param Crawler $crawler
     * @param $filter
     *
     * @return Crawler
     */
    public static function removeByFilter(Crawler $crawler, $filter){
        $crawler->filter( $filter )->each( function ( Crawler $input ) {
            foreach ($input as $node) {
                $node->parentNode->removeChild($node);
            }
        });
        return $crawler;
    }
    
    /**
     * Remove descendant by xpath selector
     * @param Crawler $crawler
     * @param $filter
     *
     * @return Crawler
     */
    public static function removeByFilterXpath(Crawler $crawler, $filter){
        $crawler->filterXPath( $filter )->each( function ( Crawler $input ) {
            foreach ($input as $node) {
                $node->parentNode->removeChild($node);
            }
        });
        return $crawler;
    }
    
}