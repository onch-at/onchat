<?php

declare(strict_types=1);

namespace app\core\service;

use app\core\Result;
use app\core\util\Arr;
use app\model\User as UserModel;
use app\core\util\Sql as SqlUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;

class ChatInvitation
{
    /** 群聊人数已满 */
    const CODE_PEOPLE_NUM_FULL = 1;

    /**
     * 邀请好友入群
     *
     * @param integer $inviter 邀请人ID
     * @param integer $chatroomId 邀请进入的群聊ID
     * @param array $chatroomIdList 受邀人的私聊聊天室ID列表
     * @return Result
     */
    public static function invite(int $inviter, int $chatroomId, array $chatroomIdList): Result
    {
        // 找到这个聊天室
        $chatroom = ChatroomModel::join('chat_member', 'chat_member.chatroom_id = chatroom.id')
            ->where([
                ['chatroom.id', '=', $chatroomId],
                ['chat_member.user_id', '=', $inviter],
                ['chat_member.role', '=', ChatMemberModel::ROLE_HOST]
            ])->field('chatroom.*')->find();

        if (!$chatroom) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $peopleNum = ChatMemberModel::where('chatroom_id', '=', $chatroomId)->count();
        // 人数超出上限
        if ($peopleNum >= $chatroom->max_people_num) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满！');
        }

        // 找到正确的私聊聊天室ID（防止客户端乱传ID）
        $chatroomIdList = ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')->where([
            ['chat_member.user_id', '=', $inviter],
            ['chatroom.id', 'IN', $chatroomIdList],
            ['chatroom.type', '=', ChatroomModel::TYPE_PRIVATE_CHAT],
        ])->column('chatroom.id');

        return Result::success($chatroomIdList);
    }
}
