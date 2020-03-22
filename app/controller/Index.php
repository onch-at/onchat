<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use think\captcha\facade\Captcha;
use think\Response;

class Index extends BaseController
{
    public function index()
    {
        dump(empty(''));
    }

    /**
     * 验证码
     *
     * @return Response
     */
    public function captcha(): Response
    {
        return Captcha::create();
    }

}
