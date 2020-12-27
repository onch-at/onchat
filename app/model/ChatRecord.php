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
     * 选择聊天室
     *
     * @param integer $chatroomId 房间号
     * @return ChatRecord
     */
    public static function opt(int $chatroomId)
    {
        $model = new static();
        $num = 1;

        if ($chatroomId > 1999) {
            $num = substr((string) $chatroomId, 0, 1);
        }

        $model->setSuffix('_' . $num . '_' . $chatroomId % 100);

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
