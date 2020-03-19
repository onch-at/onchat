<?php

declare(strict_types=1);

namespace app\model;

use think\Model;

class User extends Model
{
    /** 用户名最小长度 */
    const USER_NAME_MIN_LENGTH = 5;
    /** 用户名最大长度 */
    const USER_NAME_MAX_LENGTH = 30;
    /** 用户密码最小长度 */
    const USER_PASSWORD_MIN_LENGTH = 8;
    /** 用户密码最大长度 */
    const USER_PASSWORD_MAX_LENGTH = 50;

    /** 成功 */
    const STATUS_SUCCESS = 0;
    /** 未知错误 */
    const STATUS_UNKNOWN_ERROR = 1;
    /** 用户已存在 */
    const STATUS_USER_EXIST = 2;
    /** 用户不存在 */
    const STATUS_USER_NOT_EXIST = 3;
    /** 用户密码错误 */
    const STATUS_USER_PASSWORD_ERROR = 4;
    /** 用户名过短 */
    const STATUS_USER_NAME_SHORT = 5;
    /** 用户名过长 */
    const STATUS_USER_NAME_LONG = 6;
    /** 用户密码过短 */
    const STATUS_USER_PASSWORD_SHORT = 7;
    /** 用户密码过长 */
    const STATUS_USER_PASSWORD_LONG = 8;

    /**
     * 注册账户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return integer
     */
    public static function register(string $username, string $password): int
    {
        if (!empty(User::getIdByUsername($username))) { // 如果已经有这个用户了
            return self::STATUS_USER_EXIST;
        }

        $result = User::checkUsername($username);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户名不符合规范
            return $result;
        }

        $result = User::checkPassword($password);
        if ($result !== self::STATUS_SUCCESS) { // 如果用户密码不符合规范
            return $result;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) { // 如果密码散列创建失败
            return self::STATUS_UNKNOWN_ERROR;
        }

        User::create(['username' => $username, 'password' => $hash]);
        // TODO 保存登录状态
        return self::STATUS_SUCCESS;
    }

    /**
     * 通过用户ID获取用户名
     *
     * @param integer $id 用户ID
     * @return string|null
     */
    public static function getUsernameById(int $id): ?string
    {
        return User::where('id', '=', $id)->value('username');
    }

    /**
     * 通过用户名获取用户ID
     *
     * @param string $username 用户名
     * @return integer
     */
    public static function getIdByUsername(string $username): ?int
    {
        return User::where('username', '=', $username)->value('id');
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

        if ($length < self::USER_NAME_MIN_LENGTH) {
            return self::STATUS_USER_NAME_SHORT;
        } elseif ($length > self::USER_NAME_MAX_LENGTH) {
            return self::STATUS_USER_NAME_LONG;
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

        if ($length < self::USER_PASSWORD_MIN_LENGTH) {
            return self::STATUS_USER_PASSWORD_SHORT;
        } elseif ($length > self::USER_PASSWORD_MAX_LENGTH) {
            return self::STATUS_USER_PASSWORD_LONG;
        } else {
            return self::STATUS_SUCCESS;
        }
    }
}
