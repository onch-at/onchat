<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\Index as IndexService;
use think\captcha\facade\Captcha;
use think\Response;

class Index
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        dump(null ?? 666);
    }

    /**
     * 检测用户名是否可用.
     *
     * @param string $username
     *
     * @return Result
     */
    public function checkUsername(string $username): Result
    {
        return $this->service->checkUsername($username);
    }

    /**
     * 验证邮箱是否可用.
     *
     * @param string $email
     *
     * @return Result
     */
    public function checkEmail(string $email): Result
    {
        return $this->service->checkEmail($email);
    }

    /**
     * 发送邮箱验证码
     * 验证码10分钟内有效，1分钟内不允许重复发送
     *
     * @param string $email
     *
     * @return Result
     */
    public function sendEmailCaptcha(string $email): Result
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
