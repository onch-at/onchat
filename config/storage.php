<?php

use app\core\storage\driver\Local;
use app\core\storage\driver\Oss;

// +----------------------------------------------------------------------
// | 文件储存设置
// +----------------------------------------------------------------------

return [
    'default' => env('storage.driver', 'oss'),
    'stores'  => [
        'oss' => [
            'driver'                  => Oss::class,
            // 阿里云账户AccessKeyId
            'access_key_id'           => env('oss.access_key_id', ''),
            // 阿里云账户AccessKeySecret
            'access_key_secret'       => env('oss.access_key_secret', ''),
            // 地域节点（可填写自己绑定的域名）
            'endpoint'                => env('oss.endpoint', 'https://oss-cn-shanghai.aliyuncs.com'),
            // Bucket
            'bucket'                  => env('oss.bucket', 'onchat'),
            // 包括自定义分隔符
            // 图片样式名：缩略图
            'img_stylename_thumbnail' => env('oss.img_stylename_thumbnail', 'thumbnail'),
        ],

        'local' => [
            'driver' => Local::class,
        ]
    ]
];
