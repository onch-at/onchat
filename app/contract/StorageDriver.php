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
     * 获取根目录
     *
     * @return string
     */
    function getRootPath(): string;

    /**
     * 保存文件
     *
     * @param string $path 路径
     * @param string $file 文件名
     * @param File|string $data
     * @return Result
     */
    function save(string $path, string $file, $data): Result;

    function delete(): Result;

    /**
     * 清理目录下冗余文件
     *
     * @param string $path 目录
     * @param integer $count 要保留的文件数量
     * @return void
     */
    function clear(string $path, int $count): void;

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
