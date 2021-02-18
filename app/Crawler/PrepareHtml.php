<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-13
 * Time: 18:05
 */

namespace App\Crawler;


use App\Crawler\Browsers\BrowserManager;
use App\Libs\HtmlRepair;
use App\Libs\PhpUri;

class PrepareHtml {
    
    public static function fromUrl($url, $keep_js = false, $prerender = false, $keep_image = false){
        if($prerender){
            $base_html = BrowserManager::get( 'phantomjs')->getHtml( $url );
        }else{
            $base_html = BrowserManager::get( 'guzzle')->getHtml( $url );
        }
        $_html = new HtmlRepair( $base_html );
        if ( !$keep_js ) {
            $_html->removeJavascript();
            $_html->removeIframe();
        }
        if ( !$keep_image ) {
            $_html->removeImage();
        }
        
        $html = $_html->html();
        
        $base_url = PhpUri::parse( $url );
        $html = preg_replace_callback( '/(src=\'|src=\"|href=\'|href=\")([^\'\"]+)/', function ( $matches ) use ( $base_url ) {
            return $matches[1] . $base_url->join( $matches[2] );
        }, $html );
    
        $html = str_replace( "crossorigin=", "__crossorigin=", $html );
    
        // append your assets
        $custom_assets = "<link rel='stylesheet' href='" . asset( 'assets/css/site/inject.css') . "?v=" . config('crawler.version') . "' />";
        $custom_assets .= "<script>if(typeof $ != 'undefined'){var _$ = $;}else{var _$ = false;}</script>";
        $custom_assets .= "<script>if(typeof jQuery != 'undefined'){var _jQuery = jQuery.noConflict();}else{var _jQuery = false;}</script>";
//        $custom_assets .= "<script src='" . asset( 'assets/plugins/jquery.js' ) . "'></script>";
        $custom_assets .= "<script src='" . asset( 'packages/jquery-3.4.1.min.js') . "?v=" . config('crawler.version') . "'></script>";
        $custom_assets .= "<script>var ACjQuery = jQuery.noConflict();</script>";
        $custom_assets .= "<script src='" . asset( 'assets/js/site/inject.js') . "?v=" . config('crawler.version') . "'></script>";
        $custom_assets .= "<script>if(_jQuery)jQuery = _jQuery;</script>";
        $custom_assets .= "<script>if(_$)$ = _$;</script>";
//			$html = $sc->html();
        $html = str_replace( "</body>", $custom_assets . "</body>", $html );
//        $custom_head = "<base href=\"" . $url . "\" target=\"_blank\">";
//        $html = str_replace( "<head>", "<head>" . $custom_head, $html );
    
        return $html;
    }
}