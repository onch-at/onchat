<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户信息
 */
class UserInfo extends Model
{
    // protected $convertNameToCamel = true;

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
