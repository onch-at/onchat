package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum Gender {
    /** 男性 */
    MALE,
    /** 女性 */
    FEMALE,
    /** 保密 */
    SECRET
}
