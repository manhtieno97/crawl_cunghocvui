<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/10/17
 * Time: 01:22
 */

namespace App\Crawler\LinkProcessor;


class OneDrive extends LinkFilter {
	
	protected $type = 'onedrive';
	
	protected $contains = [
		'//onedrive.live.com/?',
		'//1drv.ms/'
	];
	
}