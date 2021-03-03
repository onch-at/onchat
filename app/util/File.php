<?php

declare(strict_types=1);

namespace app\util;

class File
{
    /**
     * 获取图片后缀名
     *
     * @param \think\File $image
     * @return void
     */
    public static function getImageExt(\think\File $image)
    {
        return substr($image->getMime(), 6);
    }
}
