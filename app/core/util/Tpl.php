<?php

declare(strict_types=1);

namespace app\core\util;

class Tpl
{
    /**
     * 插值替换
     *
     * @param string $tpl 模板
     * @param array $kv 键值对
     * @return string
     */
    public static function replace(string $tpl, array $kv): string
    {
        foreach ($kv as $key => $value) {
            $tpl = preg_replace("/\{\{\s*({$key})\s*\}\}/",  $value, $tpl);
        }
        return $tpl;
    }
}
