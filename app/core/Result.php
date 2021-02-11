<?php

declare(strict_types=1);

namespace app\core;

use app\util\Arr as ArrUtil;

class Result
{
    /** 状态码 */
    public $code;
    /** 响应消息 */
    public $msg;
    /** 响应数据 */
    public $data;

    /** 成功 */
    const CODE_SUCCESS = 0;
    /** 未知错误 */
    const CODE_ERROR_UNKNOWN = -1;
    /** 参数错误 */
    const CODE_ERROR_PARAM = -2;
    /** 权限不足 */
    const CODE_ERROR_NO_PERMISSION = -3;
    /** 访问频率过高 */
    const CODE_ERROR_HIGH_FREQUENCY = -4;

    /** 响应信息预定义 */
    const MSG = [
        self::CODE_SUCCESS              => null,
        self::CODE_ERROR_UNKNOWN        => '未知错误',
        self::CODE_ERROR_PARAM          => '参数错误',
        self::CODE_ERROR_NO_PERMISSION  => '权限不足',
        self::CODE_ERROR_HIGH_FREQUENCY => '访问频率过高',
    ];

    public function __construct(int $code, ?string $msg = null, $data = null)
    {
        $this->code = $code;
        $this->msg  = $msg ?: self::MSG[$code];
        $this->data = is_array($data) ? ArrUtil::keyToCamel($data) : $data;
    }

    public static function success($data = null, string $msg = null): Result
    {
        return new Result(self::CODE_SUCCESS, $msg ?: self::MSG[self::CODE_SUCCESS], $data);
    }
}
