<?php

// 应用公共文件
declare(strict_types=1);

include_once 'constant.php';

use think\console\Output;

if (!function_exists('output')) {
    /**
     * 打印文本到控制台.
     *
     * @param string $msg
     *
     * @return void
     */
    function output(string $msg)
    {
        (new Output())->writeln($msg);
    }
}

if (!function_exists('resource_path')) {
    /**
     * 获取应用资源目录.
     *
     * @param string $path
     *
     * @return string
     */
    function resource_path(string $path = '')
    {
        return root_path('resource'.DIRECTORY_SEPARATOR.$path);
    }
}
