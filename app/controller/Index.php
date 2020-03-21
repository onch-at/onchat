<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use think\captcha\facade\Captcha;

class Index extends BaseController
{
    public function index()
    {
        // User::logout();
        User::register('HyperLife111999999', '12345678');
        // User::login('HyperLife1119', '12345678');

        dump(session('user_login'), User::checkLogin());
    }

    public function captcha()
    {
        return Captcha::create();
    }

    public function test() {
        dump(empty(null));
    }
}
