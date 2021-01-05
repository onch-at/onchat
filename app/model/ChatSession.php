<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 聊天会话
 */
class ChatSession extends Model
{
    protected $json = ['data'];

    const TYPE_CHATROOM = 0;
    const TYPE_CHATROOM_NOTICE = 1;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ID字段获取器
     *
     * @param string|integer $value
     * @return integer
     */
    public function getIdAttr($value): int
    {
        return (int) $value;
    }

    public function getStickyAttr($value): bool
    {
        return (bool) $value;
    }
}
