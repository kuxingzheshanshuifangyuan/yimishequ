<?php
return [
    // 默认输出类型
    'default_return_type'    => 'json',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',

    // 异常配置
    // 显示错误信息
    'show_error_msg'         => true,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '\app\api\exception\ExceptionHandler',

    'session'  => [
        // 是否自动开启 SESSION
        'auto_start'     => false,
    ],
];