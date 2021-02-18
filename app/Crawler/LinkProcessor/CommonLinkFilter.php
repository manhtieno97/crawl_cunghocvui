<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/22/17
 * Time: 17:07
 */

namespace App\Crawler\LinkProcessor;


class CommonLinkFilter extends CustomLinkFilter {
	
	protected $type = 'common';
	
	/**
	 * CommonLinkFilter constructor.
	 */
	public function __construct() {
		$this->merge(new Mediafire());
		$this->merge(new GoogleDrive());
		$this->merge(new BoxCom());
		$this->merge(new DropBox());
		$this->merge(new OneDrive());
	}
}