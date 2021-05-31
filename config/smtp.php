<?php

// +----------------------------------------------------------------------
// | SMTP邮箱设置
// +----------------------------------------------------------------------

return [
    // SMTP主机
    'host'     => env('smtp.host', ''),
    // SMTP端口
    'port'     => env('smtp.port', 465),
    // SMTP安全连接
    'secure'   => env('smtp.secure', true),
    // SMTP用户名
    'username' => env('smtp.username', ''),
    // SMTP密码
    'password' => env('smtp.password', ''),
];
