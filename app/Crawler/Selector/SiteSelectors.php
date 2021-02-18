<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-10-10
 * Time: 14:10
 */

namespace App\Crawler\Selector;


use App\Libs\PhpUri;
use Illuminate\Support\Arr;

class SiteSelectors {
    
    protected $site_info = [];
    protected $siteId;
    protected $internal_link_filter;
    public $attempts;
    protected $start_url;
    
    public $path = [];
    public $type_path = [];
    public $loop_steps = [];
    public $key_steps = [];
    
    const TYPE_ROOT = 'root';
    const TYPE_LINK = 'link';
    const TYPE_DATA = 'data';
    const TYPE_KEYWORDS = 'keywords';
    const TYPE_GET_LINK = 'get_link';
    const TYPE_GET_LINK_WITH_NAME = 'get_link_with_name';
    const TYPE_GET_REMOTE_TITLE = 'get_remote_title';
    const TYPE_DOCUMENT_NAME = 'name';
    
    
    /**
     * SiteMap constructor.
     */
    public function __construct( $siteId, $startUrl, $selectors, $attempts = 0 ) {
        $this->siteId = $siteId;
        $this->attempts = $attempts;
        $this->site_info['root'] = [
            'id' => 'root',
            'url' => $startUrl,
            'type' => 'root',
            'parent_selectors' => [],
        ];
        $this->internal_link_filter = false;
        $this->start_url = PhpUri::parse( $startUrl );
        foreach ($selectors as $selector){
            if(!isset($this->site_info[$selector['id']])){
                $this->site_info[$selector['id']] = $selector;
            }else{
                $this->site_info[$selector['id']] = array_merge($this->site_info[$selector['id']], $selector);
            }
            
            foreach ($selector['parent_selectors'] as $parent_id){
                if(isset($this->site_info[$parent_id])){
                    if(isset($this->site_info[$parent_id]['children'])){
                        $this->site_info[$parent_id]['children'][] = $selector['id'];
                    }else{
                        $this->site_info[$parent_id]['children'] = [$selector['id']];
                    }
                }else{
                    $this->site_info[$parent_id] = ['children' => [$selector['id']]];
                }
            }
        }
        
        // chuẩn hóa
        foreach ($this->site_info as $k => $node){
            if(!isset($node['id'])){
                unset($this->site_info[$k]);
            }
        }

//		dd($this->site_info);
    }
    
    public function getStep($id){
        return Arr::get($this->site_info, $id, []);
    }
    
    public function loopNodes(){
        $nodes = [];
        foreach ($this->site_info as $node){
            $is_loop = in_array($node['id'], $node['parent_selectors']);
            if($is_loop){
                $nodes[] = $node;
            }
        }
        return $nodes;
    }
    
    public function nodeNameList($except = [], array $types = ['*']){
        $nodes = Arr::except($this->site_info, $except);
        if(in_array('*', $types)){
            return array_keys($nodes);
        }
        $node_names = [];
        foreach ($nodes as $node){
            if(in_array($node['type'], $types)){
                $node_names[] = $node['id'];
            }
        }
        return $node_names;
    }
    
    /**
     * @param $id
     * @param null $type
     *
     * @return mixed
     */
    public function getParent($id, $type = null){
        if(!$type){
            $type = is_array( $type ) ? $type : [$type];
        }
        $parent = Arr::get($this->site_info, $id . ".parent_selectors", []);
        $selectors = [];
        foreach ($parent as $p){
            $parentStep = $this->getStep($p);
            if($type == null || in_array( $parentStep['type'], $type)){
                $selectors[] = $parentStep;
            }
        }
        return $selectors;
    }
    
    /**
     * @param $id
     * @param null|string|array $type
     *
     * @return array
     */
    public function getChildren($id, $type = null){
        $type = is_string( $type ) ? [$type] : $type;
        $children = Arr::get($this->site_info, $id . ".children", []);
        $selectors = [];
        foreach ($children as $child){
            $childStep = $this->getStep($child);
            if($type == null){
                $selectors[] = $childStep;
            }elseif (is_array($type) && in_array($childStep['type'], $type)){
                $selectors[] = $childStep;
            }elseif (!is_array($type) && $childStep['type'] == $type){
                $selectors[] = $childStep;
            }
        }
        return $selectors;
    }
    
    /**
     * @return mixed
     */
    public function getSiteId() {
        return $this->siteId;
    }
    
    /**
     * @return int
     */
    public function getDelay() {
        return $this->delay;
    }
    
    /**
     * @return int
     */
    public function getMaxStep() {
        return $this->max_step;
    }
    
    public function getStartUrl() {
        return $this->site_info['root']['url'];
    }
    
