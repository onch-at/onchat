<?php

declare(strict_types=1);

namespace app\core\service;

use app\core\Result;
use app\model\User as UserModel;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;

class ChatInvitation
{
    public static function invitation(int $inviter, int $chatroomId, array $chatroomIdList)
    {
        // 找到好友ID列表
        $friendIdList = ChatMemberModel::whereIn('chatroom_id', function ($query) use ($inviter, $chatroomIdList) {
            // 找到正确的私聊聊天室ID（防止客户端乱传ID）
            $query->table('chat_member')->join('chatroom', 'chatroom.id = chat_member.chatroom_id')->where([
                ['chat_member.user_id', '=', $inviter],
                ['chatroom.id', 'IN', $chatroomIdList],
                ['chatroom.type', '=', ChatroomModel::TYPE_PRIVATE_CHAT],
            ])->field('chatroom.id');
        })->where('user_id', '<>', $inviter)->column('user_id');

        // 找找里面有没有人已经加入了聊天室
        $chatMemberIdList = ChatMemberModel::where([
            ['chatroom_id', '=', $chatroomId],
            ['user_id', 'IN', $friendIdList]
        ])->column('user_id');

        // 如果邀请的这批人里有人已经加入了聊天室
        if (count($chatMemberIdList)) {
            foreach ($friendIdList as $key => $value) {
                // 找到并删除
                if (in_array($value, $chatMemberIdList)) {
                    unset($friendIdList[$key]);
                }
            }
        }

        $dataList = [];

        return $friendIdList;
    }
}
