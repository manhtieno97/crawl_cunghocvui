<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 02:26
 */

namespace App\Crawler\Browsers;


use App\Crawler\Browsers\Phantomjs\RenderWithJs;

class PhantomJsLocal implements BrowserInterface {
    
    public function getHtml( $url ) {
        try{
            $response = RenderWithJs::render( $url );
            return $response['html'];
        }catch (\Exception $ex){
            dump("Render error: " . $ex->getMessage());
//            \Log::error( "Render error: " . $ex->getMessage() );
            return null;
        }
    }
    
}