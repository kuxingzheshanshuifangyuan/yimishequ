<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/8/21
 * Time: 18:49
 */
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Db;

class Edition extends AdminBase{

    /*版本管理*/
    public function index(){

        $andriod=Db::name('edition')->where(['type'=>1])->order(['edition'=>'desc'])->select();
        $ios=Db::name('edition')->where(['type'=>2])->order(['edition'=>'desc'])->select();

        $this->assign([
            'andriod' => $andriod,
            'ios'     => $ios,
        ]);
        return $this->fetch();
    }

    /*更新版本*/
    public function update(){

        $data['status']=1;
        $data['update_time']=time();
        $data['forced_update']=request()->post('forced_update');

        if(isset($_POST['andriod_edition'])){
            $update_andriod=Db::name('edition')->where('id',$_POST['andriod_edition'])->update($data);
            if($update_andriod){
                $this->success('提交成功！',url('edition/index'),'',1);
            }
        }

        if(isset($_POST['ios_edition'])){
            $update_ios=Db::name('edition')->where('id',$_POST['ios_edition'])->update($data);
            if($update_ios){
                $this->success('提交成功！',url('edition/index'),'',1);
            }
        }

    }

}