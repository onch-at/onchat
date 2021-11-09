<?php

declare(strict_types=1);

namespace app\utils;

use finfo as Finfo;
use Mimey\MimeTypes;
use think\Container;

class File
{
    /**
     * 获取文件拓展名.
     *
     * @param \think\File $file
     *
     * @return string
     */
    public static function getExtension(\think\File $file): string
    {
        return Container::getInstance()->make(MimeTypes::class)->getExtension($file->getMime());
    }

    /**
     * 获取文件MIME.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function getMime(string $filename): string
    {
        $finfo = new Finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($filename);
    }

    /**
     * 读取文件.
     *
     * @param string $filename
     *
     * @return string|false
     */
    public static function read(string $filename)
    {
        return file_get_contents($filename);
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
