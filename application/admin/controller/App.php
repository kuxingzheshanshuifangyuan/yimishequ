<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/16
 * Time: 16:54
 */

namespace app\admin\controller;

use app\common\model\App as AppModel;
use app\common\controller\AdminBase;
use think\Db;

class App extends AdminBase{

    protected $app_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->app_model = new AppModel();

    }


    //app展示页面
    public function index(){

        $app=Db::name('app')->field('id,img,order,url,dec,status,create_time,update_time')->order(['order'=>'asc','id '=>'desc'])->paginate(5);
        $page=$app->render();

        $this->assign([
            'app'  => $app,
            'page' => $page,
        ]);
        return $this->fetch();
    }

    //app添加  页面
    public function add(){
        return $this->fetch();
    }

    //添加app
    public function save(){

        //获取 上传
        $file=request()->file('file');

        /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
        $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

        if($info){
            $a_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();
            $a_data['img']   = $a_img;
            $a_data['order'] = request()->post('order');
            $a_data['dec']   = request()->post('dec');
            $a_data['url']   = request()->post('url');
            $a_data['create_time']=time();
            $a_data['update_time']=time();
            $a_one=Db::name("app")->insert($a_data);
            if($a_one){
                $this->success('添加成功！',url('app/index'));
            }else{
                $this->success('添加失败！',url('app/add'));
            }
        }else{
            $this->success('上传文件不符，重新上传',url('app/add'));
        }

    }

    //删除
    public function delete($id)
    {
        if ($this->app_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //修改app  页面
    public function edit(){
        $app_id=input('id');
        $update_app=Db::name('app')->where('id',$app_id)->field('id,img,order,dec,url')->find();
        $this->assign('update_app',$update_app);
        return $this->fetch();
    }

    //修改app
    public function update(){
        $app_id= $_POST['id'];
        $order = isset($_POST['order']) ? $_POST['order'] : '';
        $url   = isset($_POST['url']) ? $_POST['url'] : '';
        $dec   = isset($_POST['dec']) ? $_POST['dec'] : '';
        if(empty($_FILES['file']['name'])){  //不修改app图片
            $notUpdate['order'] = $order;
            $notUpdate['url']   = $url;
            $notUpdate['dec']   = $dec;
            $notUpdate['update_time']=time();
            $update_text=Db::name('app')->where('id',$app_id)->update($notUpdate);
            if($update_text){
                $this->success('修改成功!',url('app/index'));
            }
        }else{   //修改app图片
            //获取 上传
            $file=request()->file('file');

            /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

            if($info){
                $a_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();


                $a_data['img']   = $a_img;
                $a_data['order'] = $order;
                $a_data['dec']   = $dec;
                $a_data['url']   = $url;

                $a_data['update_time']=time();
                $a_one=Db::name("app")->where('id',$app_id)->update($a_data);
                if($a_one){
                    $this->success('修改成功！',url('app/index'));
                }else{
                    $this->success('修改失败！',url('app/edit',['id'=>$app_id]));
                }
            }else{
                $this->success('上传文件不符，重新上传',url('app/edit',['id'=>$app_id]));
            }


        }
    }

    //修改app状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('app')->where('id', $id)->update(['status' =>$status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

}