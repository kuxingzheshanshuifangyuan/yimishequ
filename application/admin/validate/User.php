<?php
namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username'         => 'require|unique:user|length:2,20',
        'password'         => 'confirm:confirm_password|length:6,16',
        'confirm_password' => 'confirm:password',
        'mobile'           => 'number|length:11',
       'usermail'            => 'email|unique:user',
        'status'           => 'require',
    ];

    protected $message = [
        'username.require'         => '请输入用户名',
            'username.length'      => '用户名在2位到20位之间',
        'username.unique'          => '用户名已存在',
        'password.confirm'         => '两次输入密码不一致',
            'password.length'      => '密码在6位到16位之间',
        'confirm_password.confirm' => '两次输入密码不一致',
        'mobile.number'            => '手机号格式错误',
        'mobile.length'            => '手机号长度错误',
        'usermail.email'              => '邮箱格式错误',
        'status.require'           => '请选择状态',
    		'usermail.unique'          => '邮箱已存在',
    ];
}