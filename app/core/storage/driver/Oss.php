<?php

declare(strict_types=1);

namespace app\core\storage\driver;

use OSS\OssClient;
use app\contract\StorageDriver;
use app\core\Result;
use think\Config;
use think\File;

/**
 * 阿里云OSS对象存储服务 存储驱动
 */
class Oss implements StorageDriver
{
    private $client;

    private $config;

    public function __construct(Config $config)
    {
        $accessKeyId     = $config->get('storage.stores.oss.access_key_id');
        $accessKeySecret = $config->get('storage.stores.oss.access_key_secret');
        $endpoint        = $config->get('storage.stores.oss.endpoint');

        $this->client = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $this->config = $config;
    }

    public function getRootPath(): string
    {
        return env('APP_DEBUG') ? 'dev/' : '';
    }

    public function save(string $path, string $file, $data): Result
    {
        $bucket = $this->getBucket();
        $filename =  $path . $file;

        switch (true) {
            case $data instanceof File:
                $this->uploadFile($bucket, $filename, $data->getRealPath());
                break;

            case is_string($data):
                $this->putObject($bucket, $path . $file, $data);
                break;
        }

        return Result::success();
    }

    public function clear(string $path, int $count): void
    {
        $bucket = $this->getBucket();

        $options = [
            'prefix'   => $path, // 文件路径前缀
            'max-keys' => 15,    // 最大数量
        ];

        // 列举用户所有头像
        $list = $this->listObjects($bucket, $options)->getObjectList();
        $num = count($list);
        // 如果文件冗余
        if ($num > $count) {
            // 按照时间进行升序
            usort($list, function ($a, $b) {
                return strtotime($a->getLastModified()) - strtotime($b->getLastModified());
            });

            // 需要删除的OBJ
            $objects = [];

            $num -= $count;
            for ($i = 0; $i < $num; $i++) {
                $objects[] = $list[$i]->getKey();
            }

            $this->deleteObjects($bucket, $objects);
        }
    }

    public function delete(string $filename): Result
    {
        $this->deleteObject($this->getBucket(), $filename);
        return Result::success();
    }

    public function getOriginalImageUrl(string $filename): string
    {
        return $this->signImageUrl($filename, $this->isAnimation($filename) ? null : $this->getOriginalImgStylename());
    }

    public function getThumbnailImageUrl(string $filename): string
    {
        return $this->signImageUrl($filename, $this->isAnimation($filename) ? null : $this->getThumbnailImgStylename());
    }

    /**
     * 是否为动图
     *
     * @param string $filename
     * @return boolean
     */
    public function isAnimation(string $filename): bool
    {
        return !!preg_match('/.(gif|apng)$/i', $filename);
    }

    /**
     * 获取	Bucket 域名
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->config->get('storage.stores.oss.domain');
    }

    /**
     * 获取	Bucket 名字
     *
     * @return string
     */
    public function getBucket(): string
    {
        return $this->config->get('storage.stores.oss.bucket');
    }

    /**
     * 获取图片样式名：原图
     *
     * @return string
     */
    public function getOriginalImgStylename(): string
    {
        return $this->config->get('storage.stores.oss.img_stylename_original');
    }

    /**
     * 获取图片样式名：缩略图
     *
     * @return string
     */
    public function getThumbnailImgStylename(): string
    {
        return $this->config->get('storage.stores.oss.img_stylename_thumbnail');
    }

    /**
     * 签名图像URL
     *
     * @param string $object
     * @param string|null $stylename
     * @return string
     */
    private function signImageUrl(string $object, string $stylename = null): string
    {
        $options = null;
        if ($stylename) {
            $options = [OssClient::OSS_PROCESS => 'style/' . $stylename];
        }

        return $this->signUrl($this->getBucket(), $object, 86400, 'GET', $options);
    }

    public function __call($method, $args)
    {
        return $this->client->$method(...$args);
    }
}
