<?php

namespace app\common\service;

use think\Cache;
use think\Db;
use think\Loader;
use \app\api\exception\BaseException as Exception;

/**
 * 短信验证码控制器
 */
class Vercode
{

    // 验证码缓存 - 前缀
    const PREFIX = 'vercode_';

    // 验证码过期时间,秒为单位
    static private $expire = 30000; // 5分钟

    /**
     * 发送验证码给某手机
     * @throws
     */
    static public function sendVer($phone_num)
    {
        Loader::import('cmpp.Cmpp', EXTEND_PATH);


        $sms = new \Cmpp();

        $code = mt_rand(10000,99999);

        $content = '【一米社区】 您的验证码是'.$code.'。如非本人操作，请忽略本短信';

        $smsStatus = $sms->yunsms($phone_num,$content);

        if (!$smsStatus) {
            throw new Exception('验证码获取失败', 155);
        }

        return self::_setCode($phone_num,$code);
    }

    /**
     * 验证验证码是否正确
     * @throws
     */

    static public function verification($phone_num, $vercode)
    {
        if(empty($phone_num) || empty($vercode)){
            return false;
        }

        $userCode = Db::name('user_sms')->where(['phone'=>$phone_num])->find();
        if(!$userCode){
            return false;
        }

        if ($userCode['code'] == $vercode && (time() - strtotime($userCode['update_time'])) < self::$expire ) {
            return true;
        }

        return false;
    }

//    static public function verification($phone_num, $vercode)
//    {
//        if(empty($phone_num) || empty($vercode)){
//            return false;
//        }
//        if (self::_getVer($phone_num) == $vercode) {
//
////            self::_setVer($phone_num, null);
//            return true;
//        }
//        return false;
//    }

    /**
     * 设置smsCode 到数据库
     */
    static public function _setCode($phone,$code)
    {

        $user_result = Db::name('user_sms')->where('phone', '=',$phone )->field('phone')->find();
        if ($user_result) {
            // 更新用户smsCode
            $save_result = Db::name('user_sms')->where('phone', '=', $phone)->update([
                'code' => $code,
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
        } else {
            // 创建用户smsCode
            $save_result = Db::name('user_sms')->insert([
                'phone' => $phone,
                'code' => $code,
                'update_time' => date('Y-m-d H:i:s', time())
            ]);
        }

        if ($save_result) {
            return true;
        } else {
            throw new Exception("code set fail", 104);
        }

    }

    /**
     * 获取验证码
     * @throws
     */
    static public function _getVer($phone_num)
    {
        return Cache::get(self::PREFIX . $phone_num);
    }

    /**
     * 设置验证码
     */
    static private function _setVer($phone_num, $value = null)
    {
        if ($value == null) {
            Cache::rm(self::PREFIX . $phone_num);
            return true;
        }

        if (Cache::set(self::PREFIX . $phone_num, $value, self::$expire)) {
            return true;
        } else {
            return false;
        }
    }

}