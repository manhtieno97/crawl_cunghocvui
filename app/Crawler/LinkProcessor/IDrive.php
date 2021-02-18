<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 7/10/17
 * Time: 01:24
 */

namespace App\Crawler\LinkProcessor;


class IDrive extends LinkFilter {
	
	protected $type = 'idrive';
	
	protected $contains = [
		'idrive.com/idrive/sh'
	];
	
}