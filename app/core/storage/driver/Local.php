<?php

declare(strict_types=1);

namespace app\core\storage\driver;

use app\contract\StorageDriver;
use app\core\Result;
use think\File;
use think\Filesystem;

/**
 * 简易的本地 存储驱动
 */
class Local implements StorageDriver
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getRootPath(): string
    {
        return '';
    }

    public function save(string $path, string $file, $data): Result
    {
        $filename = $path . $file;

        switch (true) {
            case $data instanceof File:
                $result = $this->filesystem->putFileAs($path, $data, $file);

                if ($result === false) {
                    return Result::unknown();
                }
                break;

            case !ctype_print($data): // 如果是二进制数据
                $result = $this->filesystem->put($filename, $data);

                if ($result === false) {
                    return Result::unknown();
                }
                break;

            case is_file($data):
                $result = $this->filesystem->putFileAs($path, $data, $file);

                if ($result === false) {
                    return Result::unknown();
                }
                break;

            case is_string($data):
                $result = $this->filesystem->put($filename, $data);

                if ($result === false) {
                    return Result::unknown();
                }
                break;
        }

        return Result::success();
    }

    public function clear(string $path, int $count): void
    {
        $dir = array_filter($this->filesystem->listContents($path), function ($o) {
            return $o['type'] === 'file';
        });

        $num = count($dir);

        if ($num > $count) {
            // 按照时间进行升序
            usort($dir, function ($a, $b) {
                return $a['timestamp'] - $b['timestamp'];
            });

            $num -= $count;
            for ($i = 0; $i < $num; $i++) {
                $this->filesystem->delete($dir[$i]['path']);
            }
        }
    }

    function delete(string $filename): Result
    {
        $result = $this->filesystem->delete($filename);
        return Result::create($result ? Result::CODE_SUCCESS : Result::CODE_UNKNOWN_ERROR);
    }

    public function exist(string $filename): Result
    {
        return Result::success($this->filesystem->has($filename));
    }

    public function getUrl(string $filename): string
    {
        $type = $this->filesystem->getDefaultDriver();
        return $this->filesystem->getDiskConfig($type, 'url') . '/' . $filename;
    }

    public function getThumbnailUrl(string $filename): string
    {
        return $this->getUrl($filename);
    }
}
