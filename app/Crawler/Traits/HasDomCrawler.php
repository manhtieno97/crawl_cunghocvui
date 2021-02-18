<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 14:35
 */

namespace App\Crawler\Traits;


use App\Crawler\Selector\SiteSelectors;
use Illuminate\Support\Collection;
use Symfony\Component\DomCrawler\Crawler;
use Vuh\CliEcho\CliEcho;

trait HasDomCrawler {
    
    /** @var Crawler */
    protected $page;
    
    public function prepare( $html ) {
        $this->page = new Crawler();
        $this->page->addHtmlContent($html);
    }
    
    public function getHtml(){
        if(!$this->page){
            return null;
        }
        return $this->page->html();
    }
    
    /**
     * @param $selector
     * @param null $filter
     *
     * @return Crawler
     */
    protected function getElementBySelector($selector, $filter = null, $multiple = false){
        try{
            list( $selector_content, $selector_type) = $this->parseSelector( $selector );
            switch ($selector_type){
                case 'css':
                    $elements = $this->page->filter( $selector_content );
                    break;
                case 'xpath':
                    $elements = $this->page->filterXPath( $selector_content );
                    break;
                default:
                    throw new \Exception("Selector type " . $selector_type . " not supported");
            }
            $elements = $multiple ? $elements : $elements->first();
            if(!$elements){
                return [];
            }
            if($filter){
                if(method_exists( $this, 'filter' . ucfirst( $filter ) )){
                    $elements = $this->{'filter' . ucfirst( $filter )}($elements);
                    return $elements;
                }else{
                    throw new \Exception("Not support filter " . $filter);
                }
            }else{
                return $elements;
            }
        }catch (\Exception $exception){
            CliEcho::warningnl( "\t Error " . $selector . ":" . $filter . " :: " . $exception->getMessage());
        }
    }
    
    protected function parseSelector($selector){
        list( $selector_type, $selector_content) = explode( ": ", $selector, 2);
        return [$selector_content, $selector_type];
    }
    
    
    /**
     * @param Crawler $elements
     *
     * @return Collection collection of Crawler link object
     */
    protected function filterLink(Crawler $elements){
        $result = new Collection();
        if($elements->count() == 1){
            $elements = [$elements->getNode( 0)];
        }
        /** @var \DOMNode $element */
        foreach ($elements as $element){
            if(strtolower( $element->nodeName ) != 'a'){
                try{
                    $links = $this->page->filterXPath( $element->getNodePath() . "//a");
                    foreach ($links as $link){
                        $result->push( $link );
                    }
                }catch (\Exception $ex){}
            }else{
                $result->push( $element );
            }
        }
        return $result;
    }
    
    protected function filterTrimText($content){
        if($content instanceof Crawler){
            $text = $content->nodeName() == 'meta' ? $content->attr( 'content') : $content->text();
            return $this->filterTrimText( $text );
        }
        if($content instanceof \DOMNode){
            $text = $content->nodeName == 'meta' ? $this->safeGetNodeValue( $content, 'content') : $content->textContent;
            return $this->filterTrimText( $text );
        }
        return trim( (string)$content );// try to cast $content to string
    }
    
    protected function filterTrimKeywords($content){
        try{
            if ($content instanceof \DOMNode){
                $content = $this->getElementBySelector( 'xpath: ' . $content->getNodePath());
            }
            if($content instanceof Crawler){
                if($content->children()->count()){
                    $keywords = [];
                    /** @var \DOMNode $child */
                    foreach ($content->filterXPath( '//*') as $child){
                        if($child->firstChild instanceof \DOMText && $child->textContent){
                            $_keywords = $this->filterTrimText( $child->textContent );
                            if(empty( $_keywords )){
                                continue;
                            }
                            $_keywords = implode( ";", array_filter( explode( "\n", $_keywords)));
                            $_keywords = implode( ";", array_filter( explode( "--", $_keywords)));
                            $_keywords = implode( ";", array_filter( explode( "> ", $_keywords)));
                            $_keywords = implode( ";", array_filter( explode( ". ", $_keywords)));
                            $keywords[] = $_keywords;
                            dump($_keywords);
                        }
                    }
                    
                    return implode( ";", $keywords);
                }
            }
        }catch (\Exception $ex){
            CliEcho::warningnl( $ex->getMessage() );
        }
        return $this->filterTrimText( $content );
    }
    
    /**
     * @param \DOMNode $a
     *
     * @return array ['href', 'title', 'text']
     */
    protected function getDomNodeLinkInfo(\DOMNode $a){
    
        $href = '';
        $title = '';
        $text = '';
        
        try{
            if($a->nodeName == 'iframe'){
                $href = $this->safeGetNodeValue( $a, 'src');
            }else{
                $href = $this->safeGetNodeValue( $a, 'href');
                $title = $this->safeGetNodeValue( $a, 'title');
                $text = $this->filterTrimText( $a->textContent );
            }
        }catch (\Exception $ex){}
        
        if($href == '#' || !$href || $href == 'javascript:void(0);'){
            $onclick = $this->safeGetNodeValue( $a, 'onclick');
            $matches = [];
            $matched = preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $onclick, $matches);
            if($matched){
                $href = $matches[0];
            }
        }
        
