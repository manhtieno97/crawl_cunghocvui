<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/12/17
 * Time: 10:25
 */

namespace App\Crawler\LinkProcessor;


class GoogleDrive extends LinkFilter {
	
	protected $type = 'google_drive';
	
	protected $starts = [
		'https://drive.google.com',
		'http://drive.google.com',
		'https://docs.google.com',
		'http://docs.google.com',
	];
	
	protected $patterns = [
//		'/sites.google.com\/.*attredirects\=0\&d\=1/'
	];
}