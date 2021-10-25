<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 聊天记录.
 */
class ChatRecord extends Model
{
    protected $json = ['data'];

    // protected $jsonType = [
    //     'data->chatroomId'    =>    'int'
    // ];

    /**
     * 通过聊天室ID选择相应的聊天记录表.
     *
     * @param int $chatroomId 房间号
     *
     * @return ChatRecord
     */
    public static function opt(int $chatroomId)
    {
        $model = new static();
        $model->table = self::getTableNameById($chatroomId);

        return $model;
    }

    /**
     * 根据聊天室ID获得数据表名称.
     *
     * @param int $chatroomId
     *
     * @return string
     */
    public static function getTableNameById(int $chatroomId): string
    {
        return 'chat_record_'.$chatroomId % 10;
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
