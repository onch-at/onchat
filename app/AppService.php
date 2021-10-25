<?php

declare(strict_types=1);

namespace app;

use think\facade\Validate;
use think\Service;

/**
 * 应用服务类.
 */
class AppService extends Service
{
    public function boot()
    {
        // 扩展验证规则
        Validate::maker(function ($validate) {
            $validate->extend('has', function ($value, $rule, $data, $field) {
                return array_key_exists($field, $data);
            });
        });
    }
}
