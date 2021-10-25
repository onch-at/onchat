<?php

declare(strict_types=1);

namespace app\constant;

class MessageType
{
    /** 系统消息 */
    const SYSTEM = 0;
    /** 文本 */
    const TEXT = 1;
    /** 富文本 */
    const RICH_TEXT = 2;
    /** 文字提示 */
    const TIPS = 3;
    /** 群聊邀请 */
    const CHAT_INVITATION = 4;
    /** 图片 */
    const IMAGE = 5;
    /** 语音 */
    const VOICE = 6;
}
