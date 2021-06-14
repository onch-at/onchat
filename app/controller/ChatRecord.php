<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\service\ChatRecord as ChatRecordService;

class ChatRecord
{
    protected $service;

    public function __construct(ChatRecordService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取聊天室消息记录
     *
     * @param integer $id 消息ID
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function getRecords(int $id, int $chatroomId): Result
    {
        return $this->service->getRecords($id, $chatroomId);
    }

    /**
     * 上传图片
     *
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function image(int $chatroomId): Result
    {
        return $this->service->image($chatroomId);
    }

    /**
     * 上传语音
     *
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public function voice(int $chatroomId): Result
    {
        return $this->service->voice($chatroomId);
    }
}
