<?php
namespace addons\superlinks\controller;
use app\admin\controller\Addons;
use Think\Loader;
use think\Model;
use think\Db;

class Superlinks extends Addons{
	

	
	/* 添加友情连接 */
	public function add(){
		
		$current = url('/admin/addons/adminlist/name/superlinks');
		//$model=  Loader::model("superlinks/model/Superlinks","Superlinks",false,"addons");
	
		$this->assign('current',$current);
		
		echo $this->fetch('addons//superlinks@superlinks/add');
	
	}
	
	/* 编辑友情连接 */
	public function edit(){
		
			$data=$this->request->param();
		$id     =  $data['id'];
		$current = url('/admin/addons/adminlist/name/Superlinks');
		$detail = D('addons://superlinks/Superlinks')->find($id);
		$this->assign('info',$detail);
		$this->assign('current',$current);
		echo $this->fetch('addons//superlinks@superlinks/edit');
		
	}
	/* 禁用友情连接 */
	public function forbidden(){
		
		$data=$this->request->param();
		$id     =  $data['id'];
		
		if(D('addons://superlinks/Superlinks')->where('id',$id)->setField('status',0)){
			
			$this->success('成功禁用该友情连接');
				
		}else{
			
			$this->error(D('addons://superlinks/Superlinks')->getError());
		}
	}
	
	/* 启用友情连接 */
	public function off(){
		
		$data=$this->request->param();
		$id     =  $data['id'];
		if(D('addons://superlinks/Superlinks')->where('id',$id)->setField('status',1)){
		
			$this->success('成功启用该友情连接');
		}else{
			$this->error(D('addons://superlinks/Superlinks')->getError());
				
		}
	}
	
	/* 删除友情连接 */
	public function del(){
		$data=$this->request->param();
		$id     =  $data['id'];
		if(D('addons://superlinks/Superlinks')->destroy($id)){
			
			$this->success('删除友情链接成功');
		}else{
			$this->error(D('addons://superlinks/Superlinks')->getError());
		}
	}
	/* 更新友情连接 */
	public function insert(){
		
		$data=$this->request->post();
		
		$data['create_time']=time();
		
	
	
	
	if(!D('addons://superlinks/Superlinks')->data($data)->allowField(true)->save()){
		
	
		$this->error('添加失败');
			
	}else{
		$this->success('添加成功',url('addons/adminlist',array('name'=>'superlinks')));
		
	
	}
	
		
	
	}
	/* 更新友情连接 */
	public function update(){
		$data=$this->request->post();
		//$data=$this->request->except('file');

		
	if(!Db::name('superlinks')->where('id',$data['id'])->data($data)->update($data)){
		
	
		$this->error('更新失败');
			
	}else{
		$this->success('更新成功',url('addons/adminlist',array('name'=>'superlinks')));
		
	
	}
	}
}
