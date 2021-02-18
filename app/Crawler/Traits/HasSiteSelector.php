<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 14:33
 */

namespace App\Crawler\Traits;


use App\Crawler\Selector\SiteSelectors;

trait HasSiteSelector {
    
    /** @var SiteSelectors */
    protected $selector;
    
    /**
     * @return SiteSelectors
     */
    public function getSelector(): SiteSelectors {
        return $this->selector;
    }
    
    /**
     * @param SiteSelectors $selector
     */
    public function setSelector( SiteSelectors $selector ): void {
        $this->selector = $selector;
    }


}