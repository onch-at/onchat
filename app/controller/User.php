<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\common\handle\User as UserHandle;
use app\common\Result;
use app\common\util\Str;

class User extends BaseController
{
    public function login(): Result
    {
        if (!input('?post.username') || !input('?post.password')) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $username = Str::trimAll(input('post.username/s'));
        $password = Str::trimAll(input('post.password/s'));
        return UserHandle::login($username, $password);
    }
}
