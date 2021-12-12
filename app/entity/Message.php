<?php

declare(strict_types=1);

namespace app\entity;

class Message
{
    public $id;
    /** 消息发送者ID */
    public $userId;
    /** 消息发送者的聊天室昵称 */
    public $nickname;
    /** 消息发送者的头像缩略图 */
    public $avatarThumbnail;
    /** 消息对应的聊天室ID */
    public $chatroomId;
    /** 消息类型 */
    public $type;
    /** 消息内容 */
    public $data;
    /** 回复消息的消息记录ID */
    public $replyId;
    /** 回复的消息 */
    public $reply;
    /** 消息在客户端的临时ID */
    public $tempId;
    /** 消息创建时间 */
    public $createTime;

    public function __construct(?int $type = null)
    {
        $this->type = $type;
    }

    public function toArray(): array
    {
        $that       = $this;
        $that->data = (array) $this->data;

        return (array) $that;
    }
}
