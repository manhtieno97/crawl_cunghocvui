<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/29/17
 * Time: 00:32
 */

namespace App\Crawler\LinkProcessor;


class BoxCom extends LinkFilter {
	
	protected $type = 'boxcom';
	
	protected $contains = [
		'app.box.com',
		'cloud.box.com',
		'.box.com/s',
		'//box.com/s'
	];
	
	
}