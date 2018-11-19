<?php
namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\controller\AdminBase;
use think\Config;
use think\Db;
use think\Image;
/**
 * 用户管理
 * Class AdminUser
 * @package app\admin\controller
 */
class User extends AdminBase
{
    protected $user_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->user_model = new UserModel();
      
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param int    $page
     * @return mixed
     */
    public function index($keyword='')
    {
        $map = [];
        $order = [];
        $order['id'] = 'desc';
        if ($keyword) {
        	  $map['username|mobile|usermail'] = ['like', "%{$keyword}%"];
        }

        $user_list = Db::name('user')
            ->alias('u')
            ->join('usergrade g','u.usergrade = g.id','left')
            ->field('u.id,u.username,u.point,u.money,u.userhead,u.usergrade,u.status,u.last_login_time,u.last_login_ip,u.is_robot,g.name')
            ->where($map)
            ->order($order)
            ->paginate(10,false,['page'=>$this->request->get('page')]);



//        $user_list = $this->user_model->where($map)->order('id DESC')->paginate(10);

//        dump($user_list);exit;

        return $this->fetch('index', ['user_list' => $user_list, 'keyword' => $keyword]);

    }

    /**
     * 添加用户
     * @return mixed
     */
    public function add()
    {
        $usergrade=Db::name('usergrade')->field('id,name,score')->order(['score'=>'asc'])->select();


        $this->assign('usergrade',$usergrade);
        return $this->fetch();
    }

    /**
     * 保存用户
     */
    public function save()
    {
            $file=request()->file('file');
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH .'/' . 'uploads');
            if($info){
                $img='/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

                $data['userhead']=$img;

                $data['username']=request()->post('username');
                $data['mobile']=request()->post('mobile');
                $data['point']=request()->post('point');
                $data['usergrade']=request()->post('usergrade');
                $data['is_robot']=request()->post('is_robot');
                $data['status']=request()->post('status');
                $data['password']=md5(md5(request()->post('password')));
                $data['last_login_time']=time();
                $data['userip']=$this->request->ip();
                $data['regtime']=time();

                $insertData=Db::name('user')->insert($data);
                if($insertData){
                    $this->success('添加成功！',url('user/index'));
                }else{
                    $this->success('添加失败！',url('user/add'));
                }

            }else{
                $this->success('上传文件不符要求,重新上传！',url('user/add'));
            }
      /*  $file=request()->file('userhead');
        $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH . 'public' . '/' . 'uploads');
        if($info){
            $img='public/'.'uploads/'.date('Ymd').'/'.$info->getFilename();
            $data            = $this->request->post();

        }*/

