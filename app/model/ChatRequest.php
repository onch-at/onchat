<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 入群申请
 */
class ChatRequest extends Model
{
    protected $json = ['readed_list'];
    protected $jsonAssoc = true;

    /** 状态：等待 */
    const STATUS_WAIT = 0;
    /** 状态：同意 */
    const STATUS_AGREE = 1;
    /** 状态：拒绝 */
    const STATUS_REJECT = 2;
    /** 状态：删除 */
    const STATUS_DELETE = 3;
    /** 状态：忽略 */
    const STATUS_IGNORE = 4;


    /** 申请人 */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function chatroom()
    {
        return $this->belongsTo(Chatroom::class);
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
