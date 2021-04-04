<?php

declare(strict_types=1);

namespace app\util;

use Mimey\MimeTypes;
use think\Container;

class File
{
    /**
     * 获取文件拓展名
     *
     * @param \think\File $file
     * @return string
     */
    public static function getExtension(\think\File $file): string
    {
        return Container::getInstance()->make(MimeTypes::class)->getExtension($file->getMime());
    }

    /**
     * 获取文件MIME
     *
     * @param string $filename
     * @return string
     */
    public static function getMime(string $filename): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        return finfo_file($finfo, $filename);
    }

    /**
     * 是否为动图
     *
     * @param string $filename
     * @return boolean
     */
    public static function isAnimation(string $filename): bool
    {
        return !!preg_match('/.(gif|apng)$/i', $filename);
    }

    // /**
    //  * 压缩图片并保存
    //  *
    //  * @param string $filename 原图地址
    //  * @param string $target 缩略图地址
    //  * @param integer $quality 质量
    //  * @return void
    //  */
    // public static function compressImage($filename, string $target, int $quality = 25)
    // {
    //     $image = new \Imagick($filename);
    //     $image->setImageFormat('jpg');
    //     $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
    //     $image->setImageCompressionQuality($quality);
    //     $image->stripImage();
    //     $image->writeImage($target);
    //     $image->clear();
    // }
}
