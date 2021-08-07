<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\ChatSession as ChatSessionService;

class ChatSession
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(ChatSessionService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取用户的聊天列表
     *
     * @return Result
     */
    public function getChatSessions(): Result
    {
        return $this->service->getChatSessions();
    }

    /**
     * 置顶聊天会话
     *
     * @param integer $id
     * @return Result
     */
    public function sticky(int $id): Result
    {
        return $this->service->sticky($id);
    }

    /**
     * 取消置顶聊天会话
     *
     * @param integer $id
     * @return Result
     */
    public function unsticky(int $id): Result
    {
        return $this->service->unsticky($id);
    }

    /**
     * 将聊天会话设置为已读
     *
     * @param integer $id
     * @return Result
     */
    public function readed(int $id): Result
    {
        return $this->service->readed($id);
    }

    /**
     * 将聊天会话设置为未读
     *
     * @param integer $id
     * @return Result
     */
    public function unread(int $id): Result
    {
        return $this->service->unread($id);
    }

    /**
     * 隐藏会话
     *
     * @param integer $id
     * @return Result
     */
    public function hide(int $id): Result
    {
        return $this->service->hide($id);
    }
}
