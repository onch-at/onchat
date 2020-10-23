<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户
 */
class User extends Model
{
    // protected $convertNameToCamel = true;

    public function chatrooms()
    {
        return $this->belongsToMany(Chatroom::class, ChatMember::class);
    }

    public function chatMember()
    {
        return $this->hasMany(ChatMember::class);
    }

    public function chatRecord()
    {
        return $this->hasMany(ChatRecord::class);
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
}
