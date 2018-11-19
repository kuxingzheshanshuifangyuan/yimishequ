<?php
namespace app\index\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [

        'username'         => 'require|unique:user|length:2,20',
        'password'         => 'confirm:confirm_password|length:6,16',
        'confirm_password' => 'confirm:password',
        'mobile'           => 'require|'
//        'usermail'            => 'email|unique:user',
    ];

    protected $message = [
        'username.require'         => '请输入用户名',
    		'username.length'      => '用户名在2位到20位之间',
        'username.unique'          => '用户名已存在',
    		'usermail.unique'      => '邮箱已存在',
        'password.confirm'         => '两次输入密码不一致',
    		'password.length'      => '密码在6位到16位之间',
        'confirm_password.confirm' => '两次输入密码不一致',
        'usermail.email'           => '邮箱格式错误',
    ];
    
    protected $scene = [
    		'passwordedit'  =>  ['password','confirm_password'],
    ];
    
}