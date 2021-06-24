<?php

declare(strict_types=1);

namespace app\controller;

use app\model\ChatRequest;
use app\model\User as UserModel;
use app\model\UserInfo as UserInfoModel;
use app\service\Index as IndexService;
use think\Response;
use think\captcha\facade\Captcha;
use think\facade\Config;
use think\facade\Db;
use think\facade\Queue;

class Index
{
    protected $service;

    public function __construct(IndexService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        dump(UserModel::join('user_info', 'user.id = user_info.user_id')->whereOr([
            ['user_info.nickname', 'LIKE', '%hyperLife%'],
            ['user.username', 'LIKE', '%hyperLife%'],
            ['user.id', 'LIKE', '%hyperLife%'],
        ])->page(2, 5)->select()->toArray());

        dump(Db::getLastSql());
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
