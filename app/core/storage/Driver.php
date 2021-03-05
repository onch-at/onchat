<?php

declare(strict_types=1);

namespace app\core\storage;

use app\contract\StorageDriver;

abstract class Driver implements StorageDriver
{
    /**
     * 获取根目录路径
     *
     * @return string
     */
    public function getRootPath(): string
    {
        return '';
    }
}
