<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\User as UserService;

class User
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return Result
     */
    public function login(string $username, string $password): Result
    {
        return $this->service->login($username, $password);
    }

    /**
     * 修改密码
     *
     * @param string $oldPassword 原密码
     * @param string $newPassword 新密码
     * @return Result
     */
    public function changePassword(string $oldPassword, string $newPassword): Result
    {
        return $this->service->changePassword($oldPassword, $newPassword);
    }

    /**
     * 通过用户名发送邮件
     *
     * @param string $username
     * @return Result
     */
    public function sendEmailCaptcha(string $username): Result
    {
        return $this->service->sendEmailCaptcha($username);
    }

    /**
     * 重置密码
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $captcha 验证码
     * @return Result
     */
    public function resetPassword(string $username, string $password, string $captcha): Result
    {
        return $this->service->resetPassword($username,  $password,  $captcha);
    }

    /**
     * 注册账户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $captcha 验证码
     * @return Result
     */
    public function register(string $username, string $password, string $email, string $captcha): Result
    {
        return $this->service->register($username,  $password,  $email,  $captcha);
    }

    /**
     * 上传用户头像
     *
     * @return Result
     */
    public function avatar(): Result
    {
        return $this->service->avatar();
    }

    /**
     * 保存用户信息
     *
     * @return Result
     */
    public function saveUserInfo(): Result
    {
        return $this->service->saveUserInfo();
    }

    /**
     * 绑定电子邮箱
     *
     * @param string $email 邮箱
     * @param string $captcha 验证码
     * @return Result
     */
    public function bindEmail(string $email, string $captcha): Result
    {
        return $this->service->bindEmail($email, $captcha);
    }

    /**
     * 获取用户
     *
     * @return Result
     */
    public function getUserById($id): Result
    {
        return $this->service->getUserById((int) $id);
    }

    /**
     * 获取私聊聊天室列表
     *
     * @return Result
     */
    public function getPrivateChatrooms(): Result
    {
        return $this->service->getPrivateChatrooms();
    }

    /**
     * 获取群聊聊天室列表
     *
     * @return Result
     */
    public function getGroupChatrooms(): Result
    {
        return $this->service->getGroupChatrooms();
    }

    /**
     * 模糊搜索用户
     *
     * @param string $keyword
     * @param integer $page
     * @return Result
     */
    public function search(string $keyword, int $page): Result
    {
        return $this->service->search($keyword, $page);
    }
}
