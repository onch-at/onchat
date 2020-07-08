<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use app\core\util\Arr as ArrUtil;
use think\facade\Db;
use app\core\util\Sql as SqlUtil;
use app\model\User as UserModel;
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
     * @param string $selfUsername 申请人的用户名
     * @param string $requestReason 申请原因
     * @param string $targetAlias 被申请人的别名
     * @return Result
     */
    public static function request(int $selfId, int $targetId, string $selfUsername, string $requestReason = null, string $targetAlias = null): Result
    {
        // 如果两人已经是好友关系，则不允许申请了
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

            $friendRequest->update_time = time() * 1000;
            $friendRequest->save();

            $friendRequest = $friendRequest->toArray();

            // TODO 把头像也返回
            $friendRequest['selfUsername'] = $selfUsername;
            $friendRequest['targetUsername'] = User::getUsernameById($targetId);

            return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($friendRequest));
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

        $friendRequest = $friendRequest->toArray();
        // TODO 把头像也返回
        $friendRequest['selfUsername'] = $selfUsername;
        $friendRequest['targetUsername'] = User::getUsernameById($targetId);

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($friendRequest));
    }

    /**
     * 获取所有正在等待验证的好友申请
     *
     * @param integer $userId 被申请人ID
     * @param string $username 被申请人用户名
     * @return Result
     */
    public static function getFriendRequests(int $userId, string $username): Result
    {
        $friendRequests = FriendRequestModel::where([
            'target_id' => $userId,
            'target_status' => FriendRequestModel::STATUS_WAIT
        ])->select()->toArray();

        $selfIdList = []; // 储存申请人的ID，用于一次性查询用户名

        foreach ($friendRequests as $key => $value) {
            // TODO 把头像也返回
            // 这里增加程序复杂度来减少SQL查询
            // $friendRequests[$key]['selfUsername'] = User::getUsernameById($value['self_id']);
            $friendRequests[$key]['targetUsername'] = $username;
            $selfIdList[] = $value['self_id'];
        }

        // 将用户名一次性查出
        $list = UserModel::where('id', 'IN', $selfIdList)->field('id, username')->select();
        // selfId => selfUsername
        $selfUsernameList = [];

        foreach ($list as $item) {
            $selfUsernameList[$item->id] = $item->username;
        }

        foreach ($friendRequests as $key => $value) {
            // TODO 把头像也返回
            $friendRequests[$key]['selfUsername'] = $selfUsernameList[$value['self_id']];
        }

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel2($friendRequests));
    }

    /**
     * 同意好友申请
     *
     * @param integer $friendRequestId 好友申请表的ID
     * @param integer $targetId 被申请人的ID
     * @param string $selfAlias 申请人的别名
     * @return Result
     */
    public static function agreeRequest(int $friendRequestId, int $targetId, string $selfAlias = null): Result
    {
        $friendRequest = FriendRequestModel::find($friendRequestId);

        // 确认被申请人的身份
        if ($friendRequest->target_id != $targetId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $timestamp = SqlUtil::rawTimestamp();

        $friendRequest->self_status = FriendRequestModel::STATUS_AGREE;
        $friendRequest->target_status = FriendRequestModel::STATUS_AGREE;
        $friendRequest->self_alias = $selfAlias;
        $friendRequest->update_time = $timestamp;
        $friendRequest->save();

        // 去找一下有没有自己申请加对方的申请记录
        // 场景：自己同意了对方的申请，但是自己之前也向对方提出好友申请
        $otherFriendRequest = FriendRequestModel::where([
            'self_id' => $targetId,
            'target_id' => $friendRequest->self_id
        ])->find();

        // 如果有，就把状态更新下
        if ($otherFriendRequest) {
            $otherFriendRequest->self_status = FriendRequestModel::STATUS_AGREE;
            $otherFriendRequest->target_status = FriendRequestModel::STATUS_AGREE;
            $otherFriendRequest->update_time = $timestamp;
            $otherFriendRequest->save();
        }

        $chatroomName = $friendRequest->self_id . ' & ' . $friendRequest->target_id;

        // 创建一个类型为私聊的聊天室
        $result = Chatroom::creatChatroom($chatroomName, ChatroomModel::TYPE_PRIVATE_CHAT);
        if ($result->code != Result::CODE_SUCCESS) {
            return $result;
        }

        $chatroomId = $result->data;

        // 启动事务
        Db::startTrans();
        try {
            $result = Chatroom::addChatMember($chatroomId, $friendRequest->self_id, $friendRequest->self_alias);
            if ($result->code != Result::CODE_SUCCESS) {
                return $result;
            }

            $result = Chatroom::addChatMember($chatroomId, $friendRequest->target_id, $friendRequest->target_alias);
            if ($result->code != Result::CODE_SUCCESS) {
                return $result;
            }

            return new Result(Result::CODE_SUCCESS, null, [
                'friendRequestId' => $friendRequest->id,
                'chatroomId'      => $chatroomId,
                'selfId'          => $friendRequest->self_id,
                'targetId'        => $friendRequest->target_id,
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN);
        }
    }

    /**
     * 判断二人是否为好友关系
     * 如果是好友关系，则返回私聊房间号；否则返回零
     *
     * @param integer $selfId
     * @param integer $targetId
     * @return integer
     */
    public static function isFriend(int $selfId, int $targetId): int
    {
        if ($selfId == $targetId) {
            return 0; // TODO 找到自己的单聊聊天室
        }
        // 找到二人的共同聊天室，且聊天室类型为私聊
        $chatroom = ChatroomModel::where('type', '=', ChatroomModel::TYPE_PRIVATE_CHAT)->where('id', 'IN', function ($query) use ($selfId, $targetId) {
            // 找到self跟target共同聊天室的ID
            $query->table('chat_member')->where('user_id', '=', $selfId)->where('chatroom_id', 'IN', function ($query) use ($targetId) {
                // 找到target所加入的所有聊天室的ID
                $query->table('chat_member')->where('user_id', '=', $targetId)->field('chatroom_id');
            })->field('chatroom_id');
        })->find();

        return empty($chatroom) ? 0 : $chatroom->id;
    }
}
