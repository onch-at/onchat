<?php

declare(strict_types=1);

namespace app\constant;

class SocketEvent
{
    /** 初始化 */
    const INIT = 'init';
    /** 卸载时 */
    const UNLOAD = 'unload';
    /** 接收到消息时 */
    const MESSAGE = 'message';
    /** 收到撤回消息 */
    const REVOKE_MESSAGE = 'revoke_message';
    /** 好友申请 */
    const FRIEND_REQUEST = 'friend_request';
    /** 同意好友申请 */
    const FRIEND_REQUEST_AGREE = 'friend_request_agree';
    /** 拒绝好友申请 */
    const FRIEND_REQUEST_REJECT = 'friend_request_reject';
    /** 创建聊天室 */
    const CREATE_CHATROOM = 'create_chatroom';
    /** 邀请好友入群 */
    const INVITE_JOIN_CHATROOM = 'invite_join_chatroom';
    /** 聊天申请（加群申请） */
    const CHAT_REQUEST = 'chat_request';
    /** 同意加群申请 */
    const CHAT_REQUEST_AGREE = 'chat_request_agree';
    /** 拒绝加群申请 */
    const CHAT_REQUEST_REJECT = 'chat_request_reject';
    /** RTC 相关数据 */
    const RTC_DATA = 'rtc_data';
    /** RTC 呼叫 */
    const RTC_CALL = 'rtc_call';
    /** RTC 挂断 */
    const RTC_HANG_UP = 'rtc_hang_up';
    /** RTC 繁忙 */
    const RTC_BUSY = 'rtc_busy';
}
