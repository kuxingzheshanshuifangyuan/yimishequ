<?php

namespace app\api\controller\v1;
use think\Db;

class Login extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
//        'id' => ['ID', 'number'],
        'phone' => ['手机号', 'require|isMobile'],
        'password' => ['密码', 'require|length:6,16'],
        'password2' => ['重复密码', 'require|confirm:password'],
        'verifyCode' => ['验证码', 'require|verificationVercode'],
        'is_register' => ['是否注册操作', 'boolean', false]
    ];


    public function login()
    {
        if ($this->request->isPost()) {

            $account  = $this->request->param('account');
            $password = $this->request->param('password');

            if (!$account) {
                return json(array('code' => 0, 'msg' => '账号不能为空'));
            }

            if (!$password) {
                return json(array('code' => 0, 'msg' => '密码不能为空'));
            }

            $userData = Db::name('user')
                ->field('id userid,username,point,money,mobile,sex')
                ->where('usermail|username|mobile', $account)
                ->find();

            if ($userData) {

                if($userData['status'] == 0){
                    return json(['code'=>0,'msg'=>'账号已被禁止登陆']);
                }

                if ($userData['password'] == md5($password . $userData['salt'])) {

                    // 支持同一账号多次登录， 使用同一token
                    if( ($token = $this->getTokenByDatabase($userData['id'])) ){
                        try{
                            $this->checkToken($token);
                        }catch (\Exception $e){
                            $token = $this->setToken($userData['id']);
                        }
                    }else{
                        $token = $this->setToken($userData['id']);
                    }

                    if ($userData['userhead'] == '') {
                        $userData['userhead'] = '/public/images/default.png';
                    }

                    Db::name('user')->update(
                        [
                            'last_login_time' => time(),
                            'last_login_ip' => $this->request->ip(),
                            'id' => $userData['id']
                        ]
                    );

                    return json(['code'=>1,'msg'=>'success','data'=>$userData,'token' =>$token]);

                } else {
                    return json(array('code' => 0, 'msg' => '密码错误'));
                }
            } else {
                return json(array('code' => 0, 'msg' => '该账号还为注册'));
            }
        }

    }


    /**
     * 设置手机密码
     * @throws
     */
    public function setPassword()
    {
        $params = $this->validateRequestParams(['phone', 'password', 'password2', 'verifyCode']);

        $user_info = Db::name(TableName::USER)
            ->where('phone', '=', $params['phone'])->field('id')->find();
        if (empty($user_info)) {
            throw new Exception('not found user by phone', 150);
        }

        $result = Db::name(TableName::USER)
            ->where('id', '=', $user_info['id'])
            ->update(['password' => password_hash_tp($params['password'])]);

        $this->setToken($user_info['id']);

        return ToApiFormat('set password success');
    }

    /**
     * 检测用户状态
     */
    public function checkUserStatus($status)
    {
        switch ($status) {
            case '0':
                throw new Exception('user not able', 106);
                break;

            case '1':
                return true;
                break;

            case '2':
                throw new Exception('user lock', 107);
                break;

            default:
                throw new Exception('not define user status : ' . $status, 108);
                break;
        }
    }

    /**
     * 发送验证码
     * @throws
     */
    public function sendVercode()
    {
        $params = $this->validateRequestParams(['phone', 'is_register']);
        try {
            $code = Vercode::sendVer($params['phone'], $params['is_register']);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        return ToApiFormat('vercode sended');
    }

}