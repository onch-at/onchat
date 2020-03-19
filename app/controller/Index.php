<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\facade\Str;
use app\model\User;
use think\captcha\facade\Captcha;

class Index extends BaseController
{
    public function index()
    {
        // $user = User::register('HyperLife1119', '12345678');
        $user = User::getIdByUsername('HyperLife11119');
        dump(Str::test());
    }

    public function captcha()
    {
        return Captcha::create();
    }

    public function test() {
        dump(empty(null));
    }
}
