<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/12/17
 * Time: 10:30
 */

namespace App\Crawler\LinkProcessor;


class WordpressCategory extends LinkFilter {
	
	protected $type = 'wordpress_category';
	
	protected $patterns = [
		'/category/'
	];
	
	/**
	 * WordpressCategory constructor.
	 */
	public function __construct() {
	}
	
	
}