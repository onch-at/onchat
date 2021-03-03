<?php

namespace app\contract;

use app\core\Result;
use think\File;

/**
 * 储存器驱动器
 */
interface StorageDriver
{
    /**
     * 保存对象
     *
     * @param string $path 路径
     * @param string $file 文件名
     * @param string $data 对象数据
     * @return Result
     */
    function saveObject(string $path, string $file, string $data): Result;

    /**
     * 保存图片
     *
     * @param string $path 路径
     * @param string $file 文件名
     * @param File $image 图片文件
     * @return Result
     */
    function saveImage(string $path, string $file, File $image): Result;

    function delete(): Result;

    /**
     * 获取原图URL
     *
     * @param string $filename 文件路径
     * @return string
     */
    function getOriginalImageUrl(string $filename): string;

    /**
     * 获取缩略图URL
     *
     * @param string $filename 文件路径
     * @return string
     */
    function getThumbnailImageUrl(string $filename): string;
}
