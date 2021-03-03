<?php

declare(strict_types=1);

namespace app\util;

class Str
{
    const CODE = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

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

    /**
     * 获取随机验证码
     *
     * @param integer $length 验证码长度
     * @return string
     */
    public static function captcha(int $length): string
    {
        $code = str_split(self::CODE);
        $captcha = '';

        for ($i = 0; $i < $length; $i++) {
            $captcha .= $code[mt_rand(0, count($code) - 1)];
        }

        return $captcha;
    }

    /**
     * 插值替换
     *
     * @param string $tpl 模板
     * @param array $kv 键值对
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
