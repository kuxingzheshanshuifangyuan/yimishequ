<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/16
 * Time: 18:03
 */
namespace app\admin\controller;

use app\common\model\Money as MoneyModel;
use app\common\controller\AdminBase;
use think\Db;

class SuccessDemo extends AdminBase{

    protected $money_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->money_model = new MoneyModel();

    }


    //成功案例页面
    public function index(){
        $demo=Db::name('money')->order(['id'=>'desc'])->paginate(5);
        $page=$demo->render();
        $this->assign([
           'demo' => $demo,
           'page' => $page,
        ]);
        return $this->fetch();

    }

    //添加案例  页面
    public function add(){
        return $this->fetch();

    }

    //添加案例
    public function save(){

        $data['name'] =request()->post('name');
        $data['phone']=request()->post('phone');
        $data['money']=request()->post('money');
        $data['create_time']=time();
        $data['update_time']=time();
        $add=DB::name('money')->insert($data);
        if($add){
            $this->success('添加成功!',url('success_demo/index'));
        }else{
            $this->error('添加失败！',url('success_demo/add'));
        }

    }

    //案例删除
    public function delete($id)
    {
        if ($this->money_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //案例状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('money')->where('id', $id)->update(['status' => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }

    //案例修改  页面
    public function edit(){
        $demo_id=input('id');
        $update_demo=Db::name('money')->where('id',$demo_id)->field('id,name,phone,money')->find();
        $this->assign('update_demo',$update_demo);
        return $this->fetch();

    }

    //修改案例
    public function update(){

        $demo_id=$_POST['id'];

        $update['name'] =isset($_POST['name']) ? $_POST['name'] : '';
        $update['phone']=isset($_POST['phone']) ? $_POST['phone'] : '';
        $update['money']=isset($_POST['money']) ? $_POST['money'] : '';
        $update['update_time']=time();
        $update_demo=Db::name('money')->where('id',$demo_id)->update($update);
        if($update_demo){
            $this->success('修改成功！',url('success_demo/index'));
        }

    }

}