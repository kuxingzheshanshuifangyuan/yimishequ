<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/24
 * Time: 14:34
 */

namespace app\admin\controller;

use app\common\model\Task as TaskModel;
use app\common\controller\AdminBase;
use think\Db;

class Task extends AdminBase{

    protected $task_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->task_model = new TAskModel();

    }

    //任务 首页
    public function index(){

        $task=Db::name('task')->order(['id'=>'desc'])->paginate(10);
        $page=$task->render();
        $this->assign('task',$task);
        $this->assign('page',$page);

        return $this->fetch();
    }

    //添加任务
    public function add(){

        //会员等级
        $usergrade=Db::name('usergrade')->order(['score'=>'asc'])->select();
        $this->assign('usergrade',$usergrade);

        return $this->fetch();
    }

    //保存任务
    public function save(){

        $data['task_name']=request()->post('task_name');
        $usergrade=implode(',',$_POST['task_allow_group']);
        $data['task_allow_group']=$usergrade;

        $data['task_point']=request()->post('task_point');
        $data['task_money']=request()->post('task_money');
        $data['task_limit']=request()->post('task_limit');

        $data['task_per']=request()->post('task_per');
        $data['task_dec']=request()->post('task_dec');
        $data['task_type']=request()->post('task_type');

        $data['create_time']=time();
        $data['update_time']=time();
        $add_task=Db::name('task')->insert($data);
        if($add_task){
            $this->success('添加成功！',url('task/index'));
        }else{
            $this->error('添加失败！',url('task/add'));
        }
    }

    //删除任务
    public function delete($id)
    {
        if ($this->task_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //编辑任务
    public function edit(){
        $task_id=input('id');
        $task=Db::name('task')->where('id',$task_id)->find();

        //当前任务所允许会员等级
        $task_groups=$task['task_allow_group'];
        $task_group=explode(',',$task_groups);

        //所有会员等级
        $usergrade=Db::name('usergrade')->order(['score'=>'asc'])->select();


        $this->assign('task',$task);
        $this->assign('task_group',$task_group);
        $this->assign('usergrade',$usergrade);

        return $this->fetch();
    }

    //修改任务
    public function update(){

        $task_id=$_POST['id'];

        $task['task_name']=request()->post('task_name') ? request()->post('task_name') : '';
        $task['task_allow_group']= implode(',' , isset($_POST['task_allow_group']) ? $_POST['task_allow_group'] : array());
        $task['task_point']=request()->post('task_point') ? request()->post('task_point') : '';
        $task['task_money']=request()->post('task_money') ? request()->post('task_money') : '';
        $task['task_limit']=request()->post('task_limit') ? request()->post('task_limit') : '';
        $task['task_per']=request()->post('task_per') ? request()->post('task_per') : '';
        $task['task_dec']=request()->post('task_dec') ? request()->post('task_dec') : '';
        $task['task_type'] =request()->post('task_type') ? request()->post('task_type') : '';

        $task['update_time']=time();

        $update_task=Db::name('task')->where('id',$task_id)->update($task);
        if($update_task){
            $this->success('修改成功！',url('task/index'));
        }else{
            $this->success('修改失败!',url('task/edit',['id'=>$task_id]));
        }

    }

    //状态
    public function updatestatus($id,$status)
    {
        if ($this->request->isGet()) {

            if (Db::name('task')->where('id', $id)->update(['status'=>$status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }
}