<?php
namespace addons\superlinks;

use app\common\controller\Addon;
use Think\Db;
use Think\Config;
/**
 * 合作单位插件
 * @author 苏南
 */
class superlinks extends Addon
{
    public $info = array(
        'name' => 'superlinks',
        'title' => '友情链接',
        'description' => '友情链接',
        'status' => 1,
        'author' => '苏南 newsn.net',
        'version' => '0.1'
    );
   
    public $admin_list = array(
        'listKey' => array(
            'title' => '站点名称',
            'type' => '类型',
            'status' => '显示状态',
            'level' => '优先级',
            'create_time' => '创建时间',
        ),
        'model' => 'superlinks',
        'order' => 'level desc,id asc'
    );
    public $custom_adminlist = 'adminlist.html';
    public function install()
    {
        $db_config = array();
        $db_config['prefix'] = Config::get('database.prefix');
        
        $dirname=dirname(__FILE__);
        
        
    $sqldata = file_get_contents($dirname. '/install.sql');
	
	
	$sql_array=preg_split("/;[\r\n]+/", str_replace('ea_',$db_config['prefix'],$sqldata));
	foreach ($sql_array as $k => $v) {
		if (!empty($v)) {
			Db::query($v);
		}
	}
        return true;
    }

    public function uninstall()
    {
        $db_config = array();
        $db_config['prefix'] = Config::get('database.prefix');
	
        $dirname=dirname(__FILE__);
        
        
    $sqldata = file_get_contents($dirname. '/uninstall.sql');
	
	
	$sql_array=preg_split("/;[\r\n]+/", str_replace('ea_',$db_config['prefix'],$sqldata));
	foreach ($sql_array as $k => $v) {
		if (!empty($v)) {
			Db::query($v);
		}
	}

        return true;
    }



    //实现的pageFooter钩子方法
    public function friendLink($param)
    {
    	$list = Db::name('superlinks')->where('status = 1')->order('level desc,id asc')->select();
    	foreach($list as $key=>$val){
    		if($val['type'] == 1){//图片连接
    			
    			
    			$list[$key]['savepath'] = get_cover($val['cover_id'],'savepath');
    		}
    	}
    	
    	
 
        
    
        
        $this->assign('list', $list);
        $this->assign('link', $param);
       echo  $this->tplfetch('widget'); 
 
    }

  
}