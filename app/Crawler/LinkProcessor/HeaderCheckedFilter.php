<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/11/17
 * Time: 15:15
 */

namespace App\Crawler\LinkProcessor;


use App\Libs\PhpUri;

class HeaderCheckedFilter extends LinkFilter {
	
	protected $type = 'header_checked';
	
	public function check( $link, $return_type = false ) {
		return parent::check( $link, $return_type ); // TODO: Change the autogenerated stub
	}
	
	public static function addLink($link, $result = 'no'){
		\Cache::add(self::makeLinkKey($link), $result,60);
	}
	
	public static function getResult($link){
		$header = \Cache::get(self::makeLinkKey($link), null);
		return $header;
	}
	
	public static function makeLinkKey($link){
		$link = PhpUri::parse($link)->to_str();
		return md5($link);
	}
	
	
}