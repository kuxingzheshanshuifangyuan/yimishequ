<?php
namespace app\api\exception;
use think\Exception;

/**
 * Class BaseException
 * 自定义异常类的基类
 */
class BaseException extends Exception
{
    public $code = 400;
    public $msg = 'invalid parameters';
    public $error_code = 999;
    
    public $shouldToClient = true;

    /**
     * 构造函数，接收一个关联数组
     * @param array $params 关联数组只应包含code、msg和error_code，且不应该是空值
     */
    public function __construct($msg = '', $error_code = 0, $code = 0)
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->error_code = $error_code;
    }
}

