<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 01:54
 */

namespace App\Crawler\Browsers;

use Spatie\Browsershot\Browsershot;

class Chrome implements BrowserInterface {
    
    protected $browserShot;
    
    /**
     * Chrome constructor.
     */
    public function __construct() {
        
        $this->browserShot = new Browsershot();
        
    }
    
    public function getHtml( $uri ) {
        return $this->browserShot->setUrl( $uri )->bodyHtml();
    }
}