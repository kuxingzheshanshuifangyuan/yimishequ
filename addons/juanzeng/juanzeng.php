<?php
namespace addons\juanzeng;

use app\common\controller\Addon;
use Think\Db;
use Think\Config;


class juanzeng extends Addon
{
    private $error;

    public $info = array(
        'name' => 'juanzeng',
        'title' => '捐赠二维码',
        'description' => '用于捐赠展示二维码',
        'status' => 1,
        'author' => 'zswin',
        'version' => '0.1'
    );

   
    public function install()
    {
    	$this->getisHook('juan', $this->info['name'], $this->info['description']);
    
        return true;
    }

    public function uninstall()
    {


        return true;
    }
   
   
    public function juan()
    {
    	
    
    	echo  $this->tplfetch('juan');
    }
  

}