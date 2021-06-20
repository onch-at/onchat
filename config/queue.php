<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'default'     => 'redis',
    'connections' => [
        'sync'     => [
            'type' => 'sync',
        ],
        'database' => [
            'type'       => 'database',
            'queue'      => 'default',
            'table'      => 'jobs',
            'connection' => null,
        ],
        'redis'    => [
            // 驱动方式
            'type'       => 'redis',
            // 服务器地址
            'host'       => env('redis.host', '127.0.0.1'),
            // 端口
            'port'       => env('redis.port', 6379),
            // 密码
            'password'   => env('redis.password', ''),
            // 长连接
            'persistent' => true,
            // 数据库号
            'select'     => env('redis.database', 0),
            'queue'      => 'default',
            'timeout'    => 0,
        ],
    ],
    'failed'      => [
        'type'  => 'none',
        'table' => 'failed_jobs',
    ],
];
