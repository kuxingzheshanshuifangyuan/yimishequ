<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/19
 * Time: 17:55
 */

namespace app\admin\controller;

use app\common\model\Link as LinkModel;
use app\common\controller\AdminBase;
use think\Db;

class Blogroll extends AdminBase{

    protected $link_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->link_model = new LinkModel();

    }


    //友情链接  页面
    public function index(){

        $blogroll=Db::name('link')->field('id,sort,link_ch,link_en,status,create_time,update_time,link_img,url')->order(['sort'=>'asc','id'=>'desc'])->paginate(5);
        $page=$blogroll->render();

        $this->assign([
            'blogroll' => $blogroll,
            'page'     => $page,
        ]);

        return $this->fetch();
    }

    //友情链接 添加 页面
    public function add(){
        return $this->fetch();
    }

    //添加 友情链接
    public function save(){
        $file=request()->file('file');

        /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
        $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

        if($info){
            $link_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

            $blogroll['link_img'] = $link_img;
            $blogroll['sort']     = request()->post('sort');
            $blogroll['link_ch']  = request()->post('link_ch');
            $blogroll['link_en']  = request()->post('link_en');
            $blogroll['url']      = request()->post('url');
            $blogroll['create_time']=time();
            $blogroll['update_time']=time();

            $add_link=Db::name('link')->insert($blogroll);
            if($add_link){
                $this->success('添加成功！',url('blogroll/index'));
            }
        }else{
            $this->success('上传文件不符，重新上传',url('blogroll/add'));
        }

    }

    //友情链接  状态
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('link')->where('id', $id)->update(['status' =>$status]) !== false) {
                //  $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    //删除 友情链接
    public function delete($id)
    {
        if ($this->link_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    //修改友情链接 页面
    public function edit(){

        $link_id=input('id');
        $link=Db::name('link')->where('id',$link_id)->field('id,sort,link_ch,link_en,url,link_img')->find();
        $this->assign('link',$link);

        return $this->fetch();
    }

    //修改友情链接
    public function update(){
        $link_id=$_POST['id'];

        $link_ch   = isset($_POST['link_ch']) ? $_POST['link_ch'] : '';
        $link_en   = isset($_POST['link_en']) ? $_POST['link_en'] : '';
        $link_url  = isset($_POST['url']) ? $_POST['url'] : '';
        $link_sort = isset($_POST['sort']) ? $_POST['sort'] : '';

        if(empty($_FILES['file']['name'])){  //不修改图片
            $link['link_ch']=$link_ch;
            $link['link_en']=$link_en;
            $link['url']    =$link_url;
            $link['sort']   =$link_sort;
            $link['update_time']=time();

            $notupdate=Db::name('link')->where('id',$link_id)->update($link);
            if($notupdate){
                $this->success('修改成功！',url('blogroll/index'));
            }
        }else{
            //图片处理
            $file=request()->file('file');

            /*$info=$file->move(ROOT_PATH.'public'.'/'.'uploads');*/
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');

            if($info){
                $link_img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();


                $blogroll['link_img'] = $link_img;
                $blogroll['sort']     = $link_sort;
                $blogroll['link_ch']  = $link_ch;
                $blogroll['link_en']  = $link_en;
                $blogroll['url']      = $link_url;

                $blogroll['update_time']=time();

                $add_link=Db::name('link')->where('id',$link_id)->update($blogroll);
                if($add_link){
                    $this->success('修改成功！',url('blogroll/index'));
                }
            }else{
                $this->success('上传文件不符，重新上传',url('blogroll/edit',['id'=>$link_id]));
            }

        }
    }

}