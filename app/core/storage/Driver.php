<?php

declare(strict_types=1);

namespace app\core\storage;

use app\contract\StorageDriver;

abstract class Driver implements StorageDriver
{
    /** 最大图片储存数 */
    protected const IMAGE_MAX_COUNT = 10;
}
