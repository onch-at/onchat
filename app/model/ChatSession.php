<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 聊天会话.
 */
class ChatSession extends Model
{
    protected $json = ['data'];
    protected $jsonType = [
        'data->chatroomId' => 'int',
    ];

    /** 聊天室会话 */
    const TYPE_CHATROOM = 0;
    /** 聊天室通知会话 */
    const TYPE_CHATROOM_NOTICE = 1;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ID字段获取器.
     *
     * @param string|int $value
     *
     * @return int
     */
    public function getIdAttr($value): int
    {
        return (int) $value;
    }

    public function getStickyAttr($value): bool
    {
        return (bool) $value;
    }

    public function setStickyAttr($value): int
    {
        return (int) $value;
    }

    public function getVisibleAttr($value): bool
    {
        return (bool) $value;
    }

    public function setVisibleAttr($value): int
    {
        return (int) $value;
    }
}
