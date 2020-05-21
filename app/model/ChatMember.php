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

    public function setCreateTime($value): int
    {
        return (int) $value * 1000;
    }

    public function setUpdateTime($value): int
    {
        return (int) $value * 1000;
    }
}
