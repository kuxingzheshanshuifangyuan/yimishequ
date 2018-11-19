<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/8/23
 * Time: 13:36
 */
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Db;

class Operate extends AdminBase{

    /*运营配置*/
    public function index(){

        $site_config = Db::name('system')->field('value')->where('name', 'operate')->find();
        $site_config = unserialize($site_config['value']);

        $this->assign([
            'site_config'=>$site_config
        ]);
        return $this->fetch();

    }

    /*更新配置*/
    public function updateOperate(){
        if ($this->request->isPost()) {
            $site_config = $this->request->post('site_config/a');
            if($site_config['sign_max'] < $site_config['sign_min'] && $site_config['sign_min'] != 0){
                return json(array('code' => 0, 'msg' => '签到奖励最大积分必须大于最小奖励积分'));
            }

            $data['value'] = serialize($site_config);

            if (Db::name('system')->where('name', 'operate')->update($data) !== false) {

                $this->success('提交成功！',url('operate/index'));

            } else {
                $this->success('提交失败！',url('operate/index'));

            }

        }

    }

}