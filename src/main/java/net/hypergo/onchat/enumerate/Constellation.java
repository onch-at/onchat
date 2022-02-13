package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum Constellation {
    /** 水瓶座 */
    AQUARIUS,
    /** 双鱼座 */
    PISCES,
    /** 白羊座 */
    ARIES,
    /** 金牛座 */
    TAURUS,
    /** 双子座 */
    GEMINI,
    /** 巨蟹座 */
    CANCER,
    /** 狮子座 */
    LEO,
    /** 处女座 */
    VIRGO,
    /** 天秤座 */
    LIBRA,
    /** 天蝎座 */
    SCORPIO,
    /** 射手座 */
    SAGITTARIUS,
    /** 摩羯座 */
    CAPRICORN
}
