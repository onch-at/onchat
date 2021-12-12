<?php

declare(strict_types=1);

namespace app\service;

use app\core\Result;
use app\core\storage\Storage;
use app\model\ChatMember as ChatMemberModel;
use app\model\Chatroom as ChatroomModel;

class Peer
{
    /**
     * 获取对方与自己的信息，用于请求 RTC.
     *
     * @param int $requesterId
     * @param int $chatroomId
     *
     * @return Result
     */
    public function call(int $requesterId, int $chatroomId): Result
    {
        $chatroom = ChatroomModel::find($chatroomId);

        // 如果没有这个聊天室或者这个聊天室不是私聊聊天室
        if (!$chatroom || $chatroom->type !== ChatroomModel::TYPE_PRIVATE_CHAT) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        $data = ChatMemberModel::join('user_info', 'user_info.user_id = chat_member.user_id')
            ->where('chat_member.chatroom_id', '=', $chatroom->id)->field([
                'chat_member.nickname',
                'user_info.avatar',
                'user_info.user_id AS id',
            ])->limit(2)->select();

        $storage = Storage::create();

        foreach ($data as $user) {
            $user->avatarThumbnail = $storage->getThumbnailUrl($user->avatar);
            $user->avatar          = $storage->getUrl($user->avatar);
        }

        // [requester, target]
        return Result::success([
            $data->where('id', '=', $requesterId)->pop(),
            $data->where('id', '<>', $requesterId)->pop(),
        ]);
    }
}
