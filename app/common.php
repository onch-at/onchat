<?php

declare(strict_types=1);
// 应用公共文件
use think\console\Output;

/**
 * 打印文本到控制台
 *
 * @param string $msg
 * @return void
 */
function output(string $msg)
{
    (new Output())->writeln($msg);
}
