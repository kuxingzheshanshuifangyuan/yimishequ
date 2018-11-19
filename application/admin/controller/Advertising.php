<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/28
 * Time: 9:04
 */
namespace app\admin\controller;

use app\common\model\Advertising as AdvertisingModel;
use app\common\controller\AdminBase;
use think\Db;
use think\Image;

class Advertising extends AdminBase{

    protected $advertising_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->advertising_model = new AdvertisingModel();

    }

    //广告管理页面
    public function index(){

        $AD=Db::name('advertising')->field('id,img,sort,link,dec,create_time,update_time,status')->order(['sort' => 'asc','id'=>'desc'])->paginate(10);
        $page=$AD->render();

        $this->assign([
            'AD' => $AD,
            'page' => $page,
        ]);
        return $this->fetch();
    }

    //添加广告
    public function add(){

        return $this->fetch();
    }

    //保存广告
    public function save(){
        //获取 上传
        $file=request()->file('file');

        /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
        $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

        if($info){
            $img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

            $data['img'] =$img;
            $data['sort']=request()->post('sort');
            $data['link']=request()->post('link');
            $data['dec'] =request()->post('dec');
            $data['create_time']=time();
            $data['update_time']=time();
            $insertAD=Db::name('advertising')->insert($data);
            if($insertAD){
                $this->success('添加成功！',url('advertising/index'));
            }else{
                $this->success('添加失败！',url('advertising/add'));
            }
        }else{
            $this->success('上传文件不符，重新上传',url('advertising/add'));
        }


    }

    //修改 广告状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('advertising')->where('id', $id)->update(['status' =>$status]) !== false) {
                // $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    //删除广告
    public function delete($id)
    {
        if ($this->advertising_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //编辑广告
    public function edit(){
        $ad_id=input('id');
        $edit_ad=Db::name('advertising')->where('id',$ad_id)->field('id,sort,link,img,dec')->find();
        $this->assign('edit_ad',$edit_ad);

        return $this->fetch();
    }

    //修改广告
    public function update(){
        $ad_id=$_POST['id'];

        $sort=request()->post('sort') ? request()->post('sort') : '';
        $link=request()->post('link') ? request()->post('link') : '';
        $dec = request()->post('dec') ? request()->post('dec') : '';

        if(empty($_FILES['file']['name'])){  //不修改广告图片

            $not_ad['sort']=$sort;
            $not_ad['link']=$link;
            $not_ad['dec'] =$dec;
            $not_ad['update_time']=time();

            $update_ad = Db::name('advertising')->where('id',$ad_id)->update($not_ad);

            if($update_ad){
                $this->success('修改成功！',url('advertising/index'));
            }else {
                $this->success('修改失败！',url('advertising/edit',['id'=>$ad_id]));
            }
        }else{   //修改广告图片

            //获取 上传
            $file=request()->file('file');

            /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

            if($info){
                $img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

                $data['img'] =$img;
                $data['sort']=$sort;
                $data['link']=$link;
                $data['dec'] =$dec;
                $data['update_time']=time();
                $insertAD=Db::name('advertising')->where('id',$ad_id)->update($data);
                if($insertAD){
                    $this->success('修改成功！',url('advertising/index'));
                }else{
                    $this->success('修改失败！',url('advertising/edit',['id'=>$ad_id]));
                }
            }else{
                $this->success('上传文件不符，重新上传',url('advertising/edit',['id'=>$ad_id]));
            }
        }
    }
}