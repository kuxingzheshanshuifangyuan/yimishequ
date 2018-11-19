<?php
namespace app\api\behavior;

use think\Request;

/**
 * 跨域行为类
 *
 * Class CSRF
 * @package app\common\behavior
 */
class CSRF
{
    public function appInit(&$params)
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: *");
        header('Access-Control-Allow-Methods: POST,GET');
        if(request()->isOptions()){
            exit('SUCCESS');
        }
    }
}
