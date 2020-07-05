<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use app\core\util\Arr as ArrUtil;
use think\facade\Db;
use app\core\util\Sql as SqlUtil;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\FriendRequest as FriendRequestModel;

class Friend
{
    /**
     * 申请添加好友
     *
     * @param integer $selfId 申请人的UserID
     * @param integer $targetId 被申请人的UserID
     * @param string $requestReason 申请原因
     * @param string $targetAlias 被申请人的别名
     * @return Result
     */
    public static function request(int $selfId, int $targetId, string $requestReason = null, string $targetAlias = null): Result
    {
        // 如果自己加自己，或者两人已经是好友关系，则不允许申请了
        if ($selfId == $targetId || self::isFriend($selfId, $targetId)) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $query = FriendRequestModel::where([
            'self_id'        => $selfId,
            'target_id'      => $targetId
        ]);

        $friendRequest = $query->where('target_status', '<>', FriendRequestModel::STATUS_AGREE)->find();
        // 如果之前已经申请过，但对方没有同意，就把对方的状态设置成等待验证
        if (!empty($friendRequest)) {
            $friendRequest->request_reason = $requestReason;
            $friendRequest->target_alias = $targetAlias;
            // 将双方的状态都设置为等待验证
            $friendRequest->self_status = FriendRequestModel::STATUS_WAIT;
            $friendRequest->target_status = FriendRequestModel::STATUS_WAIT;
            $friendRequest->save();

            return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($friendRequest->toArray()));
        }

        $timestamp = time() * 1000;

        $friendRequest = FriendRequestModel::create([
            'self_id'        => $selfId,
            'target_id'      => $targetId,
            'request_reason' => $requestReason,
            'target_alias'   => $targetAlias,
            'create_time'    => $timestamp,
            'update_time'    => $timestamp,
        ]);

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($friendRequest->toArray()));
    }

    /**
     * 判断二人是否为好友关系
     *
     * @param integer $selfId
     * @param integer $targetId
     * @return boolean
     */
    public static function isFriend(int $selfId, int $targetId): bool
    {
        // 找到二人的共同聊天室，且聊天室类型为私聊
        $chatroom = ChatroomModel::where('type', '=', ChatroomModel::TYPE_PRIVATE_CHAT)->where('id', 'IN', function ($query) use ($selfId, $targetId) {
            // 找到self跟target共同聊天室的ID
            $query->table('chat_member')->where('user_id', '=', $selfId)->where('chatroom_id', 'IN', function ($query) use ($targetId) {
                // 找到target所加入的所有聊天室的ID
                $query->table('chat_member')->where('user_id', '=', $targetId)->field('chatroom_id');
            })->field('chatroom_id');
        })->find();

        return !empty($chatroom);
    }
}
