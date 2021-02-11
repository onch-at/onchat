<?php

declare(strict_types=1);

namespace app\controller;

use think\Response;
use app\facade\UserService;
use think\captcha\facade\Captcha;
use app\service\Index as IndexService;

class Index extends BaseController
{
    protected $service;

    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        dump(UserService::checkLogin());
    }

    public function sendMailCaptcha()
    {
        $this->service->sendMailCaptcha();
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
