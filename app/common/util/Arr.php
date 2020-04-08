<?php

declare(strict_types=1);

namespace app\common\util;

use think\helper\Str;

class Arr
{
    /**
     * 将一维数组的KEY转为小写驼峰形式
     * 下划线转驼峰(首字母小写)
     *
     * @param array $arr
     * @return array
     */
    public static function keyToCamel(array $arr): array
    {
        $temp = [];
        foreach ($arr as $k => $v) {
            $temp[Str::camel($k)] = $v;
        }
        return $temp;
    }

    /**
     * 将二维数组的KEY转为小写驼峰形式
     * 下划线转驼峰(首字母小写)
     *
     * @param array $arr
     * @return array
     */
    public static function keyToCamel2(array $arr): array
    {
        $temp = [];
        foreach ($arr as $item) {
            $temp[] = self::keyToCamel($item);
        }
        return $temp;
    }
}
