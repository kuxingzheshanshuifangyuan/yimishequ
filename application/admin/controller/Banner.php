<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/16
 * Time: 13:42
 */
namespace app\admin\controller;

use app\common\model\Banner as BannerModel;
use app\common\controller\AdminBase;
use think\Db;
use think\Image;

class Banner extends AdminBase{

    protected $banner_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->banner_model = new BannerModel();

    }

    //轮播图管理
    public function index(){

        $banner=Db::name("banner")->field('id,img,order,dec,status,url,create_time,update_time')->order(['order' => 'asc','id' => 'desc'])->paginate(10);
        $page=$banner->render();

        $this->assign([
            'banner' => $banner,
            'page'   => $page,
        ]);

        return $this->fetch();
    }

    //轮播图添加  页面
    public function add(){
        return $this->fetch();
    }

    //添加轮播图片
    public function save(){
        //图片处理

                //获取 上传
                $file=request()->file('file');

                /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
                $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');
                if($info){
                    $b_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

                    $b_data['img']  =$b_img;
                    $b_data['order']=request()->post('order');
                    $b_data['dec']=request()->post('dec');
                    $b_data['url']=request()->post('url');
                    $b_data['create_time']=time();
                    $b_data['update_time']=time();
                    $b_one=Db::name("banner")->insert($b_data);
                    if($b_one){
                        $this->success('添加成功！',url('banner/index'));
                    }else{
                        $this->success('添加失败！',url('banner/add'));
                    }
                }else{
                    $this->success('上传文件不符要求，重新上传',url('banner/add'));
                }


    }

    //删除
    public function delete($id)
    {
        if ($this->banner_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //轮播图状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('banner')->where('id', $id)->update(['status' => $status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    //轮播图修改 页面
    public function edit(){
        $banner_id=input('id');
        $update_banner=Db::name('banner')->where('id',$banner_id)->field('id,order,img,url,dec')->find();
        $this->assign('update_banner',$update_banner);

        return $this->fetch();
    }

    //修改轮播图
    public function update(){

        $banner_id=$_POST['id'];
        $order=request()->post('order') ? request()->post('order') : '';
        $url=request()->post('url') ? request()->post('url') : '';
        $dec=request()->post('dec') ? request()->post('dec') : '';

        if(empty($_FILES['file']['name'])){  //不修改图片
            $notUpdate['order']=$order;
            $notUpdate['url']  =$url;
            $notUpdate['dec']  =$dec;

            $notUpdate['update_time']=time();
            $update_text=Db::name('banner')->where('id',$banner_id)->update($notUpdate);
            if($update_text){
                $this->success('修改成功!',url('banner/index'));
            }
        }else{  //修改图片

            //获取 上传
            $file=request()->file('file');

            /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');
            if($info){
                $b_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

                $b_data['img']  =$b_img;
                $b_data['order']=request()->post('order');
                $b_data['dec']=request()->post('dec');
                $b_data['url']=request()->post('url');

                $b_data['update_time']=time();
                $b_one=Db::name("banner")->where('id',$banner_id)->update($b_data);
                if($b_one){
                    $this->success('修改成功！',url('banner/index'));
                }else{
                    $this->success('修改失败！',url('banner/edit',['id'=>$banner_id]));
                }
            }else{
                $this->success('上传文件不符要求，重新上传',url('banner/edit',['id'=>$banner_id]));
            }


        }

    }

}