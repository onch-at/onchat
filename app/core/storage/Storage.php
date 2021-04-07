<?php

declare(strict_types=1);

namespace app\core\storage;

use app\contract\StorageDriver;
use app\core\Result;
use think\Config;
use think\Container;
use think\File;

/**
 * 文件存储器
 */
class Storage implements StorageDriver
{
    /** 最大图片储存数 */
    const IMAGE_MAX_COUNT = 5;

    /** @var Driver */
    protected $driver;
    protected $container;
    protected $config;

    public function __construct(Container $container, Config $config)
    {
        $default         = $config->get('storage.default');
        $this->driver    = $container->make($config->get("storage.stores.{$default}.driver"));
        $this->config    = $config;
        $this->container = $container;
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
     * 设置驱动
     *
     * @param string $driver 驱动类类路径
     * @return void
     */
    public function setDriver(string $driver)
    {
        $this->driver = $this->container->make($driver);
    }

    public function getRootPath(): string
    {
        return $this->driver->getRootPath();
    }

    public function save(string $path, string $file, $image): Result
    {
        return $this->driver->save($path,  $file, $image);
    }

    public function delete(string $filename): Result
    {
        return $this->driver->delete($filename);
    }

    public function exist(string $filename): Result
    {
        return $this->driver->exist($filename);
    }

    public function clear(string $path, int $count): void
    {
        $this->driver->clear($path, $count);
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
