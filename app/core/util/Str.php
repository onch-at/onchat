<?php

declare(strict_types=1);

namespace app\core\util;

class Str
{
    /**
     * 删除字符串中所有空格
     *
     * @param string $str
     * @return string
     */
    public static function trimAll(string $str): string
    {
        return preg_replace('/[\s|　]+/', '', $str);
    }
}
