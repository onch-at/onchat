<?php

declare(strict_types=1);

namespace app\utils;

use think\helper\Str as StrHelper;

class Str extends StrHelper
{
    /**
     * 删除字符串中所有空格
     *
     * @param string $str
     *
     * @return string
     */
    public static function trimAll(string $str): string
    {
        return preg_replace('/[\s\n]+/', '', $str);
    }

    /**
     * 判断字符串是否为空（剔除空格，回车）.
     *
     * @param string $str
     *
     * @return bool
     */
    public static function isEmpty(string $str): bool
    {
        return self::length(self::trimAll($str)) === 0;
    }

    /**
     * 打乱字符串字符顺序，支持中文.
     *
     * @param string $str
     *
     * @return string
     */
    public static function shuffle(string $str): string
    {
        $arr = mb_str_split($str, 1, 'utf-8');
        shuffle($arr);

        return implode('', $arr);
    }

    /**
     * 获取随机验证码
     *
     * @param int $length 验证码长度
     *
     * @return string
     */
    public static function captcha(int $length): string
    {
        return parent::random($length);
    }

    /**
     * 插值替换.
     *
     * @param string $tpl 模板
     * @param array  $kv  键值对
     *
     * @return string
     */
    public static function assign(string $tpl, array $kv): string
    {
        foreach ($kv as $key => $value) {
            $tpl = preg_replace("/\{\{\s*({$key})\s*\}\}/", $value, $tpl);
        }

        return $tpl;
    }
}
