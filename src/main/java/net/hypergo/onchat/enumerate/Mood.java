package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum Mood {
    /** 喜 */
    JOY,
    /** 怒 */
    ANGRY,
    /** 哀 */
    SORROW,
    /** 乐 */
    FUN
}
