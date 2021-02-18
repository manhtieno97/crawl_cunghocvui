<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-13
 * Time: 22:05
 */

namespace App\Libs;


use Symfony\Component\DomCrawler\Crawler;

class HtmlRepair {
    
    private $sc;
    
    /**
     * HtmlRepair constructor.
     */
    public function __construct($html) {
        $this->sc = new Crawler();
        $this->sc->addHtmlContent($html);
    }
    
    public function removeJavascript(){
        $this->sc->filter('script')->each(function ( Crawler $crawler){
            foreach ($crawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });
    }
    
    public function removeIframe(){
        $this->sc->filter('iframe')->each(function ( Crawler $crawler){
            /** @var \DOMElement $node */
            foreach ($crawler as $node) {
                $new_node = $node->parentNode->ownerDocument->createElement("a");
                $new_node->setAttribute('class', 'ac_iframe');
                $src = $node->getAttribute('src');
                $new_node->setAttribute('href', $src);
                $new_node->textContent = "IFRAME :: " . $src;
                $node->parentNode->replaceChild($new_node, $node);
            }
        });
    }
    public function removeImage(){
        $this->sc->filter('img')->each(function ( Crawler $crawler){
            /** @var \DOMElement $node */
            foreach ($crawler as $node) {
                $new_node = $node->parentNode->ownerDocument->createElement("a");
                $new_node->setAttribute('class', 'ac_iframe');
                $src = $node->getAttribute('src');
                $new_node->setAttribute('href', $src);
                $new_node->setAttribute('title', str_limit($src, 60));
                $new_node->textContent = "IMG";
                $node->parentNode->replaceChild($new_node, $node);
            }
        });
    }
    
    public function html(){
        return "<!DOCTYPE html>\n<html>" . $this->sc->html() . "</html>";
    }
    
    public function __toString() {
        return $this->html();
    }
}