<?php

use Swoole\Table;
use think\swoole\websocket\socketio\Handler;

return [
    'http'       => [
        'enable'     => true,
        'host'       => env('server.host', '127.0.0.1'),
        'port'       => env('server.port', 9501),
        'worker_num' => swoole_cpu_num() * 2,
        'options'    => [
            'package_max_length' => 1024 * 1024 * 50
        ]
    ],
    'websocket'  => [
        'enable'        => true,
        'handler'       => Handler::class,
        'ping_interval' => 25000,
        'ping_timeout'  => 60000,
        'room'          => [
            'type'  => 'table',
            'table' => [
                'room_rows'   => 4096,
                'room_size'   => 2048,
                'client_rows' => 8192,
                'client_size' => 2048,
            ],
            'redis' => [
                'host'          => env('redis.host', '127.0.0.1'),
                'port'          => env('redis.port', 6379),
                'password'      => env('redis.password', ''),
                'select'        => env('redis.database', 0),
                'max_active'    => 3,
                'max_wait_time' => 5,
            ],
        ],
        'listen'        => [],
        'subscribe'     => [],
    ],
    'rpc'        => [
        'server' => [
            'enable'     => false,
            'host'       => '0.0.0.0',
            'port'       => 9000,
            'worker_num' => swoole_cpu_num() * 2,
            'services'   => [],
        ],
        'client' => [],
    ],
    'queue'      => [
        'enable'  => true,
        'workers' => [
            'default' => [
                'worker_num' => swoole_cpu_num(),
                'delay'      => 0,
                'sleep'      => 3,
                'tries'      => 1,
                'timeout'    => 60
            ]
        ],
    ],
    'hot_update' => [
        'enable'  => env('APP_DEBUG', false),
        'name'    => ['*.php'],
        'include' => [app_path()],
        'exclude' => [],
    ],
    //连接池
    'pool'       => [
        'db'    => [
            'enable'        => true,
            'max_active'    => 100,
            'max_wait_time' => 5,
        ],
        'cache' => [
            'enable'        => true,
            'max_active'    => 100,
            'max_wait_time' => 5,
        ],
        //自定义连接池
    ],
    'tables'     => [
        'user' => [
            'size'    => 8192,
            'columns' => [
                [
                    'name' => 'id',
                    'type' => Table::TYPE_INT,
                    'size' => 4
                ],
                [
                    'name' => 'username',
                    'type' => Table::TYPE_STRING,
                    'size' => 30
                ]
            ]
        ],
        'fd' => [
            'size'    => 8192,
            'columns' => [
                [
                    'name' => 'fd',
                    'type' => Table::TYPE_STRING,
                    'size' => 1024
                ],
            ]
        ],
        'throttle' => [
            'size'    => 8192,
            'columns' => [
                [
                    'name' => 'time',
                    'type' => Table::TYPE_INT,
                    'size' => 8
                ],
                [
                    'name' => 'count',
                    'type' => Table::TYPE_INT,
                    'size' => 1
                ],
            ]
        ]
    ],
    //每个worker里需要预加载以共用的实例
    'concretes'  => [],
    //重置器
    'resetters'  => [],
    //每次请求前需要清空的实例
    'instances'  => [],
    //每次请求前需要重新执行的服务
    'services'   => [],
];
