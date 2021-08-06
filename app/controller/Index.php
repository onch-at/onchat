<?php

declare(strict_types=1);

namespace app\controller;

use Firebase\JWT\JWT;
use app\core\Redis;
use app\core\Result;
use app\facade\TokenService;
use app\model\ChatRequest;
use app\model\User as UserModel;
use app\model\UserInfo as UserInfoModel;
use app\service\Index as IndexService;
use app\service\Token;
use think\Response;
use think\captcha\facade\Captcha;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Validate;
use think\validate\ValidateRule;

class Index
{
    protected $service;

    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        dump(TokenService::instance()->cookie->get());
        // /** @var Token */
        // $tokenService = app(Token::class);
        // $payload = $tokenService->generate(1, ONCHAT_ACCESS_TOKEN_TTL);
        // $payload->usr = [
        //     'username' => 'HyperLife1119',
        // ];
        // $tokenService->issue(ONCHAT_ACCESS_TOKEN, $payload);
        // $tokenService->issue(ONCHAT_REFRESH_TOKEN, $tokenService->generate(1, ONCHAT_REFRESH_TOKEN_TTL));
    }

    /**
     * 检测用户名是否可用
     *
     * @param string $username
     * @return Result
     */
    public function checkUsername(string $username): Result
    {
        return $this->service->checkUsername($username);
    }

    /**
     * 验证邮箱是否可用
     *
     * @param string $email
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
