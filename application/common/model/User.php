<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
    protected $insert = ['regtime','userip'];
    
    
    /**
     * 创建时间
     * @return bool|string
     */
    protected function setRegtimeAttr()
    {
        return time();
    }
    protected function setUseripAttr()
    {
    	
    	return $_SERVER["REMOTE_ADDR"];
    }

    /**
     * 用户  会员等级  链表查
     */
    public function userGroup(){

        return $this->hasOne('Usergrade','id','usergrade');
    }
}