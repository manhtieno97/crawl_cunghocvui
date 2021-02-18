<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 01:52
 */

namespace App\Crawler\Browsers;

interface BrowserInterface {
    
    public function getHtml($url);
    
}