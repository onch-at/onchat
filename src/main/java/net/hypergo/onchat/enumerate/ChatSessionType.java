package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum ChatSessionType {
    /** 聊天室 */
    CHATROOM,
    /** 聊天室通知 */
    CHATROOM_NOTICE
}
