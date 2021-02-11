<?php

declare(strict_types=1);

namespace app\controller;

use think\App;
use app\core\Result;
use app\service\Chat as ChatService;

class Chat extends BaseController
{
    protected $service;

    public function __construct(App $app, ChatService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取我收到的入群申请
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
     * @param integer $id
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
}
