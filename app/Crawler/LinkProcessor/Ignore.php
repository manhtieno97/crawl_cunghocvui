<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 6/29/17
 * Time: 01:44
 */

namespace App\Crawler\LinkProcessor;


class Ignore extends LinkFilter {
	
	protected $type = 'ignore';


	public function __construct($prefix = '', $base = '')
    {
        parent::__construct($prefix, $base);
	
	    $ignoreFilter = config( 'crawler.link_filter.ignore_filter', [] );
	
	    $this->patterns = array_get( $ignoreFilter, 'patterns', [] );
	    $this->contains = array_get( $ignoreFilter, 'contains', [] );
	    $this->starts   = array_get( $ignoreFilter, 'starts', [] );
	    $this->ends     = array_get( $ignoreFilter, 'ends', [] );
    }

    public function addRules(array $rules)
    {
        foreach ( [ 'patterns', 'contains', 'starts', 'ends' ] as $rule_name ) {
            if(array_get($rules , $rule_name)){
                array_push($this->$rule_name ,array_get($rules, $rule_name, []));
            }
        }

        return $this;
    }

}