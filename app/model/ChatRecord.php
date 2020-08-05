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
    /**
     * 选择聊天室
     *
     * @param integer|string $chatroomId 房间号
     * @return ChatRecord
     */
    public static function opt($chatroomId)
    {
        // TODO 如果房间ID超过了1000，则需要继续拓展
        $model = new static();
        $model->setSuffix('_1_' . $chatroomId % 100);

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
