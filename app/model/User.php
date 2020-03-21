<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

class User extends Model
{
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
