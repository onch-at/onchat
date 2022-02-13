package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum ChatMemberRole {
    /** 普通 */
    NORMAL,
    /** 管理 */
    MANAGE,
    /** 主人 */
    HOST
}
