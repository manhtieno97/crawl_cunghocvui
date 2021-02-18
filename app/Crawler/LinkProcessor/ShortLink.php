<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 5/30/17
 * Time: 10:10
 */

namespace App\Crawler\LinkProcessor;


use App\Browser\Crawler;
use App\Libs\PhpUri;
use GuzzleHttp\Client;

class ShortLink extends LinkFilter {
	
	protected $type = "shortlink";
	
	protected $contains = [
		'//goo.gl/',
		'//bitly.com'
	];
	
	protected $filters = [
		'adfly' => [
			'contains' => [
				'//adf.ly/',
			]
		],
		'3fcbdesign' => [
			'contains' => [
				'3fcbdesign.tk/',
				'injectionmolding.tk/',
				'sobatdikbud.com/',
			]
		]
	];
	
	public function check( $link , $not_in_use = false) {
		$link = PhpUri::urlEncode($link);
		if(parent::check( $link )){
			$realLink = $this->getRealLink($link);
		}else{
			return $this->getFromOtherAdsShort($link);
		}
		return $realLink;
	}
	
	
	public function getRealLink($link){
		$client = Crawler::initClient();
		try{
			$response = $client->head($link, ['allow_redirects' => false]);
			if($response->getStatusCode() == 301){
				$realLink = $response->getHeader('Location');
				if(empty($realLink)){
					$realLink = false;
				}else{
					$realLink = $realLink[0];
				}
			}else{
//				$response = $client->get($link);
//				$realLink = $this->extractLinkFromContent($response);
				$realLink = null;
			}
			return $realLink;
		}catch (\Exception $ex){
			\Log::alert("Request header error " . $ex->getMessage());
		}
		return false;
	}
	
	public function getFromOtherAdsShort($link){
		foreach ( $this->filters as $k => $filter_config ) {
			$filter = new CustomLinkFilter();
			if($filter->setRules($filter_config)->check($link)){
				return call_user_func([$this, "get" . ucfirst($k) . "Link"], $link);
			}
		}
		return false;
	}
	
	public function getAdflyLink($link){
		$client = Crawler::initClient();
		try{
			$response = $response = $client->get($link);
			$html = $response->getBody()->getContents();
			$matches = [];
			$matched = preg_match('/var\s+ysmm\s+=\s*[\'\"](.*)[\'\"]\;/', $html, $matches);
			if(!$matched){return false;}
			$encoded = $matches[1];
			$left = '';
			$right = '';
			for ($i = 0; $i<strlen($encoded); $i += 2){
				$left .= $encoded[$i];
				$right = $encoded[$i+1] . $right;
			}
			$decoded = base64_decode($left . $right);
			if($pos = strpos($decoded, "go.php?u=")){
				$decoded = substr($decoded, $pos + 9);
			}else{
				$decoded = substr($decoded, 2);
			}
			return $decoded;
		}catch (\Exception $ex){
			
		}
		return false;
	}
	
	public function get3fcbdesignLink($link){
		$hash = preg_replace('/^.*url=/', '', $link);
		$real_link = base64_decode($hash);
		if(strpos($real_link, '//')){
			return $real_link;
		}
		return false;
	}
	
}