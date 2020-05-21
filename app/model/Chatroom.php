<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 聊天室
 */
class Chatroom extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, ChatMember::class);
    }

    public function setCreateTime($value): int
    {
        return (int) $value * 1000;
    }

    public function setUpdateTime($value): int
    {
        return (int) $value * 1000;
    }
}
