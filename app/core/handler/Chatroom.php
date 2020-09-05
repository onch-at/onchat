<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use think\facade\Db;
use app\model\User as UserModel;
use app\core\util\Arr as ArrUtil;
use app\core\util\Sql as SqlUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRecord as ChatRecordModel;

class Chatroom
{
    /** 没有消息 */
    const CODE_NO_RECORD = 1;
    /** 别名过长 */
    const CODE_NAME_LONG = 2;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_NO_RECORD => '没有消息',
        self::CODE_NAME_LONG => '聊天室名字长度不能大于' . self::NAME_MAX_LENGTH . '位字符',
    ];

    /** 每次查询的消息行数 */
    const MSG_ROWS = 15;
    /** 群名最大长度 */
    const NAME_MAX_LENGTH = 30;

    /**
     * 获取聊天室名称
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public static function getName(int $id): Result
    {
        $chatroom = ChatroomModel::where('id', '=', $id)->field('name, type')->find();
        if (!$chatroom) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果聊天室类型是私聊的，则聊天室的名称需要返回私聊好友的Nickname
        if ($chatroom->type == ChatroomModel::TYPE_PRIVATE_CHAT) {
            $userId = User::getId();
            if (empty($userId)) {
                return new Result(Result::CODE_ERROR_NO_ACCESS);
            }

            // 找到自己
            $self = ChatMemberModel::where([
                'chatroom_id' => $id,
                'user_id'     => $userId
            ])->find();

            // 如果找不到，则代表自己没有进这个群
            if (empty($self)) {
                return new Result(Result::CODE_ERROR_NO_ACCESS);
            }

            // 查找加入了这个房间的另一个好友的nickname
            $name = ChatMemberModel::where('chatroom_id', '=', $id)->where('user_id', '<>', $userId)->value('nickname');

            if (empty($name)) {
                return new Result(Result::CODE_ERROR_UNKNOWN, '该私聊聊天室没有其他成员');
            }

            return new Result(Result::CODE_SUCCESS, null, $name);
        }

        return new Result(Result::CODE_SUCCESS, null, $chatroom->name);
    }

    public static function getChatroom(int $id): Result
    {
        $chatroom = ChatroomModel::where('id', '=', $id)->find();
        if (!$chatroom) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果聊天室类型是私聊的，则聊天室的名称需要返回私聊好友的Nickname
        if ($chatroom->type == ChatroomModel::TYPE_PRIVATE_CHAT) {
            $userId = User::getId();
            if (empty($userId)) {
                return new Result(Result::CODE_ERROR_NO_ACCESS);
            }

            // 找到自己
            $self = ChatMemberModel::where([
                'chatroom_id' => $id,
                'user_id'     => $userId
            ])->find();

            // 如果找不到，则代表自己没有进这个群
            if (empty($self)) {
                return new Result(Result::CODE_ERROR_NO_ACCESS);
            }

            // 查找加入了这个房间的另一个好友的nickname
            $name = ChatMemberModel::where('chatroom_id', '=', $id)->where('user_id', '<>', $userId)->value('nickname');

            if (empty($name)) {
                return new Result(Result::CODE_ERROR_UNKNOWN, '该私聊聊天室没有其他成员');
            }
            $chatroom->name = $name;
        }

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($chatroom->toArray()));
    }

    /**
     * 创建一个聊天室
     *
     * @param string $name 聊天室名称
     * @param integer $type 聊天室类型
     * @return Result
     */
    public static function creatChatroom(string $name = null, int $type = ChatroomModel::TYPE_GROUP_CHAT): Result
    {
        if ($name) {
            $name = trim($name);

            // 如果别名长度超出
            if (mb_strlen($name, 'utf-8') > self::NAME_MAX_LENGTH) {
                return new Result(self::CODE_NAME_LONG, self::MSG[self::CODE_NAME_LONG]);
            }
        }

        $timestamp = SqlUtil::rawTimestamp();

        // 创建一个聊天室
        $chatroom = ChatroomModel::create([
            'name'        => $name,
            'type'        => $type,
            'create_time' => $timestamp,
            'update_time' => $timestamp,
        ]);

        return new Result(Result::CODE_SUCCESS, null, $chatroom->id);
    }

    /**
     * 添加聊天成员
     *
     * @param integer $id 聊天室ID
     * @param integer $userId 用户ID
     * @param integer $nickname 室友昵称（好友昵称）
     * @param integer $role 角色
     * @return Result
     */
    public static function addChatMember(int $id, int $userId, string $nickname = null, int $role = 0): Result
    {
        $username = User::getUsernameById($userId);
        // 如果没有这个房间，或者没有这个用户，或者这个用户已经加入了这个房间
        if (
            empty(ChatroomModel::find($id)) ||
            empty($username) ||
            !empty(ChatMemberModel::where([
                'chatroom_id' => $id,
                'user_id'     => $userId
            ])->find())
        ) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        $timestamp = SqlUtil::rawTimestamp();

        ChatMemberModel::create([
            'chatroom_id' => $id,
            'user_id'     => $userId,
            'nickname'    => $nickname ?: $username,
            'role'        => $role,
            'unread'      => 1,
            'create_time' => $timestamp,
            'update_time' => $timestamp,
        ]);

        return new Result(Result::CODE_SUCCESS);
    }

    /**
     * 添加消息
     *
     * @param integer $userId 用户ID
     * @param array $msg 消息体
     * @return Result
     */
    public static function setMessage(int $userId, array $msg): Result
    {
        // 拿到当前用户在这个聊天室的昵称
        $nickname = User::getNicknameInChatroom($userId, $msg['chatroomId']);
        if (!$nickname) { // 如果拿不到就说明当前用户不在这个聊天室
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $result = Message::handler($msg);

        if ($result->code != Result::CODE_SUCCESS) {
            return $result;
        }

        $msg = $result->data;

        // 启动事务
        Db::startTrans();
        try {
            $timestamp = time() * 1000;

            $id = ChatRecordModel::opt($msg['chatroomId'])->json(['data'])->insertGetId([
                'chatroom_id' => $msg['chatroomId'],
                'user_id'     => $userId,
                'type'        => $msg['type'],
                'data'        => $msg['data'],
                'reply_id'    => $msg['replyId'] ?? null,
                'create_time' => $timestamp
            ]);

            ChatMemberModel::update([
                'is_show'     => true,
                'update_time' => $timestamp,
                // 如果是该用户的，则归零；
                // 如果不是该用户的，且小于100，则递增；否则直接100
                'unread'      => Db::raw('CASE WHEN user_id = ' . $userId . ' THEN 0 ELSE CASE WHEN unread < 100 THEN unread + 1 ELSE 100 END END')
            ], [
                'chatroom_id' => $msg['chatroomId']
            ]);

            $msg['id'] = $id;
            $msg['userId'] = $userId;
            $msg['nickname'] = $nickname;
            $msg['avatarThumbnail'] = OssClient::getDomain() . User::getInfoByKey('id', $userId, 'avatar')['avatar'] . OssClient::getThumbnailImgStylename();
            $msg['createTime'] = $timestamp;

            // 提交事务
            Db::commit();
            return new Result(Result::CODE_SUCCESS, null, $msg);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 查询消息记录
     * 按照消息ID查询，若消息ID为0，则为初次查询，否则查询传入的消息ID之前的消息
     *
     * @param integer $id 聊天室ID
     * @param integer $msgId 消息ID
     * @return Result
     */
    public static function getRecords(int $id, int $msgId): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 拿到当前用户在这个聊天室的昵称
        $nickname = User::getNicknameInChatroom($userId, $id);
        if (!$nickname) { // 如果拿不到就说明当前用户不在这个聊天室
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 用于缓存 user id => nickname
        $nicknameMap = [];
        $nicknameMap[$userId] = $nickname;
        // 用于缓存 user id => avatarThumbnail
        $avatarThumbnailMap = [];

        $chatRecord = ChatRecordModel::opt($id)->json(['data'])->where('chatroom_id', '=', $id);
        if ($chatRecord->count() === 0) { // 如果没有消息
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        // 初次查询的时候，顺带把未读消息数归零
        if ($msgId == 0) {
            ChatMemberModel::where([
                'user_id'     => $userId,
                'chatroom_id' => $id
            ])->update([
                'unread' => 0
            ]);
        }

        // 如果msgId为0，则代表初次查询
        $data = $msgId == 0 ? $chatRecord : $chatRecord->where('id', '<', $msgId);

        $domain = OssClient::getDomain();
        $stylename = OssClient::getThumbnailImgStylename();

        $records = [];
        foreach ($data->order('id', 'DESC')->limit(self::MSG_ROWS)->cursor() as $item) {
            $item = $item->toArray();

            // 如果nicknameMap里面没有找到已经缓存的nickname
            if (!isset($nicknameMap[$item['user_id']])) {
                $nickname = User::getNicknameInChatroom($item['user_id'], $id);

                if (!$nickname) { // 如果在聊天室成员表找不到这名用户了（退群了）但是她的消息还在，直接去用户表找
                    $nickname = User::getUsernameById($item['user_id']);
                }

                $nicknameMap[$item['user_id']] = $nickname;
            }

            // 如果avatarThumbnailMap里面没有找到已经缓存的avatarThumbnail
            if (!isset($avatarThumbnailMap[$item['user_id']])) {
                $avatarThumbnailMap[$item['user_id']] = $domain . User::getInfoByKey('id', $item['user_id'], 'avatar')['avatar'] . $stylename;
            }

            $item['nickname'] = $nicknameMap[$item['user_id']];
            $item['avatarThumbnail'] = $avatarThumbnailMap[$item['user_id']];
            $item['data'] = json_decode($item['data']);
            $records[] = $item;
        }

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel2($records));
    }

    /**
     * 撤回消息
     *
     * @param integer $id 房间号
     * @param integer $userId 用户ID
     * @param integer $msgId 消息ID
     * @return Result
     */
    public static function revokeMsg(int $id, int $userId, int $msgId): Result
    {
        $query = ChatRecordModel::opt($id)->where('id', '=', $msgId);
        $msg = $query->find();
        // 如果没找到这条消息
        if (!$msg) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        // 如果消息不是它本人发的 或者 已经超时了
        if ($msg['user_id'] != $userId || time() > $msg['create_time'] + 120000) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 启动事务
        Db::startTrans();
        try {
            // 如果消息删除失败
            if ($query->delete() == 0) {
                return new Result(Result::CODE_ERROR_UNKNOWN);
            }

            ChatMemberModel::update([
                'update_time' => SqlUtil::rawTimestamp(),
                // 如果消息不是该用户的，且未读消息数小于100，则递减（未读消息数最多储存到100，因为客户端会显示99+）
                'unread'      => Db::raw('CASE WHEN user_id != ' . $userId . ' AND unread BETWEEN 1 AND 100 THEN unread-1 ELSE unread END'),
            ], [
                'chatroom_id' => $id
            ]);

            // 提交事务
            Db::commit();
            return new Result(Result::CODE_SUCCESS, null, ['chatroomId' => $id, 'msgId' => $msgId]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }
}
