<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\service\ChatSession as ChatSessionService;

class ChatSession
{
    protected $service;

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
    public function stickyChatSession(int $id): Result
    {
        return $this->service->stickyChatSession($id);
    }

    /**
     * 取消置顶聊天会话
     *
     * @param integer $id
     * @return Result
     */
    public function unstickyChatSession(int $id): Result
    {
        return $this->service->unstickyChatSession($id);
    }

    /**
     * 将聊天会话设置为已读
     *
     * @param integer $id
     * @return Result
     */
    public function readedChatSession(int $id): Result
    {
        return $this->service->readedChatSession($id);
    }

    /**
     * 将聊天会话设置为未读
     *
     * @param integer $id
     * @return Result
     */
    public function unreadChatSession(int $id): Result
    {
        return $this->service->unreadChatSession($id);
    }

    /**
     * 隐藏会话
     *
     * @param integer $id
     * @return Result
     */
    public function hideChatSession(int $id): Result
    {
        return $this->service->hideChatSession($id);
    }
}