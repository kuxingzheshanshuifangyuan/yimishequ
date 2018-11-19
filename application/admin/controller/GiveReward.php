<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/8/21
 * Time: 9:11
 */
namespace app\admin\controller;

use app\common\model\GiveReward as GiveRewardModel;
use app\common\controller\AdminBase;
use think\Db;

class GiveReward extends AdminBase{

    protected $GiveReward_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->GiveReward_model = new GiveRewardModel();

    }

    /*打赏管理*/
    public function index(){

        $give_reward=Db::name('give_reward')->order(['order'=>'asc'])->paginate(10);
        $page=$give_reward->render();
        $this->assign([
            'give_reward' => $give_reward,
            'page'        => $page,
        ]);
        return $this->fetch();
    }

    /*添加打赏金币*/
    public function add(){
        return $this->fetch();
    }

    /*保存打赏金币*/
    public function save(){
        $data['order']=request()->post('order');
        $data['money']=request()->post('money');
        $data['create_time']=time();
        $data['update_time']=time();

        $add_reward=Db::name('give_reward')->insert($data);
        if($add_reward){
            $this->success('添加成功！',url('give_reward/index'));
        }else{
            $this->success('添加失败,重新添加！',url('give_reward/add'));
        }
    }

    /*删除*/
    public function delete($id)
    {
        if ($this->GiveReward_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }
    
    /*编辑*/
    public function edit(){
        $id=input('id');
        $give_reward=Db::name('give_reward')->field('id,order,money')->where('id',$id)->find();
        $this->assign(['give_reward' => $give_reward,]);
        return $this->fetch();
    }

    /*修改打赏金币*/
    public function update(){
        $id=request()->post('id');

        $data['order']=request()->post('order') ? request()->post('order') : '';
        $data['money']=request()->post('money') ? request()->post('money') : '';
        $data['update_time']=time();
        $update_reward=Db::name('give_reward')->where('id',$id)->update($data);
        if($update_reward){
            $this->success('修改成功！',url("give_reward/index"));
        }else{
            $this->error("修改失败，重新修改！",url("give_reward/edit",["id"=>$id]));
        }
    }

    /*状态*/
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('give_reward')->where('id', $id)->update(['status' => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }
}