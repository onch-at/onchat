package net.hypergo.onchat.enumerate;

import com.fasterxml.jackson.annotation.JsonFormat;

@JsonFormat(shape = JsonFormat.Shape.NUMBER)
public enum MessageType {
    /** 系统消息 */
    SYSTEM,
    /** 文本 */
    TEXT,
    /** 富文本 */
    RICH_TEXT,
    /** 文字提示 */
    TIPS,
    /** 聊天邀请 */
    CHAT_INVITATION,
    /** 图片 */
    IMAGE,
    /** 语音 */
    VOICE
}
