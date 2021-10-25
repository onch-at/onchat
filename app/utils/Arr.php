<?php

declare(strict_types=1);

namespace app\utils;

use think\helper\Arr as ArrHelper;
use think\helper\Str;

class Arr extends ArrHelper
{
    /**
     * 将数组的KEY转为小写驼峰形式
     * 下划线转驼峰(首字母小写).
     *
     * @param array $arr
     *
     * @return array
     */
    public static function keyToCamel(array $arr): array
    {
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[is_string($k) ? Str::camel($k) : $k] = is_array($v) ? self::keyToCamel($v) : $v;
        }

        return $temp;
    }
}
