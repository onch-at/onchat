<?php

declare(strict_types=1);

namespace app\model;

use think\Model;
use app\model\User;
use app\model\Chatroom;

/**
 * 聊天记录
 */
class ChatRecord extends Model
{
    protected $json = ['data'];

    /**
     * 通过聊天室ID选择相应的聊天记录表
     *
     * @param integer $chatroomId 房间号
     * @return ChatRecord
     */
    public static function opt(int $chatroomId)
    {
        $model = new static();
        // 拿到千位数（小于1000，千位数为1）
        $thousand = $chatroomId < 1000 ? 1 : substr((string) $chatroomId, 0, -3);

        $model->setSuffix('_' . $thousand . '_' . $chatroomId % 100);

        return $model;
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
    }

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
}
