<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\Chat as ChatService;

class Chat
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(ChatService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取我收到的入群申请.
     *
     * @return Result
     */
    public function getReceiveRequests(): Result
    {
        return $this->service->getReceiveRequests();
    }

    /**
     * 通过请求ID获取我收到的入群请求
     *
     * @param int $id
     *
     * @return Result
     */
    public function getReceiveRequestById(int $id): Result
    {
        return $this->service->getReceiveRequestById($id);
    }

    /**
     * 已读所有入群请求
     *
     * @return Result
     */
    public function readed(): Result
    {
        return $this->service->readed();
    }

    /**
     * 获取我发送的所有入群申请.
     *
     * @return Result
     */
    public function getSendRequests(): Result
    {
        return $this->service->getSendRequests();
    }

    /**
     * 通过请求ID获取我发送的入群请求
     *
     * @param int $id
     *
     * @return Result
     */
    public function getSendRequestById(int $id): Result
    {
        return $this->service->getSendRequestById($id);
    }
}
