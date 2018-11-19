<?php
namespace addons\attach\model;
use Think\Model;
use Think\Db;
/**
 * 分类模型
 */
class Attach extends Model{
	
	
	
	
	protected function getCreateTimeAttr($value)
	{
		return friendlyDate($value);
	}
	protected function getSizeAttr($value)
	{
		return format_bytes($value);
	}


	



	
}