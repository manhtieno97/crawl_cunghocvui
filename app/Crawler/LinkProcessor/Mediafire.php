<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/12/17
 * Time: 10:24
 */

namespace App\Crawler\LinkProcessor;


class Mediafire extends LinkFilter {
	
	protected $type = 'mediafire';
	
	protected $starts = [
		'https://www.mediafire.com',
	];
	
	protected $contains = [
		'mediafire.com/',
	];
	
}