<?php
namespace app\index\model;
use think\Model;
class Forum extends Model
{
    protected $insert = ['create_time'];


   function add($data){
		$result = $this->isUpdate(false)->allowField(true)->save($data);
		if($result){
			return true;
		}else{
			return false;
		}
	}

	function edit($data){
		$result = $this->isUpdate(true)->allowField(true)->save($data);
		if($result){
			return true;
		}else{
			return false;
		}
	}

    protected function setCreateTimeAttr()
    {
        return time();
    }
}
