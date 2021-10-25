<?php

declare(strict_types=1);

namespace app\controller;

use app\core\Result;
use app\middleware\Jsonify;
use app\service\Chatroom as ChatroomService;

class Chatroom
{
    protected $service;

    protected $middleware = [Jsonify::class];

    public function __construct(ChatroomService $service)
    {
        $this->service = $service;
    }

    /**
     * 获取聊天室名称.
     *
     * @param int $id 聊天室ID
     *
     * @return Result
     */
    public function getName(int $id): Result
    {
        return $this->service->getName($id);
    }

    /**
     * 设置聊天室名称.
     *
     * @param int    $id   聊天室ID
     * @param string $name 名称
     *
     * @return Result
     */
    public function setName(int $id, string $name): Result
    {
        return $this->service->setName($id, $name);
    }

    /**
     * 设置群昵称.
     *
     * @param int    $id       聊天室ID
     * @param string $nickname 昵称
     *
     * @return Result
     */
    public function setNickname(int $id, string $nickname): Result
    {
        return $this->service->setNickname($id, $nickname);
    }

    /**
     * 获取聊天室.
     *
     * @param int $id 聊天室ID
     *
     * @return Result
     */
    public function getChatroom(int $id): Result
    {
        return $this->service->getChatroom($id);
    }

    /**
     * 获取群聊所有成员.
     *
     * @param int $id 聊天室ID
     *
     * @return Result
     */
    public function getChatMembers(int $id): Result
    {
        return $this->service->getChatMembers($id);
    }

    /**
     * 上传聊天室头像.
     *
     * @param int $id
     *
     * @return Result
     */
    public function avatar(int $id): Result
    {
        return $this->service->avatar($id);
    }

    /**
     * 模糊搜索聊天室.
     *
     * @param string $keyword
     * @param int    $page
     *
     * @return Result
     */
    public function search(string $keyword, int $page): Result
    {
        return $this->service->search($keyword, $page);
    }
}
