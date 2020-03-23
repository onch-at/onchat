<?php

declare(strict_types=1);

namespace app\common\handler;

use app\model\User as UserModel;
use app\common\Result;

class User
{
    /** 用户名最小长度 */
    const USERNAME_LENGTH_MIN = 5;
    /** 用户名最大长度 */
    const USERNAME_LENGTH_MAX = 30;
    /** 用户密码最小长度 */
    const PASSWORD_LENGTH_MIN = 8;
    /** 用户密码最大长度 */
    const PASSWORD_LENGTH_MAX = 50;

    /** 成功 */
    const STATUS_SUCCESS = 0;
    /** 未知错误 */
    const STATUS_UNKNOWN_ERROR = 1;
    /** 用户已存在 */
    const STATUS_USER_EXIST = 2;
    /** 用户不存在 */
    const STATUS_USER_NOT_EXIST = 3;
    /** 用户密码错误 */
    const STATUS_PASSWORD_ERROR = 4;
    /** 用户名过短 */
    const STATUS_USERNAME_SHORT = 5;
    /** 用户名过长 */
    const STATUS_USERNAME_LONG = 6;
    /** 用户密码过短 */
    const STATUS_PASSWORD_SHORT = 7;
    /** 用户密码过长 */
    const STATUS_PASSWORD_LONG = 8;

    /** 响应消息预定义 */
    const MSG = [
        self::STATUS_SUCCESS        => '',
        self::STATUS_UNKNOWN_ERROR  => '未知错误',
        self::STATUS_USER_EXIST     => '用户已存在',
        self::STATUS_USER_NOT_EXIST => '用户不存在',
        self::STATUS_PASSWORD_ERROR => '密码错误',
        self::STATUS_USERNAME_SHORT => '用户名长度必须在' . self::USERNAME_LENGTH_MIN . '~' . self::USERNAME_LENGTH_MAX . '位字符之间',
        self::STATUS_USERNAME_LONG  => '用户名长度必须在' . self::USERNAME_LENGTH_MIN . '~' . self::USERNAME_LENGTH_MAX . '位字符之间',
        self::STATUS_PASSWORD_SHORT => '密码长度必须在' . self::PASSWORD_LENGTH_MIN . '~' . self::PASSWORD_LENGTH_MAX . '位字符之间',
        self::STATUS_PASSWORD_LONG  => '密码长度必须在' . self::PASSWORD_LENGTH_MIN . '~' . self::PASSWORD_LENGTH_MAX . '位字符之间',
    ];

    /** 用户登录SESSION名 */
    const SESSION_USER_LOGIN = 'user_login';

    /**
     * 注册账户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return Result
     */
    public static function register(string $username, string $password): Result
    {
        $result = self::checkUsername($username);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户名不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $result = self::checkPassword($password);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        if (!empty(self::getIdByUsername($username))) { // 如果已经有这个用户了
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::STATUS_USER_EXIST]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) { // 如果密码散列创建失败
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::STATUS_UNKNOWN_ERROR]);
        }
        $user = UserModel::create(['username' => $username, 'password' => $hash]);
        self::saveLoginStatus($user->id, $username, $hash); // 保存登录状态

        return new Result(Result::CODE_SUCCESS, '注册成功！即将跳转…');
    }

    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return Result
     */
    public static function login(string $username, string $password): Result
    {
        $result = self::checkUsername($username);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户名不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $result = self::checkPassword($password);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $info = self::getInfoByKey('username', $username, ['id', 'username', 'password']);
        if (empty($info)) { // 如果用户不存在
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::STATUS_USER_NOT_EXIST]);
        }

        if (!password_verify($password, $info['password'])) { // 如果密码错误
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::STATUS_PASSWORD_ERROR]);
        }

        self::saveLoginStatus($info['id'], $info['username'], $info['password']); // 保存登录状态

        return new Result(Result::CODE_SUCCESS, '登录成功！即将跳转…');
    }

    /**
     * 清除登录Session，退出登录
     *
     * @return void
     */
    public static function logout(): void
    {
        session(self::SESSION_USER_LOGIN, null);
    }

    /**
     * 设置用户登录Session，用于保存登录状态
     *
     * @param integer $id 用户ID
     * @param string $username 用户名
     * @param string $hashPassword 密码密文
     * @return void
     */
    public static function saveLoginStatus(int $id, string $username, string $hashPassword): void
    {
        session(self::SESSION_USER_LOGIN, [
            "id"       => $id,
            "username" => $username,
            "password" => $hashPassword,
        ]);
    }

    /**
     * 通过用户标识获取用户信息
     *
     * @param string $key 用户标识名
     * @param [type] $value 用户标识值
     * @param string|array $field 需要获取的字段名
     * @return array
     */
    public static function getInfoByKey(string $key, $value, $field): array
    {
        return UserModel::where($key, '=', $value)->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 通过用户ID获取用户名
     *
     * @param integer $id 用户ID
     * @return string|null
     */
    public static function getUsernameById(int $id): ?string
    {
        return UserModel::where('id', '=', $id)->value('username');
    }

    /**
     * 通过用户名获取用户ID
     *
     * @param string $username 用户名
     * @return integer
     */
    public static function getIdByUsername(string $username): ?int
    {
        return UserModel::where('username', '=', $username)->value('id');
    }

    /**
     * 检查用户是否已经登录/处于登录状态
     *
     * @return boolean
     */
    public static function checkLogin(): bool
    {
        $session = session(self::SESSION_USER_LOGIN);
        if (empty($session)) { // 如果没有登录的Session
            return false;
        }

        $password = self::getInfoByKey('id', $session['id'], 'password')['password'];
        if ($session['password'] !== $password) { // 如果密码错误
            return false;
        }

        return true;
    }

    /**
     * 检查用户名是否符合规范
     *
     * @param string $username
     * @return integer
     */
    public static function checkUsername(string $username): int
    {
        $length = mb_strlen($username, 'utf-8');

        if ($length < self::USERNAME_LENGTH_MIN) {
            return self::STATUS_USERNAME_SHORT;
        } elseif ($length > self::USERNAME_LENGTH_MAX) {
            return self::STATUS_USERNAME_LONG;
        } else {
            return self::STATUS_SUCCESS;
        }
    }

    /**
     * 检查用户密码时候符合规范
     *
     * @param string $password
     * @return integer
     */
    public static function checkPassword(string $password): int
    {
        $length = mb_strlen($password, 'utf-8');

        if ($length < self::PASSWORD_LENGTH_MIN) {
            return self::STATUS_PASSWORD_SHORT;
        } elseif ($length > self::PASSWORD_LENGTH_MAX) {
            return self::STATUS_PASSWORD_LONG;
        } else {
            return self::STATUS_SUCCESS;
        }
    }
}
