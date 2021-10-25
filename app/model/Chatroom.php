<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 聊天室.
 */
class Chatroom extends Model
{
    // protected $convertNameToCamel = true;

    /** 聊天室类型：群聊 */
    const TYPE_GROUP_CHAT = 0;
    /** 聊天室类型：私聊 */
    const TYPE_PRIVATE_CHAT = 1;
    /** 聊天室类型：单聊（就是自己跟自己聊） */
    const TYPE_SINGLE_CHAT = 2;

    public function users()
    {
        return $this->belongsToMany(User::class, ChatMember::class);
    }

    public function chatRecord()
    {
        return $this->hasMany(ChatRecord::class);
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
}
