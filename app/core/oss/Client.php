<?php

declare(strict_types=1);

namespace app\core\oss;

use OSS\OssClient;

class Client
{
    /** 实例 */
    private static $instance;
    /** Object Storage Service */
    public $client;

    public static $imageHeadersOptions = [
        OssClient::OSS_HEADERS => [
            'Cache-Control' => 'public, max-age=31536000',
        ]
    ];

    private function __construct()
    {
        $accessKeyId = config('oss.access_key_id');
        $accessKeySecret = config('oss.access_key_secret');
        $endpoint = config('oss.endpoint');

        $this->client = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    }

    private function __clone()
    {
    }

    /**
     * 获取实例
     *
     * @return Client
     */
    public static function getInstance(): Client
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 销毁实例
     *
     * @return void
     */
    public static function destroyInstance(): void
    {
        self::$instance = null;
    }

    /**
     * 获取根目录路径
     * 如果是开发模式，则根目录为dev/，否则为空字符串
     */
    public static function getRootPath(): string
    {
        return env('app_debug', false) ? 'dev/' : '';
    }

    /**
     * 获取	Bucket 域名
     *
     * @return string
     */
    public static function getDomain(): string
    {
        return config('oss.domain');
    }

    /**
     * 获取图片样式名：原图
     *
     * @return string
     */
    public static function getOriginalImgStylename(): string
    {
        return config('oss.img_stylename_original');
    }

    /**
     * 获取图片样式名：缩略图
     *
     * @return string
     */
    public static function getThumbnailImgStylename(): string
    {
        return config('oss.img_stylename_thumbnail');
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->client, $method], $args);
    }
}
