<?php

declare(strict_types=1);

namespace app\core\storage\driver;

use OSS\OssClient;
use app\core\Result;
use app\core\storage\Driver;
use think\Config;
use think\File;

/**
 * 阿里云OSS对象存储服务 存储驱动
 */
class Oss extends Driver
{
    private $client;

    private $config;

    public function __construct(Config $config)
    {
        $accessKeyId = config('oss.access_key_id');
        $accessKeySecret = config('oss.access_key_secret');
        $endpoint = config('oss.endpoint');

        $this->client = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $this->config = $config;
    }

    public function saveObject(string $path, string $file, string $data): Result
    {
        $bucket = $this->getBucket();
        $this->putObject($bucket, $path . $file, $data);

        return Result::success();
    }

    public function saveImage(string $path, string $file, File $image): Result
    {
        $bucket = $this->getBucket();
        // 用于搜索用户所有历史头像
        $options = [
            'prefix'   => $path, // 文件路径前缀
            'max-keys' => 20,     // 最大数量
        ];

        $object =  $path . $file;

        // 上传到OSS
        $this->uploadFile($bucket, $object, $image->getRealPath());

        // 列举用户所有头像
        $objectList = $this->listObjects($bucket, $options)->getObjectList();

        $count = count($objectList);

        // 如果用户的头像大于10张
        if ($count > self::IMAGE_MAX_COUNT) {
            // 按照时间进行升序
            usort($objectList, function ($a, $b) {
                return strtotime($a->getLastModified()) - strtotime($b->getLastModified());
            });

            // 需要删除的OBJ
            $objects = [];

            $num = $count - self::IMAGE_MAX_COUNT;
            for ($i = 0; $i < $num; $i++) {
                $objects[] = $objectList[$i]->getKey();
            }

            // 把超过的删除
            $this->deleteObjects($bucket, $objects);
        }

        return Result::success();
    }

    public function delete(): Result
    {
        return Result::success();
    }

    public function getOriginalImageUrl(string $filename): string
    {
        return $this->signImageUrl($filename, $this->getOriginalImgStylename());
    }

    public function getThumbnailImageUrl(string $filename): string
    {
        return $this->signImageUrl($filename);
    }

    /**
     * 获取	Bucket 域名
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->config->get('oss.domain');
    }

    /**
     * 获取	Bucket 名字
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->config->get('oss.bucket');
    }

    /**
     * 获取图片样式名：原图
     *
     * @return string
     */
    public function getOriginalImgStylename(): string
    {
        return $this->config->get('oss.img_stylename_original');
    }

    /**
     * 获取图片样式名：缩略图
     *
     * @return string
     */
    public function getThumbnailImgStylename(): string
    {
        return $this->config->get('oss.img_stylename_thumbnail');
    }

    /**
     * 签名图像URL
     *
     * @param string $object
     * @param string|null $stylename 默认为缩略图样式
     * @return string
     */
    private function signImageUrl(string $object, string $stylename = null): string
    {
        return $this->signUrl($this->getBucket(), $object, 86400, 'GET', [
            OssClient::OSS_PROCESS => 'style/' . ($stylename ?: $this->getThumbnailImgStylename())
        ]);
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->client, $method], $args);
    }
}
