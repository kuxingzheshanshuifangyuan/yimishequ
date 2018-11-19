<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use think\Cache;
use think\Db;

use app\common\model\User as UserModel;

class Login extends HomeBase
{
    protected $site_config;
    protected $yzmarr;

    public function _initialize()
    {
        parent::_initialize();
        $this->site_config = Cache::get('site_config');

        $this->yzmarr = explode(',', $this->site_config['site_yzm']);

        if (in_array(1, $this->yzmarr)) {
            $regyzm = 1;
        } else {
            $regyzm = 0;
        }
        if (in_array(2, $this->yzmarr)) {
            $loginyzm = 1;
        } else {
            $loginyzm = 0;
        }
        if (in_array(3, $this->yzmarr)) {
            $forgetyzm = 1;
        } else {
            $forgetyzm = 0;
        }

        $this->assign('regyzm', $regyzm);
        $this->assign('loginyzm', $loginyzm);
        $this->assign('forgetyzm', $forgetyzm);


    }


    /**
     * 用户注册
     * @author GuoLin
     * @createdate 2018-07-31
     *
     */
    public function register()
    {
        $regswitch = Cache::get('site_config');
        $site_config = Db::name('system')->field('value')->where('name', 'operate')->find();
        $site_config = unserialize($site_config['value']);
        if ($this->request->isPost()) {

            if (!$regswitch['user_reg']) {

                return json(array('code' => 0, 'msg' => '网站已关闭会员注册功能'));
            }

//            if (!empty($regswitch['baoliu'])) {
//                $arr = explode(',', $regswitch['baoliu']);
//                foreach ($arr as $k => $v) {
//                    if (strpos($data['username'], $v) !== false) {
//                        return json(array('code' => 0, 'msg' => '你的昵称被禁止注册'));
//                    }
//                }
//            }

            $password = $this->request->param('password');
            $code = $this->request->param('code');
            $mobile = $this->request->param('mobile');
            $tid = $this->request->param('tid');

//            if (in_array(1, $this->yzmarr)) {
//                if (!captcha_check(input('code'))) {
//                    return json(array('code' => 0, 'msg' => '验证码错误'));
//                }
//            }

            if (!$mobile) {
                return json(array('code' => 0, 'msg' => '手机号不能为空'));
            }

            if (!$password) {
                return json(array('code' => 0, 'msg' => '密码不能为空'));
            }

            if (!check_mobile_number($mobile)) {
                return json(array('code' => 0, 'msg' => '请输入正确的手机号'));
            }

            if (!$code) {
                return json(array('code' => 0, 'msg' => '短信验证码不能为空'));
            }

            if ($code != session('cmsCode')) {
                return json(array('code' => 0, 'msg' => '短信验证码错误'));
            }

            $isExist = Db::name('user')->where('mobile', $mobile)->find();

            if ($isExist) {
                return json(array('code' => 0, 'msg' => '该手机号已被注册'));
            }


//            $validate_result = $this->validate($data, 'User');

//            if ($validate_result !== true) {
            // $this->error($validate_result);
//                return json(array('code' => 0, 'msg' => $validate_result));
//            } else {
            // $salt = generate_password(18);

            $usergrade_id = Db::name('usergrade')->order(['score' => 'asc'])->value('id');

            $insertData = [
                // 'salt'       => $salt,
                'regtime' => time(),
                'mobile' => $mobile,
                'grades' => 0,
                'status' => 1,//config('web.WEB_REG');
                'point' => $site_config['jifen_reg'],//config('point.REG_POINT');
                'userhead' => '/public/images/default.png',
                'userip' => $this->request->ip(),
                'password' => md5(md5($password)),
                'usergrade' => $usergrade_id ? $usergrade_id : 0,
            ];

            $userId = Db::name('user')->insertGetId($insertData);

            if ($userId) {
                $userName = '米友' . $userId;
                Db::name('user')->where('id', $userId)->setField('username', $userName);
                runTask(4, $userId, $usergrade_id);
//                point_note($regswitch['jifen_reg'], $userId, 'reg');

                session('userstatus', $insertData['status']);
                session('grades', $insertData['grades']);
                session('userhead', $insertData['userhead']);
                session('username', $userName);
                session('userid', $userId);
                session('point', $insertData['point']);

//                $cook = array('id' => $userId, 'status' => $insertData['status'], 'point' => $insertData['point'], 'username' => $userName, 'userhead' => $insertData['userhead'], 'grades' => $insertData['grades']);
//                systemSetKey($cook);

                Db::name('user')->update(
                    [
                        'last_login_time' => time(),
                        'last_login_ip' => $insertData['userip'],
                        'id' => $userId
                    ]
                );

                if ($tid) {
                    $isUser = Db::name('user')->where(['id' => $tid])->find();
                    if ($isUser) {
                        $site_config = Db::name('system')->field('value')->where('name', 'operate')->value('value');
                        $site_config = unserialize($site_config);
                        $invite_friends = $site_config['invite_friends'];

                        Db::name('invite_record')->insert([
                            'uid' => $userId,
                            'tid' => $tid,
                            'money' => $invite_friends,
                            'create_time' => time()
                        ]);

                        Db::name('user')->where(['id' => $tid])->setInc('money', $invite_friends);

                        addMoneyRecord($tid, '成功邀请好友', $invite_friends, 2, 7);
                    }

                }

                //$member->where('id',session('userid'))->setInc('point',$site_config['jifen_login']);
//                point_note($site_config['jifen_login'], session('userid'), 'login');

                session('cmsCode', md5(time()));
                return json(array('code' => 1, 'msg' => '注册成功'));
            } else {
                return json(array('code' => 0, 'msg' => '注册失败'));
            }
        }
        if (!$regswitch['user_reg']) {
            $this->error('网站已关闭会员注册功能', url('index/index'));
        }

        return view();
    }

