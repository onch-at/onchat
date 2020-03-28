<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\common\handler\User as UserHandler;
use app\common\Result;
use app\common\util\Str;

class User extends BaseController
{
    /**
     * 用户登录
     *
     * @return Result
     */
    public function login(): Result
    {
        if (empty(input('post.username')) || empty(input('post.password'))) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $username = input('post.username/s');
        $password = input('post.password/s');
        return UserHandler::login($username, $password);
    }

    /**
     * 退出登录
     *
     * @return void
     */
    public function logout(): void
    {
        UserHandler::logout();
    }

    /**
     * 检测用户是否已经登录
     *
     * @return Result
     */
    public function checkLogin(): Result {
        return new Result(Result::CODE_SUCCESS, null, UserHandler::checkLogin());
    }

    /**
     * 用户注册
     *
     * @return Result
     */
    public function register(): Result
    {
        if (empty(input('post.username')) || empty(input('post.password')) || empty(input('post.captcha'))) { // 如果参数缺失
            return new Result(Result::CODE_ERROR_PARAM);
        }

        if (!captcha_check(input('post.captcha'))) {
            return new Result(Result::CODE_ERROR_PARAM, '验证码错误！');
        }

        $username = Str::trimAll(input('post.username/s'));
        $password = Str::trimAll(input('post.password/s'));
        return UserHandler::register($username, $password);
    }

    public function getChatrooms(): Result
    {
        return UserHandler::getChatrooms();
    }

    public function getChatList(): Result
    {
        return UserHandler::getChatList();
    }
}
