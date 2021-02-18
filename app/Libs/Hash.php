<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-24
 * Time: 16:21
 */

namespace App\Libs;


use League\Uri\Components\Fragment;
use League\Uri\Components\Query;
use League\Uri\Uri;

class Hash {
    
    /**
     * Tạo hash string cho url
     *
     * @param $url
     *
     * @return string
     */
    public static function hashUrl($url){
        $uri = Uri::createFromString($url);
        $url = $uri->withQuery( Query::createFromUri( $uri )->sort() )->__toString();
        return hash( 'sha256', $url);
    }
}