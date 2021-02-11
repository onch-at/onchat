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
     * 通过生日获得年龄
     *
     * @param integer $birthday 生日时间戳
     * @return integer
     */
    public static function getAge(int $birthday): int
    {
        $birthday = getdate($birthday);
        $bYear = $birthday['year'];
        $bMonth = $birthday['mon'];
        $bDay = $birthday['mday'];

        $today = getdate();
        $tYear = $today['year'];
        $tMonth = $today['mon'];
        $tDay = $today['mday'];

        $age = $tYear - $bYear; // 获得岁数(未考虑月，日)

        // 如果当月还没到生日月 or 如果当月就是生日月，且当天仍未到生日
        return ($tMonth < $bMonth) || ($tMonth == $bMonth && $tDay < $bDay) ? --$age : $age;
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
