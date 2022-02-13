package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum ChatroomType {
    /** 群聊 */
    GROUP,
    /** 私聊 */
    PRIVATE,
    /** 单聊 */
    SINGLE
}
