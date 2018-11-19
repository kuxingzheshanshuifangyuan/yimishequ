<?php
namespace app\api\validate;

use think\Validate;
use \app\common\service\Vercode;

/**
 * Class BaseValidate
 * 验证类的基类
 */
class BaseValidate extends Validate
{
    //手机号的验证规则
    protected function isMobile($value)
    {
        $rule = '^1[0-9][0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 验证验证码是否正确
     */
    protected function verificationVercode($value,$other,$all_params)
    {
        if( Vercode::verification($all_params['phone'], $value)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取规则
     * @param $arr_fields array 请求数组
     * @param $request_params array 自定义规则列表，在对应请求控制器中
     * @return array
     */
    static public function getRule($arr_fields, $request_params)
    {
        $rule = [];
        foreach($arr_fields as $field){
            if(array_key_exists($field, $request_params)){
                $rule[ $field.'|'.$request_params[$field][0] ] = $request_params[$field][1];
            }
        }
        return $rule;
    }
}