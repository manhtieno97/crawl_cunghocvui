<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-11-16
 * Time: 23:13
 */

namespace App\Crawler;


use Symfony\Component\DomCrawler\Crawler;

class MediaHelper {
    
    /**
     *
     * Detect và download ảnh trong phần nội dung html
     *
     * @param Crawler $crawler
     *
     * @return array
     */
    public static function download_images_from_crawler(Crawler $crawler){
        $images = $crawler->filter('img');
        $downloaded = [];
        /** @var \DOMElement $image */
        foreach ($images as $image){
            try{
                $src = $image->getAttribute( 'src');
                $downloaded[] = [
                    'url' => $src,
                    'data-url' => (string)(app('image')->make($src)->encode('data-url')),
                ];
            }catch (\Exception $ex){
            
            }
        }
        return $downloaded;
    }
    
    public static function src_to_base64_image($content, $images){
        foreach ($images as $image){
            $content = str_replace( $image['url'], $image['data-url'], $content);
        }
        return $content;
    }
    
    public static function cleanHtml($html, $attributes = ['class', 'data\-[a-z\-]+', 'style']){
        foreach ($attributes as $attribute){
            $html = preg_replace( "/ " . $attribute . "=\"([^\"]*)\"/ui", "", $html);
            $html = preg_replace( "/ " . $attribute . "=\'([^\']*)\'/ui", "", $html);
        }
        return trim( $html );
    }
    
}