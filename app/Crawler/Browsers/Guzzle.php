<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-13
 * Time: 18:35
 */

namespace App\Crawler\Browsers;


use GuzzleHttp\Client;

class Guzzle implements BrowserInterface {
    
    protected $client;
    
    /**
     * Guzzle constructor.
     *
     * @param $client
     */
    public function __construct( ?Client $client = null ) {
        if($client){
            $this->client = $client;
        }else{
            $this->client = new Client(config('crawler.browsers.guzzle'));
        }
    }
    
    
    public function getHtml( $url ) {
        $reponse = $this->client->get( $url );
        return $reponse->getBody()->getContents();
    }
    
}