        /*if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'User');

            if ($validate_result !== true) {
               // $this->error($validate_result);
                return json(array('code' => 0, 'msg' =>$validate_result));
            } else {
            	
            //	$data['salt'] = generate_password(18);

                $data['password'] = md5(md5($data['password']));
                $data['last_login_time']=time();
                if ($this->user_model->allowField(true)->save($data)) {
                    //$this->success('保存成功');
                    return json(array('code' => 200, 'msg' => '添加成功'));
                } else {
                   // $this->error('保存失败');
                    return json(array('code' => 0, 'msg' => '添加失败'));
                }
            }
        }*/

    }

    /**
     * 编辑用户
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $user = $this->user_model->find($id);

        //用户  会员等级
        $usergrade=Db::name('usergrade')->order(['score'=>'asc'])->select();
        $this->assign('usergrade',$usergrade);
        return $this->fetch('edit', ['user' => $user]);
    }

    /**
     * 更新用户
     * @param $id
     */
    public function update($id)
    {
        /*dump($_POST);die;*/

//        if ($this->request->isPost()) {
//            $data            = $this->request->param();
//            $validate_result = $this->validate($data, 'User');
//
//            if ($validate_result !== true) {
//              //  $this->error($validate_result);
//               return json(array('code' => 0, 'msg' => $validate_result));
//            } else {
//                $user           = $this->user_model->find($id);
//                $user->id       = $id;
//                $user->username = $data['username'];
//                $user->mobile   = $data['mobile'];
//               /* $user->usermail    = $data['email'];*/
//                $user->usergrade   =$data['usergrade'];
//                $user->userhead    =$data['userhead'];
//                $user->is_robot    =$data['is_robot'];
//                $user->last_login_time =time();
//
//                if($data['status']==0&&$user['status']>0){
//                	$user->status   = 0-$user['status'];//等于 负的状态，当恢复时可以变为正数
//                }else if($data['status']==0&&$user['status']<=0){
//
//                	//不变
//                }else if($data['status']==1&&$user['status']>0){
//                	//不变
//                }else {
//                	$user->status   = 0-$user['status'];
//                }
//
//
//                $user->point   = $data['point'];
//
//                if (!empty($data['password']) && !empty($data['confirm_password'])) {
//                    $user->password = md5(md5($data['password']));
//                }
//                if ($user->save() !== false) {
//                   // $this->success('更新成功');
//                    return json(array('code' => 200, 'msg' => '更新成功'));
//                } else {
//                   // $this->error('更新失败');
//                    return json(array('code' => 0, 'msg' => '更新失败'));
//                }
//            }
//        }
        $user_id=request()->post('id');

        $username=request()->post('username') ? request()->post('username') : '';
        $mobile=request()->post('mobile') ? request()->post('mobile') : '';
        $point=request()->post('point') ? request()->post('point') : '';
        $usergrade=request()->post('usergrade') ? request()->post('usergrade') : '';
        $is_robot=request()->post('is_robot') ? request()->post('is_robot') : '';
        $status=request()->post('status') ? request()->post('status') : '';
        $password=md5(md5(request()->post('password')));
        $userip=$this->request->ip();


        if(empty($_FILES['file']['name']) ){
            $data['username']=$username;
            $data['mobile']=$mobile;
            $data['point']=$point;
            $data['usergrade']=$usergrade;
            $data['is_robot']=$is_robot;
            $data['status']=$status;
            $data['password']=$password;
            $data['last_login_time']=time();
            $data['userip']=$userip;
            $data['regtime']=time();

            $updateData=Db::name('user')->where('id',$user_id)->update($data);
            if($updateData){
                $this->success('修改成功！',url('user/index'));
            }else {
                $this->success('修改失败，重新修改！',url('user/edit',['id'=>$user_id]));
            }
        } else {
            $file=request()->file('file');
            $info = $file->validate(['ext'=>'jpg,png,gif'])->move(ROOT_PATH .'/' . 'uploads');
            if($info){
                $img='/'.'uploads/'.date('Ymd').'/'.$info->getFilename();

                $data['userhead']=$img;

                $data['username']=$username;
                $data['mobile']=$mobile;
                $data['point']=$point;
                $data['usergrade']=$usergrade;
                $data['is_robot']=$is_robot;
                $data['status']=$status;
                $data['password']=$password;
                $data['last_login_time']=time();
                $data['userip']=$userip;
                $data['regtime']=time();

                $updateData=Db::name('user')->where('id',$user_id)->update($data);
                if($updateData){
                    $this->success('修改成功！',url('user/index'));
                }else{
                    $this->success('修改失败，重新修改！',url('user/edit',['id'=>$user_id]));
                }

            }else{
                $this->success('上传文件不符要求,重新上传！',url('user/edit',['id'=>$user_id]));
            }
        }

    }

    /**
     * 删除用户
     * @param $id
     */
    public function delete($id)
    {
        if ($this->user_model->destroy($id)) {
            //$this->success('删除成功');
            return json(array('code' => 200, 'msg' => '删除成功'));
        } else {
           // $this->error('删除失败');
            return json(array('code' => 0, 'msg' => '删除失败'));
        }
    }

    public function getAotuGade(){
        if(!$this->request->isAjax()){
            return json(['code'=>0,'msg'=>'']);
        }

        $id = $this->request->param('id');

        if(!$id){
            return json(['code'=>0,'msg'=>'id不能为空']);
        }

        $score = Db::name('usergrade')->where(['id'=>$id])->value('score');

        if(!$score){
            return json(['code'=>0,'msg'=>'']);
        }

        return json(['code'=>1,'msg'=>'success','data'=>$score]);


    }


}