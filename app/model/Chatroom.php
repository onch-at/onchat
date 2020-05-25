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
}
