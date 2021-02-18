<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 17:29
 */

namespace App\Crawler\Browsers;


use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Str;

class WebDriver implements BrowserInterface {
    
    protected $drivers;
    protected $host;
    protected $desired;
    
    /**
     * WebDriver constructor.
     *
     * @param $driver
     */
    public function __construct( $host = 'http://localhost:4444/wd/hub/', $desired = 'chrome' ) {
        $this->driver = RemoteWebDriver::create($host, DesiredCapabilities::{$desired}());
        $this->host = $host;
        $this->desired = $desired;
    }
    
    
    public function getHtml( $url ) {
        $driver = RemoteWebDriver::create($this->host, DesiredCapabilities::{$this->desired}());
        try{
            $html = $driver->get($url)->getPageSource();
            dump(Str::limit( $html, 100));
            return $html;
        }catch (\Exception $ex){
        
        }finally{
            $driver->quit();
        }
    }
    
    public function __destruct() {
        try{
            $this->driver->quit();
        }catch (\Exception $ex){
        
        }
    }
}