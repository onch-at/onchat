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

        $this->client = new OssClient($accessKeyId, $accessKeySecret, $endpoint, true);
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

            case !ctype_print($data): // 如果是二进制数据
                $this->putObject($bucket, $path . $file, $data);
                break;

            case is_file($data):
                $this->uploadFile($bucket, $filename, $data);
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
            'max-keys' => 10,    // 最大数量
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

    public function exist(string $filename): Result
    {
        $exist = $this->doesObjectExist($this->getBucket(), $filename);
        return Result::success($exist);
    }

    public function getUrl(string $filename): string
    {
        return $this->signImageUrl($filename);
    }

    public function getThumbnailUrl(string $filename): string
    {
        return $this->signImageUrl($filename, $this->getThumbnailStylename());
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
     * 获取图片样式名：缩略图
     *
     * @return string
     */
    public function getThumbnailStylename(): string
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
    private function signImageUrl(string $object, ?string $stylename = null): string
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