    /**
     * @return mixed
     */
    public function getInternalLinkFilter()
    {
        return $this->internal_link_filter;
    }
    
    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }
    
    public function initPath() {
        if(!empty($this->path)) return;
        
        $this->getPath();
        
        if($this->err != SiteMap::$NONE_ERR) return;
//        chuẩn hóa path
        foreach ($this->path as $key => $path) {
            $this->path[$key] = array_unique($path);
        }
        
        $this->getTypePath();
    }
    
    public function getPath() {
        $get_link_nodes = $this->getGetlinkNode();
        
        foreach ($get_link_nodes as $node) {
            //duyệt cây con từ root đến get link down, đồng thời xác định các loop step
            $this->backtrack($node, $node, 0);
        }
    }
    
    public function getTypePath(){
        $get_link_nodes = $this->getGetlinkNode();
        
        foreach ($get_link_nodes as $node) {
            if(!isset($this->loop_steps[$node['id']])) {
                $this->type_path[$node['id']]="no_loop";
            }
            else {                          //có loop
                $loop_step_id = $this->loop_steps[$node['id']];
                foreach($this->getChildrenInPath($loop_step_id, $node['id']) as $child_id) {
                    $child = $this->getStep($child_id);
                    
                    if($child['type']=='get_link') {
                        $parent_loop_step_id = $this->getParentInPath($loop_step_id, $child_id)[0];
                        $parent_loop_step = $this->getStep($parent_loop_step_id);
                        if(count($parent_loop_step['children']) == 1) $this->type_path[$node['id']]="loop_type_3"; //http://www.city.ito.shizuoka.jp/html/shiseijouhou.html
                        else $this->type_path[$node['id']]="loop_type_1"; //kiểu bài tutorial
                    }
                }
                
                if(!isset($this->type_path[$node['id']])) $this->type_path[$node['id']]="loop_type_2";                                    //kiểu các trang báo
            }
            $this->key_steps[$node['id']] = $this->getKeyPathRun($node['id']);
        }
    }
    
    public function backtrack($node, $leaf, $flag) {
        $this->path[$leaf['id']][]=$node['id'];
        
        if($flag > 27) {
            /** @todo quên mất ý nghĩa :( */
            throw new \Exception("Loop error");
        }
        
        if($node['id'] == 'root') return ;
        
        foreach ($node['parent_selectors'] as $parent_id) {
            $parent = $this->getStep($parent_id);
            if($parent['id'] == $node['id']){
                $this->loop_steps[$leaf['id']]=$node['id'];
                continue;
            }
            
            else  $this->backtrack($parent, $leaf, $flag+1);
        }
    }
    
    public function getGetlinkNode() {
        $nodes = [];
        foreach ($this->site_info as $node){
            $is_getlink = $node['type']=='get_link';
            if($is_getlink){
                $nodes[] = $node;
            }
        }
        return $nodes;
    }
    
    public function getKeyPathRun($node_id){
        $type_path = $this->type_path[$node_id];
        
        if($type_path=='no_loop') {
            $back_step = 0;
            $child_id = $node_id;
            
            do {
                $parents = $this->getParentInPath($child_id, $node_id);
                if(count($parents) == 1) $parent_id = $parents[0];
                else {
                    $parent1 = $parents[0];
                    $parent2 = $parents[1];
                    
                    if(in_array($parent1, $this->getParentInPath($parent2, $node_id))) $parent_id = $parent1;
                    else $parent_id = $parent2;
                }
                
                $child_id = $parent_id;
                $back_step++;
            } while ($child_id != 'root' && $back_step < 2);
            
            return $child_id;
        }
        if($type_path=='loop_type_1') {
            $loop_step_id = $this->loop_steps[$node_id];
            $loop_step_parent_id = $this->getParentInPath($loop_step_id, $node_id)[0];
            $loop_step_granparent_id = $this->getParentInPath($loop_step_parent_id, $node_id);
            if($loop_step_granparent_id == NULL) return "root";
            else return $loop_step_granparent_id[0];
        }
        if($type_path=='loop_type_2'||$type_path=='loop_type_3'){
            $loop_step_id=$this->loop_steps[$node_id];
            $loop_step_parent_id = $this->getParentInPath($loop_step_id, $node_id)[0];
            
            return $loop_step_parent_id;
        }
        return 0;
    }
    
    public function getChildrenInPath($parentId ,$id) {
        $parent = $this->getStep($parentId);
        $children = $parent['children'];
        $path = $this->path[$id];
        $selectors = [];
        foreach ($children as $child) {
            if(in_array($child, $path)) $selectors[]=$child;
        }
        
        return $selectors;
    }
    
    public function getParentInPath($childId, $id) {
        $child = $this->getStep($childId);
        $parents = $child['parent_selectors'];
        $path = $this->path[$id];
        $selectors = [];
        foreach ($parents as $parent) {
            if(in_array($parent, $path)) $selectors[]=$parent;
        }
        
        return $selectors;
    }
    
    public function assertFullUrl($url){
        if(preg_match( "/^(https?:)?\/\//", $url)){
            return $url;
        }else{
            return $this->start_url->join( $url );
        }
    }
    
    public function isMatchStartDomain($url){
        $url = $this->assertFullUrl( $url );
        return PhpUri::parse( $url )->getUniDomain() == $this->start_url->getUniDomain();
    }
    
}