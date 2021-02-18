<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/10/17
 * Time: 01:14
 */

namespace App\Crawler\LinkProcessor;


class DropBox extends LinkFilter {
	
	protected $type = 'dropbox';
	
	protected $contains = [
		'.dropbox.com/',
		'//dropbox.com/',
	];
	
	
}