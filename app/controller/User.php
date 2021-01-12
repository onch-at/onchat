<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\BaseController;
use app\core\service\User as UserService;

class User extends BaseController
{
    /**
     * 用户登录
     *
     * @return Result
     */
    public function login(): Result
    {
        return UserService::login();
    }

    /**
     * 退出登录
     *
     * @return void
     */
    public function logout(): void
    {
        UserService::logout();
    }

    /**
     * 检测用户是否已经登录
     * 如果已登录，则返回User；否则返回false
     *
     * @return Result
     */
    public function checkLogin(): Result
    {
        return UserService::checkLogin();
    }

    /**
     * 用户注册
     *
     * @return Result
     */
    public function register(): Result
    {
        return UserService::register();
    }

    /**
     * 上传用户头像
     *
     * @return Result
     */
    public function avatar(): Result
    {
        return UserService::avatar();
    }

    /**
     * 保存用户信息
     *
     * @return Result
     */
    public function saveUserInfo(): Result
    {
        return UserService::saveUserInfo();
    }

    /**
     * 获取用户
     *
     * @return Result
     */
    public function getUserById($id): Result
    {
        return UserService::getUserById((int) $id);
    }

    /**
     * 获取用户ID
     *
     * @return Result
     */
    public function getUserId(): Result
    {
        return UserService::getUserId();
    }

    /**
     * 获取该用户下所有聊天室
     *
     * @return Result
     */
    public function getChatrooms(): Result
    {
        return UserService::getChatrooms();
    }

    /**
     * 获取用户的聊天列表
     *
     * @return Result
     */
    public function getChatSessions(): Result
    {
        return UserService::getChatSessions();
    }

    /**
     * 获取私聊聊天室列表
     *
     * @return Result
     */
    public function getPrivateChatrooms(): Result
    {
        return UserService::getPrivateChatrooms();
    }

    /**
     * 置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function stickyChatSession(int $id): Result
    {
        return UserService::stickyChatSession($id);
    }

    /**
     * 取消置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function unstickyChatSession(int $id): Result
    {
        return UserService::unstickyChatSession($id);
    }

    /**
     * 将聊天列表子项设置为已读
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function readedChatSession(int $id): Result
    {
        return UserService::readedChatSession($id);
    }

    /**
     * 将聊天列表子项设置为未读
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public function unreadChatSession(int $id): Result
    {
        return UserService::unreadChatSession($id);
    }
}
