<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\service\Chatroom as ChatroomService;
use think\App;

class Chatroom extends BaseController
{
    protected $service;

    public function __construct(App $app, ChatroomService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取聊天室名称
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getName(int $id): Result
    {
        return $this->service->getName($id);
    }

    /**
     * 设置聊天室名称
     *
     * @param integer $id 聊天室ID
     * @param string $name 名称
     * @return Result
     */
    public function setName(int $id, string $name): Result
    {
        return $this->service->setName($id, $name);
    }

    /**
     * 设置群昵称
     *
     * @param integer $id 聊天室ID
     * @param string $nickname 昵称
     * @return Result
     */
    public function setNickname(int $id, string $nickname): Result
    {
        return $this->service->setNickname($id, $nickname);
    }

    /**
     * 获取聊天室
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getChatroom(int $id): Result
    {
        return $this->service->getChatroom($id);
    }

    /**
     * 获取聊天室消息记录
     *
     * @param integer $id 聊天室ID
     * @param integer $msgId 消息ID
     * @return Result
     */
    public function getChatRecords(int $id, int $msgId): Result
    {
        return $this->service->getChatRecords($id, $msgId);
    }

    /**
     * 获取群聊所有成员
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getChatMembers(int $id): Result
    {
        return $this->service->getChatMembers($id);
    }

    /**
     * 上传聊天室头像
     *
     * @param integer $id
     * @return Result
     */
    public function avatar(int $id): Result
    {
        return $this->service->avatar($id);
    }

    /**
     * 上传图片
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function image(int $id): Result
    {
        return $this->service->image($id);
    }

    /**
     * 上传语音
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function voice(int $id): Result
    {
        return $this->service->voice($id);
    }
}
