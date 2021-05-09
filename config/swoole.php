<?php

use Swoole\Table;
use think\swoole\websocket\socketio\Handler;

return [
    'server'     => [
        'host'      => env('SWOOLE_HOST', '127.0.0.1'), // 监听地址
        'port'      => env('SWOOLE_PORT', 9501), // 监听端口
        'mode'      => SWOOLE_PROCESS, // 运行模式 默认为SWOOLE_PROCESS
        'sock_type' => SWOOLE_SOCK_TCP, // sock type 默认为SWOOLE_SOCK_TCP
        'options'   => [
            'pid_file'              => runtime_path() . 'swoole.pid',
            'log_file'              => runtime_path() . 'swoole.log',
            'daemonize'             => true,
            // Normally this value should be 1~4 times larger according to your cpu cores.
            'reactor_num'           => swoole_cpu_num() * 2,
            'worker_num'            => swoole_cpu_num() * 2,
            'task_worker_num'       => swoole_cpu_num(),
            'enable_static_handler' => true,
            'document_root'         => public_path(),
            'package_max_length'    => 128 * 1024 * 1024,
            'buffer_output_size'    => 128 * 1024 * 1024,
            'socket_buffer_size'    => 128 * 1024 * 1024
        ],
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
                'max_active'    => 10,
                'max_wait_time' => 5,
            ],
        ],
        'listen'        => [],
        'subscribe'     => [],
    ],
    'rpc'        => [
        'server' => [
            'enable'   => false,
            'port'     => 9000,
            'services' => [],
        ],
        'client' => [],
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
    'coroutine'  => [
        'enable' => true,
        'flags'  => SWOOLE_HOOK_ALL ^ SWOOLE_HOOK_CURL,
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
                    'type' => Table::TYPE_INT,
                    'size' => 4
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
