<?php
namespace app\index\model;
use think\Model;
class Comment extends Model
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

//    public function user()
//    {
//        return $this->hasOne('User', 'id', 'uid');
//    }
//
//    public function referTopic()
//    {
//        return $this->hasOne('Comment', 'id', 'tid');
//    }

}
