<?php

declare(strict_types=1);

namespace app\core;

use app\utils\Arr as ArrUtils;
use Swoole\Http\Status;
use think\Collection;
use think\Model;
use think\response\Json;

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
    const CODE_UNKNOWN_ERROR = -1;
    /** 参数错误 */
    const CODE_PARAM_ERROR = -2;
    /** 未授权 */
    const CODE_UNAUTHORIZED = -3;
    /** 授权过期 */
    const CODE_AUTH_EXPIRES = -4;
    /** 权限不足 */
    const CODE_NO_PERMISSION = -5;
    /** 访问频率过高 */
    const CODE_ACCESS_OVERCLOCK = -6;

    /** 响应信息预定义 */
    const CODE_PHRASES = [
        self::CODE_SUCCESS          => null,
        self::CODE_UNKNOWN_ERROR    => 'Unknown Error',
        self::CODE_PARAM_ERROR      => 'Parameter Error',
        self::CODE_UNAUTHORIZED     => 'Unauthorized',
        self::CODE_AUTH_EXPIRES     => 'Authorization Expires',
        self::CODE_NO_PERMISSION    => 'No Permission',
        self::CODE_ACCESS_OVERCLOCK => 'Access Overclock',
    ];

    private function __construct(int $code, string $msg = null, $data = null)
    {
        $this->code = $code;
        $this->msg = $msg ?? self::CODE_PHRASES[$code];

        if ($data instanceof Collection || $data instanceof Model) {
            $this->data = ArrUtils::keyToCamel($data->toArray());
        } elseif (is_array($data)) {
            $this->data = ArrUtils::keyToCamel($data);
        } else {
            $this->data = $data;
        }
    }

    public static function create(int $code, string $msg = null, $data = null): self
    {
        return new self($code, $msg, $data);
    }

    public static function unknown(string $msg = null): self
    {
        return new self(self::CODE_UNKNOWN_ERROR, $msg);
    }

    public static function unauth(string $msg = null): self
    {
        return new self(self::CODE_UNAUTHORIZED, $msg);
    }

    public static function success($data = null, string $msg = null): self
    {
        return new self(self::CODE_SUCCESS, $msg, $data);
    }

    public function msg($msg): self
    {
        $this->msg = $msg;

        return $this;
    }

    public function data($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->code === self::CODE_SUCCESS;
    }

    public function isFail(): bool
    {
        return $this->code !== self::CODE_SUCCESS;
    }

    public function toJson(): Json
    {
        $code = null;

        switch ($this->code) {
            case self::CODE_SUCCESS:
                $code = Status::OK;
                break;

            case self::CODE_UNAUTHORIZED:
                $code = Status::UNAUTHORIZED;
                break;

            case self::CODE_AUTH_EXPIRES:
            case self::CODE_NO_PERMISSION:
            case self::CODE_ACCESS_OVERCLOCK:
                $code = Status::FORBIDDEN;
                break;

            default:
                $code = Status::BAD_REQUEST;
        }

        return Json::create($this, 'json', $code);
    }
}
