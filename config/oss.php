<?php

// +----------------------------------------------------------------------
// | Aliyun Object Storage Service 阿里云对象存储服务设置
// +----------------------------------------------------------------------

return [
    // 阿里云账户AccessKeyId
    'access_key_id'           => env('oss.access_key_id', ''),
    // 阿里云账户AccessKeySecret
    'access_key_secret'       => env('oss.access_key_secret', ''),
    // 地域节点
    'endpoint'                => env('oss.endpoint', 'https://oss-cn-shanghai.aliyuncs.com'),
    // Bucket 域名（可填写自己绑定的域名）
    'domain'                  => env('oss.domain', 'https://x.oss-cn-shanghai.aliyuncs.com/'),
    // 包括自定义分隔符
    // 图片样式名：原图
    'img_stylename_original'  => env('oss.img_stylename_original', ''),
    // 图片样式名：缩略图
    'img_stylename_thumbnail' => env('oss.img_stylename_thumbnail', ''),
];
