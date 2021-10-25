<?php

namespace app\contract;

use app\core\Result;
use think\File;

/**
 * 储存器驱动器.
 */
interface StorageDriver
{
    /**
     * 获取根目录.
     *
     * @return string
     */
    public function getRootPath(): string;

    /**
     * 保存文件.
     *
     * @param string      $path 路径
     * @param string      $file 文件名
     * @param File|string $data
     *
     * @return Result
     */
    public function save(string $path, string $file, $data): Result;

    /**
     * 删除文件.
     *
     * @param string $filename 文件完整名
     *
     * @return Result
     */
    public function delete(string $filename): Result;

    /**
     * 文件是否存在.
     *
     * @param string $filename 文件完整名
     *
     * @return Result
     */
    public function exist(string $filename): Result;

    /**
     * 清理目录下冗余文件.
     *
     * @param string $path  目录
     * @param int    $count 要保留的文件数量
     *
     * @return void
     */
    public function clear(string $path, int $count): void;

    /**
     * 获取URL.
     *
     * @param string $filename 文件路径
     *
     * @return string
     */
    public function getUrl(string $filename): string;

    /**
     * 获取缩略图URL.
     *
     * @param string $filename 文件路径
     *
     * @return string
     */
    public function getThumbnailUrl(string $filename): string;
}
