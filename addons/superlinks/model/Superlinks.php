<?php
namespace addons\superlinks\model;
use Think\Model;
use Think\Db;
/**
 * 分类模型
 */
class Superlinks extends Model{
	
	
	
	
	protected function getCreateTimeAttr($value)
	{
		return friendlyDate($value);
	}
	protected function getStatusAttr($value)
	{
			if($value==1){
			$name='启用';
		}else{
			$name='禁用';
		}
		return $name;
	}
	protected function getTypeAttr($value)
	{
		if($value==1){
			$name='图片连接';
		}else{
			$name='普通连接';
		}
		return $name;
	}


	



	
}