        return compact( 'href', 'title', 'text');
        
    }
    
    protected function safeGetNodeValue(\DOMNode $node, $attribute){
        if($nodeItem = $node->attributes->getNamedItem($attribute)){
            return $nodeItem->nodeValue;
        }else{
            return null;
        }
    }
    
    /**
     * Xử lý lấy document title từ page, trường hợp này chỉ lấy 1 title và được gán data vào page, sau đó các tài liệu
     * lấy từ page này sẽ có thể lấy làm title của tài liệu
     * @param $step
     *
     * @return string|null
     */
    protected function tryToGetTitle($step){
        $child_selectors = $this->selector->getChildren( $step, [SiteSelectors::TYPE_GET_REMOTE_TITLE, SiteSelectors::TYPE_DOCUMENT_NAME]);
//        $child_selectors = $this->selector->getChildren( $step, SiteSelectors::TYPE_DOCUMENT_NAME);
        foreach ($child_selectors as $child_selector){
            $elements = $this->getElementBySelector( $child_selector['selector'], null, $child_selector['multiple']);
            if($child_selector['multiple'] == false){
                $elements = [$elements->getNode( 0)];
            }
            foreach ($elements as $element){
                $title = $this->filterTrimText($element->textContent);
                if($title){
                    return $title;
                }
            }
        }
        return null;
    }
    
    protected function tryToGetDownloadLinks($step){
        $links = [];
        $child_selectors = $this->selector->getChildren( $step, [SiteSelectors::TYPE_GET_LINK, SiteSelectors::TYPE_GET_LINK_WITH_NAME]);
        foreach ($child_selectors as $child_selector){
            $_links = [];
            $elements = $this->getElementBySelector( $child_selector['selector'], null, $child_selector['multiple']);
            if($child_selector['multiple'] == false){
                $elements = [$elements->getNode( 0)];
            }
            foreach ($elements as $element){
                if(!$element){
                    continue;
                }
                if($element->nodeName == 'a'){
                    $_links[] = $this->extractDownloadLinkInfo($element, $child_selector['id']);
                }else{
                    $link_elements = $this->getElementBySelector( $element->getNodePath() . "//a");
                    if($link_elements){
                        foreach ($link_elements as $element){
                            $_links[] = $this->extractDownloadLinkInfo($element, $child_selector['id']);
                        }
                    }
                }
            }
            // get title
            // keyword
            $keywords = $this->tryToGetKeywords($child_selector['id']);
            if($child_selector['type'] == SiteSelectors::TYPE_GET_LINK_WITH_NAME){
                foreach ($_links as $k => $v){
                    $_links[$k]['keywords'] = $keywords;
                    $_links[$k]['document_name'] = $_links[$k]['text'];
                }
            }else{
                // document name
                $document_names = $this->tryToGetDocumentName($child_selector['id']);
                if(is_array( $document_names ) && count($document_names) == count( $_links )){
                    foreach ($_links as $k => $v){
                        $_links[$k]['document_name'] = $document_names[$k];
                        $_links[$k]['keywords'] = $keywords;
                    }
                }elseif (!is_array( $document_names )){
                    foreach ($_links as $k => $v){
                        $_links[$k]['document_name'] = $document_names;
                        $_links[$k]['keywords'] = $keywords;
                    }
                }else{
                    foreach ($_links as $k => $v){
                        $_links[$k]['keywords'] = $keywords;
                    }
                }
            }
            $links = array_merge( $links, $_links );
        }
        return $links;
    }
    
    protected function tryToGetData($step, $include_basic = false){
        $data = [];
        if($include_basic){
            // title
            $data['page_title'] = $this->getElementBySelector( 'css: title', 'trimText');
            // description
            $data['page_description'] = $this->getElementBySelector( 'css: meta[name=description]', 'trimText');
        }
        // keyword
        $data['keywords'] = $this->tryToGetKeywords($step);
        // document name
        $data['document_name'] = $this->tryToGetDocumentName($step);
        if(!is_array( $data['document_name'] )){
            $data['document_name'] = [$data['document_name']];
        }
        return $data;
    }
    
    protected function extractDownloadLinkInfo(\DOMNode $url, $step){
        $link_info = $this->getDomNodeLinkInfo( $url );
        return $link_info;
    }
    
    /**
     * Xử lý lấy keywords từ page, trường hợp này keywords được gán data vào page, sau đó các tài liệu
     * lấy từ page này sẽ có thể lấy làm keywords của tài liệu
     *
     * @param $step
     *
     * @return string|null
     */
    protected function tryToGetKeywords($step){
        $keywords = [];
        $child_selectors = $this->selector->getChildren( $step, [SiteSelectors::TYPE_KEYWORDS]);
        foreach ($child_selectors as $child_selector){
            $keywords[] = $this->getElementBySelector( $child_selector['selector'], 'trimKeywords', $child_selector['multiple']);
        }
        return implode( ",", array_filter( $keywords ) );
    }
    
    /**
     * @param $step
     *
     * @return array|string
     */
    protected function tryToGetDocumentName($step) {
        $titles = [];
        $child_selectors = $this->selector->getChildren( $step, [SiteSelectors::TYPE_DOCUMENT_NAME, SiteSelectors::TYPE_GET_REMOTE_TITLE]);
        $is_multiple = count($child_selectors) > 1;
        foreach ($child_selectors as $child_selector){
            $is_multiple = $is_multiple || $child_selector['multiple'];
            $elements = $this->getElementBySelector( $child_selector['selector'], null, $child_selector['multiple']);
            if($child_selector['multiple'] == false && !empty( $elements )){
                $elements = [$elements->getNode( 0)];
            }
            foreach ($elements as $element){
                if(!$element){
                    continue;
                }
                $titles[] = $this->filterTrimText( $element->textContent );
            }
        }
        if(!$is_multiple){
            return count($titles) ? $titles[0] : '';
        }
        return $titles;
    }
    
}