<?php

declare(strict_types=1);

namespace app\model;

use think\model\Pivot;
use app\model\User;
use app\model\Chatroom;

/**
 * 聊天成员
 */
class ChatMember extends Pivot
{
    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

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
}
