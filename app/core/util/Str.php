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

    /**
     * 打乱字符串字符顺序，支持中文
     *
     * @param string $str
     * @return string
     */
    public static function shuffle(string $str): string
    {
        $arr = mb_str_split($str, 1, 'utf-8');
        shuffle($arr);
        return implode('', $arr);
    }
}
