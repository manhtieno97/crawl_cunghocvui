<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-12
 * Time: 16:57
 */

namespace App\Crawler\Browsers;

class SplashRemote implements BrowserInterface {
    
    protected $client;
    
    /**
     * SplashRemote constructor.
     *
     * @param $client
     *
     * @throws \Exception
     */
    public function __construct( $host = 'http://localhost:8050' ) {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $host,
        ]);
        $ping_res = $this->ping();
        if($ping_res['status'] != "ok"){
            throw new \Exception(json_encode( $ping_res ));
        }
    }
    
    public function getHtml( $url ) {
        $response = $this->client->get( 'render.html', [
           'query' => [
               'url' => $url
           ]
        ]);
        
        return $response->getBody()->getContents();
    }
    
    public function ping(){
        try{
            $response = $this->client->get( '_ping');
            return \GuzzleHttp\json_decode( $response->getBody()->getContents(), true );
        }catch (\Exception $ex){
            return [
                'status' => 'not_ok',
                'message' => $ex->getMessage(),
            ];
        }
    }
    
    public function debug(){
        try{
            $response = $this->client->get( '_debug');
            return \GuzzleHttp\json_decode( $response->getBody()->getContents(), true );
        }catch (\Exception $ex){
            return [
                'status' => 'not_ok',
                'message' => $ex->getMessage(),
            ];
        }
    }
    
    public function clearCache(){
        try{
            $response = $this->client->post( '_gc');
            return \GuzzleHttp\json_decode( $response->getBody()->getContents(), true );
        }catch (\Exception $ex){
            return [
                'status' => 'not_ok',
                'message' => $ex->getMessage(),
            ];
        }
    }
    
    
}