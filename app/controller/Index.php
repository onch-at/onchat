<?php

declare(strict_types=1);

namespace app\controller;

use app\model\ChatRequest;
use app\service\Index as IndexService;
use think\App;
use think\Response;
use think\captcha\facade\Captcha;

class Index extends BaseController
{
    protected $service;

    public function __construct(App $app, IndexService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    public function index()
    {
        // $this->service->sendEmailCaptcha('1838491745@qq.com');
    }

    /**
     * 发送邮箱验证码
     * 验证码10分钟内有效，1分钟内不允许重复发送
     *
     * @param string $email
     * @return Result
     */
    public function sendEmailCaptcha(string $email)
    {
        return $this->service->sendEmailCaptcha($email);
    }

    /**
     * 验证码
     *
     * @return Response
     */
    public function imageCaptcha(): Response
    {
        return Captcha::create();
    }
}
