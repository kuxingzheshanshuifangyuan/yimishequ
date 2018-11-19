<?php

namespace app\admin\controller;

use app\common\model\AppUpdate as AppUpdateModel;
use app\common\controller\AdminBase;
use think\Db;

class AppUpdate extends AdminBase{

    protected $AppUpdate_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->AppUpdate_model = new AppUpdateModel();

    }

    /**
     * 渠道列表
     * @author GuoLin
     * @createdate 2018-09-19
     *
     */
    public function index(){

        $dataList = Db::name('app_update')->paginate(15, false, ['query' => ['page'=>$this->request->param('page')]]);
        $this->assign([
            'dataList'  => $dataList,
            'page'      => $dataList->render()
        ]);

        return $this->fetch();
    }

    /**
     * 发布新版
     * @author GuoLin
     * @createdate 2018-09-19
     *
     */
    public function push(){
        $id = $this->request->param('id','');

        if(!$id){
            $this->error('非法请求');
        }

        $data = Db::name('app_update')->where(['id'=>$id])->find();

        if($this->request->isPost()){

            $version = $this->request->param('version','');

            if($version <= $data['version']){
                $this->error('升级版本号不能小于当前版本');
            }

            $updateData = [
                'id'            => $id,
                'version'       => $version,
                'update_time'   => time()
            ];

            $insertData = [
                'platform_id'   => $id,
                'push_content'  => $this->request->param('push_content',''),
                'version'       => $version,
                'is_packaging'  => $this->request->param('is_packaging',0),
                'is_forced'     => $this->request->param('is_forced',0),
                'download_url'  => $this->request->param('download_url',''),
                'create_time'   => time(),
            ];

            Db::startTrans();
            try{
                Db::name('app_update')->update($updateData);
                Db::name('app_update_record')->insert($insertData);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error('发布失败',url('app_update/push',['id'=>$id]));
            }

            $this->success('发布成功',url('admin/AppUpdate/index'));

        }

        $this->assign([
            'data'  => $data,
            'id'    => $id
        ]);

        return $this->fetch();
    }


    public function add(){
        if($this->request->isPost()){

            $name = $this->request->param('name');

            if(!$name){
                $this->error('名称不能为空');
            }

            $result = Db::name('app_update')->insert([
                'name'        => $name,
                'description' => $this->request->param('description',''),
                'create_time' => time(),
                'update_time' => time(),
            ]);

            if($result){
                $this->success('添加成功',url('admin/AppUpdate/index'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    public function updatePackaging($id,$status){
        if ($this->request->isPost()) {

            if (Db::name('app_update_record')->where('id', $id)->update(['is_packaging' =>$status]) !== false) {

                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {

                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    public function updateForced($id,$status){
        if ($this->request->isPost()) {
            if (Db::name('app_update_record')->where('id', $id)->update(['is_forced' =>$status]) !== false) {

                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {

                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    public function versionList(){

        $dataList = Db::name('app_update_record r')
            ->join('app_update u','r.platform_id = u.id')
            ->field('r.id,u.name,r.version,r.is_forced,r.is_packaging,r.push_content,r.download_url,r.create_time')
            ->order(['create_time'=>'desc'])
            ->paginate(15, false, ['query' => ['page'=>$this->request->param('page')]]);

        $this->assign([
            'dataList'  => $dataList
        ]);

        return $this->fetch();
    }

    public function versionEdit(){

        $id = $this->request->param('id','');

        if(empty($id)){
            $this->error('非法请求');
        }

        $data = Db::name('app_update_record')->where(['id'=>$id])->find();

        if(!$data){
            $this->error('未找到当前版本');
        }

        if($this->request->isPost()){

            $editData = $this->request->param();
            $editData['update_time'] = time();
            $result = Db::name('app_update_record')->update($editData);

            if($result !== false){
                $this->success('修改成功',url('admin/AppUpdate/versionList'));
            }else{
                $this->error('修改失败');
            }
        }

        $this->assign([
            'data'  => $data
        ]);

        return $this->fetch();
    }




//        $list = Db::name('app_platform p')
//            ->join('app_update a','a.platform_id = p.id')
//            ->field('a.id,p.name,a.platform_id,a.version,a.comment,a.is_packaging,a.is_forced,a.download_url,a.system,a.create_time')
////            ->where('')
//            ->order('a.create_time desc')
//            ->group('a.platform_id')
////            ->select();
////        dump($list);exit;
//            ->paginate(15, false, ['query' => ['page'=>$this->request->param('page')]]);

//        echo Db::name('appUpdate')->getLastSql();






//    /*App升级*/
//    public function index(){
//
//        $app_update=Db::name('app_update')->select();
//        $this->assign([
//            'app_update' => $app_update,
//        ]);
//        return $this->fetch();
//    }
//


    /*保存app升级内容*/
    public function save(){
        $app_update['app_type']=request()->post('app_type');
        $app_update['app_edition']=request()->post('app_edition');
        $app_update['push_title']=request()->post('push_title');
        $app_update['push_content']=request()->post('push_content');
        $app_update['push_platform']=request()->post('push_platform');
        $app_update['push_link']=request()->post('push_link');
        $app_update['create_time']=time();
        $app_update['update_time']=time();

        $data['type']=request()->post('app_type');
        $data['edition']=request()->post('app_edition');
        $data['create_time']=time();

        $addEdition=Db::name('edition')->insert($data);
        if($addEdition){
            $addApp=Db::name('app_update')->insert($app_update);
            if($addApp){
                $this->success('添加成功！',url("app_update/index"));
            }
        }

    }

    /*状态*/
    public function updatestatus($id,$status){
        if ($this->request->isGet()) {

            if (Db::name('app_update_record')->where('id', $id)->update(['status' =>$status]) !== false) {
                // $this->success('更新成功');
                return json(array('code' => 200, 'msg' => '修改成功'));
            } else {
                // $this->error('更新失败');
                return json(array('code' => 0, 'msg' => '修改失败'));
            }
        }

    }

    /*编辑*/
    public function edit(){

//        $id=input('id');
//        $data=Db::name('app_update')->where('id',$id)->field('id,app_type,app_edition,push_title,push_content,push_platform,push_link')->find();
//        $this->assign([
//            'data' => $data,
//        ]);
        return $this->fetch();
    }

    /*修改*/
    public function update(){

        $id=$_POST['id'];
        $data['app_type']=request()->post('app_type') ? request()->post('app_type') : '';
        $data['app_edition']=request()->post('app_edition') ? request()->post('app_edition') : '';
        $data['push_title']=request()->post('push_title') ? request()->post('push_title') : '';
        $data['push_content']=request()->post('push_content') ? request()->post('push_content') : '';
        $data['push_platform']=request()->post('push_platform') ? request()->post('push_platform') : '';
        $data['push_link']=request()->post('push_link') ? request()->post('push_link') : '';
        $data['update_time']=time();

        $update=Db::name('app_update')->where('id',$id)->update($data);

        if($update){
            $this->success('修改成功！',url('app_update/index'));
        }else{
            $this->success('修改失败,重新修改！',url('app_update/edit',["id"=>$id]));
        }

    }

    /*删除*/
    public function delete($id)
    {
        if ($this->AppUpdate_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
            // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }






}