<?php

declare(strict_types=1);

namespace app\core\storage;

use app\contract\StorageDriver;
use app\core\Result;
use think\App;
use think\Config;
use think\Container;
use think\File;

/**
 * 文件存储器
 */
class Storage implements StorageDriver
{
    /** @var Driver */
    protected $driver;

    protected $config;

    public function __construct(App $app, Config $config)
    {
        $this->driver = $app->make($config->get('storage.driver'));
        $this->config = $config;
    }

    /**
     * 获取实例
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return Container::getInstance()->make(self::class);
    }

    /**
     * 获取根目录路径
     * 如果是开发模式，则根目录为dev/，否则为空字符串
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return env('app_debug') ? 'dev/' : '';
    }

    public function saveObject(string $path, string $file, string $data): Result
    {
        return $this->driver->saveObject($path, $file, $data);
    }

    public function saveImage(string $path, string $file, File $image): Result
    {
        return $this->driver->saveImage($path,  $file, $image);
    }

    public function delete(): Result
    {
        return $this->driver->delete();
    }

    public function getOriginalImageUrl(string $filename): string
    {
        return $this->driver->getOriginalImageUrl($filename);
    }

    public function getThumbnailImageUrl(string $filename): string
    {
        return $this->driver->getThumbnailImageUrl($filename);
    }
}
