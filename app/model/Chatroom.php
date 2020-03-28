<?php

declare(strict_types=1);

namespace app\model;

use think\Model;
use app\model\ChatRecord;

/**
 * 聊天室
 */
class Chatroom extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, ChatMember::class);
    }

    public function record()
    {
        return $this->hasMany(ChatRecord::class);
    }
}
