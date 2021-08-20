<?php

declare(strict_types=1);

namespace app\util;

class Date
{
    /**
     * 返回当前毫秒级时间戳
     *
     * @return integer
     */
    public static function now(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * 通过生日获得星座编号
     *
     * @param integer $birthday 生日时间戳
     * @return integer
     */
    public static function getConstellation(int $birthday): int
    {
        $birthday = getdate($birthday);
        $month = $birthday['mon'];
        $day = $birthday['mday'];

        // [
        //     "水瓶座", "双鱼座", "白羊座", "金牛座", "双子座", "巨蟹座",
        //     "狮子座", "处女座", "天秤座", "天蝎座", "射手座", "摩羯座"
        // ];
        $constellations = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        if ($day <= 22) {
            return $constellations[$month !== 1 ? $month - 2 : 11];
        }

        return $constellations[$month - 1];
    }
}
