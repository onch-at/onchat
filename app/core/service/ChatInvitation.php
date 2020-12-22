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
use app\model\ChatInvitation as ChatInvitationModel;

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

        if ($peopleNum >= $chatroom->max_people_num) {
            return new Result(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满！', []);
        }

        $ossClient = OssClient::getInstance();

        $inviterInfo = User::getInfoByKey('id', $inviter, ['username', 'avatar']);
        $inviterAvatarThumbnail = $ossClient->signImageUrl($inviterInfo['avatar'], OssClient::getThumbnailImgStylename());

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

        $whereOr = [];
        // 拼接whereOr条件
        foreach ($friendIdList as $invitee) {
            $whereOr[] = ['invitee_id', '=', $invitee];
        }

        // 看看邀请的这批人中，有没有之前已经邀请过的
        // 如果有的话，就更新状态
        $chatInvitations = ChatInvitationModel::where([
            ['inviter_id', '=', $inviter],
            ['chatroom_id', '=', $chatroomId]
        ])->where(function ($query) use ($whereOr) {
            $query->whereOr($whereOr);
        })->select();

        $dataSet = [];
        $timestamp = time() * 1000;

        // 更新状态
        foreach ($chatInvitations as $item) {
            $dataSet[] = [
                'inviter_status' => ChatInvitationModel::STATUS_WAIT,
                'invitee_status' => ChatInvitationModel::STATUS_WAIT,
                'update_time' => $timestamp
            ] + $item->toArray();
        }

        // 已邀请过的人的ID列表
        $invitedList = $chatInvitations->column('invitee_id');

        foreach ($friendIdList as $invitee) {
            // 如果没有被邀请过，那就生成一条新记录
            if (!in_array($invitee, $invitedList)) {
                $dataSet[] = [
                    'inviter_id' => $inviter,
                    'invitee_id' => $invitee,
                    'chatroom_id' => $chatroomId,
                    'create_time' => $timestamp,
                    'update_time' => $timestamp,
                ];
            }
        }

        $model = new ChatInvitationModel;
        $data = $model->saveAll($dataSet)->toArray();

        foreach ($data as $key => $item) {
            $data[$key]['inviterUsername'] = $inviterInfo['username'];
            $data[$key]['inviterAvatarThumbnail'] = $inviterAvatarThumbnail;
        }

        return Result::success(Arr::keyToCamel($data));
    }
}
