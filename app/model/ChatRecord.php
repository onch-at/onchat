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
    /** 定义默认的表后缀 */
    protected $suffix = '_1';

    /**
     * 选择聊天室
     *
     * @param integer|string $chatroomId 房间号
     * @return ChatRecord
     */
    public static function opt($chatroomId)
    {
        $model = new static();
        $model->setSuffix('_' . $chatroomId);

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
