<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Db;
use \app\xgy_api\exception\BaseException as Exception;
use \app\common\enum\UserRoleEnum;
use \app\common\enum\AccountTypeEnum;
use \app\common\enum\TableName;
use \app\common\service\Vercode;

class User extends Base
{

    // 校验参数规则数组
    protected $validate_param_rules = [
        'id' => ['ID', 'number'],
        'phone' => ['手机号', 'require|isMobile'],
        'password' => ['密码', 'require|length:6,16'],
        'password2' => ['重复密码', 'require|confirm:password'],
        'verifyCode' => ['验证码', 'require|verificationVercode'],
        'is_register' => ['是否注册操作', 'boolean', false]
    ];

    /**
     * 账号登录
     * @throws
     */
    public function login()
    {
        $params = $this->validateRequestParams(['phone', 'password']);

        $user_info = Db::name(TableName::USER)
            ->field('id,name, status, role_id, authentication, account_type, sex,id_card,cmp_id,org_id')
            ->where([
                'phone' => $params['phone'],
                'password' => password_hash_tp($params['password'])
            ])
            ->find();
        if (empty($user_info)) {
            throw new Exception('phone or passrod error', 105);
        }


        $this->checkUserStatus($user_info['status']);

        if ($user_info['role_id'] != UserRoleEnum::COLLECTION_USR) {
            throw new Exception('this user not is collertor', 108);
        }

        if ($user_info['account_type'] != AccountTypeEnum::SERVICE) {
            throw new Exception('this user not is service', 108);
        }

        // 支持同一账号多次登录， 使用同一token
        if( ($token = $this->getTokenByDatabase($user_info['id'])) ){
            try{
                $this->checkToken($token);
            }catch (Exception $e){
                $token = $this->setToken($user_info['id']);
            }
        }else{
            $token = $this->setToken($user_info['id']);
        }

        unset($user_info['id']);

        // 添加项目中可能用到的全局用户信息
        // 获取用户所在公司名称
        $com_info = Db::name(TableName::COMPANY)
            ->field('cmp_name')
            ->where('id', '=', $user_info['cmp_id'])
            ->find();
        if ($com_info) {
            $user_info['cmp_name'] = $com_info['cmp_name'];
        }
        // 获取用户所在组名称
        $org_info = Db::name(TableName::ORGANIZATION)
            ->field('org_name')
            ->where('id', '=', $user_info['org_id'])
            ->find();
        if ($org_info) {
            $user_info['org_name'] = $org_info['org_name'];
        }
        $user_info['phone'] = $params['phone'];
        return ToApiFormat('login success', compact('token', 'user_info'));
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