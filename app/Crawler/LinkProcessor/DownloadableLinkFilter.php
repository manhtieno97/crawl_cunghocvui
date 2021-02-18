<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/22/17
 * Time: 19:39
 */

namespace App\Crawler\LinkProcessor;


use App\Libs\Mime\UrlFastMime;
use App\Libs\PhpUri;
use GuzzleHttp\Client;

class DownloadableLinkFilter extends LinkFilter {
	
	protected $type = "downloadable";
	
	protected $mimetypes = [
		"application/msword",
		
		"application/vnd.openxmlformats-officedocument.wordprocessingml.document",
		"application/vnd.openxmlformats-officedocument.wordprocessingml.template",
		"application/vnd.ms-word.document.macroEnabled.12",
		
		"application/vnd.ms-excel",
		
		"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
		"application/vnd.openxmlformats-officedocument.spreadsheetml.template",
		"application/vnd.ms-excel.sheet.macroEnabled.12",
		"application/vnd.ms-excel.template.macroEnabled.12",
		"application/vnd.ms-excel.addin.macroEnabled.12",
		"application/vnd.ms-excel.sheet.binary.macroEnabled.12",
		
		"application/vnd.ms-powerpoint",
		
		"application/vnd.openxmlformats-officedocument.presentationml.presentation",
		"application/vnd.openxmlformats-officedocument.presentationml.template",
		"application/vnd.openxmlformats-officedocument.presentationml.slideshow",
		"application/vnd.ms-powerpoint.addin.macroEnabled.12",
		"application/vnd.ms-powerpoint.presentation.macroEnabled.12",
		"application/vnd.ms-powerpoint.template.macroEnabled.12",
		"application/vnd.ms-powerpoint.slideshow.macroEnabled.12",
		
		"application/vnd.oasis.opendocument.text",
		"application/vnd.oasis.opendocument.text-template",
		"application/vnd.oasis.opendocument.text-web",
		"application/vnd.oasis.opendocument.text-master",
		"application/vnd.oasis.opendocument.graphics",
		"application/vnd.oasis.opendocument.graphics-template",
		"application/vnd.oasis.opendocument.presentation",
		"application/vnd.oasis.opendocument.presentation-template",
		"application/vnd.oasis.opendocument.spreadsheet",
		"application/vnd.oasis.opendocument.spreadsheet-template",
		"application/vnd.oasis.opendocument.chart",
		"application/vnd.oasis.opendocument.formula",
		"application/vnd.oasis.opendocument.database",
		"application/vnd.oasis.opendocument.image",
		"application/vnd.openofficeorg.extension",
		
		"application/pdf",
		
		//archive files
		"application/x-7z-compressed",
		"application/x-rar-compressed",
		"application/zip",
		"application/x-zip",
		"application/rar",
		
		// stream
		"application/octet-stream",
	
	];
	
	public function check( $link , $return_type = false) {
		$link = PhpUri::urlEncode($link);
		$link = $this->standardLink($link);
		
		if($result = HeaderCheckedFilter::getResult( $link)){// try to get from cache
            $return = false;
        }elseif ( $this->isDownloadable( $link ) ) {// check online
			$return = true;
		}else{
			$return = false;
		}
		
		if(!$return){
			HeaderCheckedFilter::addLink($link, 'no');
		}
		
		if($return_type && $return){
			return [
				'type' => $this->type,
				'check' => $return
			];
		}else{
			return $return;
		}
	}
	
	public function isDownloadable( $link ) {
        try {
            
            $mime_type = (new UrlFastMime())->getMime( $link );
            if ($mime_type && in_array( $mime_type, $this->mimetypes ) ) {
                return true;
            }
            
            return false;
        } catch ( \Exception $ex ) {
            echo "\n" . $link . " error " . $ex->getMessage();
            return false;
        }
	}
	
}