    /**
     * 找回密码
     * @author GuoLin
     * @createdate 2018-08-01
     *
     */
    public function recoveredPass()
    {

//        if (in_array(3, $this->yzmarr)) {
//            if (!captcha_check(input('code'))) {
//                return json(array('code' => 0, 'msg' => '验证码错误'));
//            }
//        }
        $account = $this->request->param('account');
        $code = $this->request->param('code');
        $newPass = $this->request->param('newPass');

        if (!$account) {
            return json(array('code' => 0, 'msg' => '账号不能为空'));
        }

        if (!$code) {
            return json(array('code' => 0, 'msg' => '短信验证码不能为空'));
        }

        if (!$newPass) {
            return json(array('code' => 0, 'msg' => '新秘密不能为空'));
        }

        if ($code != session('cmsCode')) {
            return json(array('code' => 0, 'msg' => '短信验证码错误'));
        }

        $userData = Db::name('user')->where('usermail|username|mobile', $account)->find();

        if (!$userData) {
            return json(array('code' => 0, 'msg' => '该手机号还未注册'));
        }

        $newPass = md5(md5($newPass));

        $result = Db::name('user')->where('id', $userData['id'])->update(['password' => $newPass]);

        if ($result !== false) {

            session('cmsCode', md5(time()));

            return json(['code' => 1, 'msg' => '密码修改成功，请重新登录']);
        } else {
            return json(['code' => 0, 'msg' => '密码修改失败，请稍后重试']);
        }

    }

    public function sms_login()
    {
        if ($this->request->isPost()) {
            $code = $this->request->param('code');
            $account = $this->request->param('account');

            if (!$account) {
                return json(['error_code' => 2, 'msg' => '手机号不能为空']);
            }

            if (!check_mobile_number($account)) {
                return json(['error_code' => 3, 'msg' => '手机号有误']);
            }

            if (!$code) {
                return json(['error_code' => 1, 'msg' => '验证码不能为空']);
            }

            if ($code != session('cmsCode')) {
                return json(array('error_code' => 5, 'msg' => '短信验证码错误'));
            }

            $user_data = Db::name('user')->where(['status' => 1])->where('mobile', $account)->find();

            if ($user_data) {
                autoUpGrade($user_data['id']);
                session('userstatus', $user_data['status']);
                session('usergrade', $user_data['usergrade']);
                session('userhead', $user_data['userhead']);
                session('username', $user_data['username']);
                session('userid', $user_data['id']);
                session('point', $user_data['point']);
                session('cmsCode', md5(time()));

                DB::name('user')->update(
                    [
                        'last_login_time' => time(),
                        'last_login_ip' => $this->request->ip(),
                        'id' => $user_data['id']
                    ]
                );

                return json(array('error_code' => 0, 'msg' => '登录成功'));
            } else {
                return json(array('error_code' => 6, 'msg' => '账号错误或已禁用'));
            }
        } else {
            return json(array('error_code' => 7, 'msg' => '非法请求'));
        }

    }


    public function resetmima()
    {
        $data = $this->request->param();
        $n = Db::name('user')->where('id', $data['mod'])->find();
        if (md5($n['salt'] . $n['id'] . $n['usermail']) == $data['id']) {

            $this->assign('userid', $n['id']);
            $this->assign('username', $n['username']);
            return view();
        } else {
            $this->error('非法操作', url('login/forget'));
        }

    }


    public function forget()
    {

        if (request()->isPost()) {

            $datan = $this->request->param();

            if (in_array(3, $this->yzmarr)) {
                if (!captcha_check(input('code'))) {
                    return json(array('code' => 0, 'msg' => '验证码错误'));
                }
            }

            $n = Db::name('user')->where('usermail', $datan['usermail'])->find();

            if (empty($n) || ($n['status'] != 2 && $n['status'] != 5)) {
                return json(array('code' => 0, 'msg' => '邮箱未激活或邮箱未注册'));
            } else {
                $data['email'] = $n['usermail'];
                $data['title'] = '找回密码';
                $str = md5($n['salt'] . $n['id'] . $n['usermail']);
                $data['body'] = 'http://' . $_SERVER['HTTP_HOST'] . url('login/resetmima') . '?mod=' . $n['id'] . '&id=' . $str;
                asyn_sendmail($data);
                return json(array('code' => 200, 'msg' => '邮件已发送，请到邮箱进行查收'));
            }
        } else {


        }
        return view();

    }

    public function logout()
    {
        session("userstatus", NULL);
        session("userid", NULL);
        session("username", NULL);
        session("usermail", NULL);
        session("kouling", NULL);
        session("usergrade", NULL);

        cookie('sys_key', null);
        return json(array('code' => 200, 'msg' => '退出成功'));
        //  return NULL;
    }
}