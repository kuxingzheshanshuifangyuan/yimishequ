<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/19
 * Time: 14:22
 */
namespace app\admin\controller;

use app\common\model\Offer as Offermodel;
use app\common\controller\AdminBase;
use think\Db;

class Offer extends AdminBase{

    protected $offer_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->offer_model = new OfferModel();

    }

    //通知管理页面
    public function index(){

        $offer=Db::name('offer')->order(['id' => 'desc'])->paginate(5);
        $page=$offer->render();

        $this->assign([
            'offer' => $offer,
            'page'  => $page,
        ]);

        return $this->fetch();

    }

    //添加 通知 页面
    public function add(){
        return $this->fetch();

    }

    //添加通知
    public function save(){

        $offer['content']=request()->post('content');
        $offer['status'] =request()->post('status');
        $offer['creat_time'] =time();
        $offer['update_time']=time();

        $addoffer=Db::name('offer')->insert($offer);
        if($addoffer){
            $this->success('添加成功！',url('offer/index'));
        }

    }

    //修改  通知状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('offer')->where('id',$id)->update(['status' => $status]) !== false) {
                // $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '更新成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '更新失败'));
            }
        }

    }

    //删除
    public function delete($id)
    {
        if ($this->offer_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }


    //修改 通知页面
    public function edit(){

        $offer_id=input('id');
        $offer=Db::name('offer')->where('id',$offer_id)->field('id,content,status')->find();
        $this->assign('offer',$offer);
        return $this->fetch();

    }

    //修改 通知
    public function update(){
        $offer_id=$_POST['id'];

        $update_offer['content']=request()->post('content') ? request()->post('content') : '';
        $update_offer['status'] =request()->post('status') ? request()->post('status') : '';
        $update_offer['update_time']=time();

        $update=DB::name('offer')->where('id',$offer_id)->update($update_offer);

        if($update){
            $this->success('修改成功！',url('offer/index'));
        }else{
            $this->error('修改失败！',url('offer/update'));
        }

    }

}