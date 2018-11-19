<?php
namespace addons\attach;

use app\common\controller\Addon;
use Think\Db;
use Think\Config;


class attach extends Addon
{
    private $error;

    public $info = array(
        'name' => 'attach',
        'title' => '附件上传',
        'description' => '用于附件的上传',
        'status' => 1,
        'author' => 'zswin',
        'version' => '0.1'
    );

    public $admin_list = array(
        'model' => 'attach', //要查的表
        'fields' => '*', //要查的字段
        'map' => '', //查询条件, 如果需要可以再插件类的构造方法里动态重置这个属性
        'order' => 'id desc', //排序,
        'listKey' => array( //这里定义的是除了id序号外的表格里字段显示的表头名
            'id' => 'ID',
            'name' => '原文件名',
            'savename' => '保存名称',
            'savepath' => '保存路径',
            'size' => '文件大小',
        		'score' => '积分',
        		'download' => '下载',
            'create_time' => '上传时间',
        ),
    );
    public $custom_adminlist = 'adminlist.html';
    public function install()
    {
    	$this->getisHook('forumaddattach', $this->info['name'], $this->info['description']);
    	$this->getisHook('threadattach', $this->info['name'], $this->info['description']);
    
    	
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
   
   
    public function forumaddattach()
    {
    	
    	$config = $this->getConfig('attach');
    	
    	$this->assign('configext',$config['configext']);
    	echo  $this->tplfetch('forumadd');
    }
    public function threadattach()
    {
    	
    	echo  $this->tplfetch('threadattach');
    }

   
  

}