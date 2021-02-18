<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 5/30/17
 * Time: 10:11
 */

namespace App\Crawler\LinkProcessor;


use App\Libs\PhpUri;

abstract class LinkFilter {
	protected $patterns = [];
	protected $contains = [];
	protected $starts = [];
	protected $ends = [];
	protected $type = "";
	protected $prefix = "";
	protected $base = "";
	
	/**
	 * LinkFilter constructor.
	 *
	 * @param string $prefix
	 *
	 * @param string $base
	 *
	 * @throws \Exception
	 */
	public function __construct($prefix = '', $base = '') {
		if(!$this->type){
			throw new \Exception("Filter chua co ten");
		}
		$this->prefix = $prefix;
		if($base){
			$this->base = PhpUri::parse($base);
		}
	}
	
	public function check($link, $return_type = false){
		$link = PhpUri::urlEncode($link);
		$link = $this->standardLink($link);
		$return = false;
		foreach ($this->patterns as $pattern){
			if(preg_match($pattern, $link)){
				$return = true;
			}
		}
		if(!$return){
			foreach ($this->contains as $contain){
				if(strpos($link, $contain) !== false){
					$return = true;
				}
			}
		}
		if(!$return){
			foreach ($this->starts as $start){
				if(strpos($link, $start) === 0){
					$return = true;
				}
			}
		}
		if(!$return){
			foreach ($this->ends as $end){
				$right_pos = strlen($link) - strlen($end) - 1;
				if(strpos($link, $start) === $right_pos){
					$return = true;
				}
			}
		}
		if($return_type){
			return [
				'type' => $this->type,
				'check' => $return
			];
		}else{
			return $return;
		}
	}
	
	protected function standardLink($link){
		if($this->prefix){
			$link = strpos($link,$this->prefix) == 0 ?  substr($link, strlen($this->prefix)) : $link;
		}
		if ($this->base && strpos($link, "//") === false){
			$link = $this->base->join($link);
		}
		return $link;
	}
	
	public function getType(){
		if($this->type){
			return $this->type;
		}else{
			throw new \Exception("Filter chua co ten");
		}
	}
	
	public function getRules(){
		return [
			'patterns' => $this->patterns,
			'contains' => $this->contains,
			'starts' => $this->starts,
			'ends' => $this->ends
		];
	}
	public function setRules(array $rules){
		foreach ( [ 'patterns', 'contains', 'starts', 'ends' ] as $rule_name ) {
			$this->$rule_name = array_get($rules, $rule_name, []);
		}
		return $this;
	}
	
	public function downloadToFile($path){
		
	}
	
	/**
	 * @param $link
	 *
	 * @return string
	 * @deprecated use PhpUri::urlEncode instead
	 */
	public static function urlEncode($link){
		$is_encoded = preg_match('~%[0-9A-F]{2}~i', $link);
		if($is_encoded){
			return $link;
		}
		$matches = [];
		$is_match = preg_match('/^https?\:\/\/(\w+\.)+\w+\/?/', $link, $matches);
		if($is_match){
			$base = $matches[0];
			$remain = str_replace($base, '', $link);
		}else{
			$base = "";
			$remain = $link;
		}
		$remain = str_replace("/", '__slash__', $remain);
		$remain = str_replace("?", '__question_mark__', $remain);
		$remain = str_replace("&", '__and_mark__', $remain);
		$remain = urlencode($remain);
		$remain = str_replace("__slash__", '/', $remain);
		$remain = str_replace("__question_mark__", '?', $remain);
		$remain = str_replace("__and_mark__", '&', $remain);
		return $base . $remain;
	}
}