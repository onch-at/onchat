<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\core\handler\User as UserHandler;
use app\core\Result;
use app\core\util\Str as StrUtil;

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
     * 如果已登录，则返回UserId；否则返回零
     *
     * @return Result
     */
    public function checkLogin(): Result
    {
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

        $username = StrUtil::trimAll(input('post.username/s'));
        $password = StrUtil::trimAll(input('post.password/s'));
        return UserHandler::register($username, $password);
    }

    /**
     * 获取用户ID
     *
     * @return Result
     */
    public function getUserId(): Result
    {
        return UserHandler::getUserId();
    }

    /**
     * 获取该用户下所有聊天室
     *
     * @return Result
     */
    public function getChatrooms(): Result
    {
        return UserHandler::getChatrooms();
    }

    /**
     * 获取用户的聊天列表
     *
     * @return Result
     */
    public function getChatList(): Result
    {
        return UserHandler::getChatList();
    }

    /**
     * 置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function sticky(int $id): Result
    {
        return UserHandler::sticky($id);
    }

    /**
     * 取消置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function unsticky(int $id): Result
    {
        return UserHandler::unsticky($id);
    }

    /**
     * 将聊天列表子项设置为已读
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function readed(int $id): Result
    {
        return UserHandler::readed($id);
    }

    /**
     * 将聊天列表子项设置为未读
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function unread(int $id): Result
    {
        return UserHandler::unread($id);
    }
}
