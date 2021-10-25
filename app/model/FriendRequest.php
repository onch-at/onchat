<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 好友申请.
 */
class FriendRequest extends Model
{
    // protected $convertNameToCamel = true;

    /** 状态：等待验证 */
    const STATUS_WAIT = 0;
    /** 状态：同意 */
    const STATUS_AGREE = 1;
    /** 状态：拒绝 */
    const STATUS_REJECT = 2;

    /** 申请人 */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /** 被申请人 */
    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
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
