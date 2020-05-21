<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
use app\core\util\Arr;
use app\model\User as UserModel;
use think\facade\Db;

class Chatroom
{
    /** 没有消息 */
    const CODE_NO_RECORD = 1;
    const CODE_MSG_LONG  = 2;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_NO_RECORD => '没有消息',
        self::CODE_MSG_LONG  => '文本消息长度过长'
    ];

    /** 每次查询的消息行数 */
    const MSG_ROWS = 15;
    /** 文本消息最长长度 */
    const MSG_MAX_LENGTH = 4096;

    /** 聊天记录表前缀 + chatroomId */
    const TABLE_PREFIX_CHAT_RECORD = 'chat_record_';

    /**
     * 获取聊天室名称
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public static function getName(int $id): Result
    {
        $name = ChatroomModel::where('id', '=', $id)->value('name');
        if (!$name) {
            return new Result(Result::CODE_ERROR_PARAM);
        }
        return new Result(Result::CODE_SUCCESS, null, $name);
    }

    /**
     * 添加聊天成员
     *
     * @param integer $id 聊天室ID
     * @param integer $userId 用户ID
     * @param integer $role 角色
     * @return Result
     */
    public static function addChatMember(int $id, int $userId, int $role = 0): Result
    {
        $username = User::getUsernameById($userId);
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

        ChatMemberModel::create([
            'chatroom_id' => $id,
            'user_id'     => $userId,
            'nickname'    => $username,
            'role'        => $role
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
        // TODO 仅在消息类型为文本的时候才判断
        if (mb_strlen($msg['content'], 'utf-8') > self::MSG_MAX_LENGTH) {
            return new Result(self::CODE_MSG_LONG, self::MSG[self::CODE_MSG_LONG]);
        }

        // 拿到当前用户在这个聊天室的昵称
        $nickname = ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $msg['chatroomId'])->value('nickname');
        if (!$nickname) { // 如果拿不到就说明当前用户不在这个聊天室
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // TODO 仅在消息类型为文本的时候才转化
        $content = htmlspecialchars($msg['content']);

        // 启动事务
        Db::startTrans();
        try {
            $time = time() * 1000;
            $id = Db::table(self::TABLE_PREFIX_CHAT_RECORD . $msg['chatroomId'])->insertGetId([
                'chatroom_id' => $msg['chatroomId'],
                'user_id'     => $userId,
                'type'        => $msg['type'],
                'content'     => $content,
                'reply_id'    => $msg['replyId'],
                'create_time' => $time
            ]);

            // 如果消息不是该用户的，且未读消息数小于100，则递增（未读消息数最多储存到100，因为客户端会显示99+）
            ChatMemberModel::update([
                'is_show' => true,
                'unread' => Db::raw('unread+1')
            ], [
                'chatroom_id' => $msg['chatroomId'],
                'unread'      => Db::raw('< 100'),
                'user_id'     => Db::raw('!= ' . $userId)
            ]);

            $msg['id'] = $id;
            $msg['content'] = $content;
            $msg['userId'] = $userId;
            $msg['nickname'] = $nickname;
            // TODO 查询用户头像
            $msg['avatarThumbnail'] = null;
            $msg['createTime'] = $time;

            // 提交事务
            Db::commit();
            return new Result(Result::CODE_SUCCESS, null, $msg);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN);
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
        $nickname = ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $id)->value('nickname');
        if (!$nickname) { // 如果拿不到就说明当前用户不在这个聊天室
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 用于缓存 user id => nickname
        $nicknameMap = [];
        $nicknameMap[$userId] = $nickname;

        $chatRecord = Db::table(self::TABLE_PREFIX_CHAT_RECORD . $id); // ::where('chatroom_id', '=', $id)
        if ($chatRecord->count() === 0) { // 如果没有消息
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        // 初次查询的时候，顺带把未读消息数归零
        if ($msgId == 0) {
            ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $id)->update([
                'unread' => 0
            ]);
        }

        // 如果msgId为0，则代表初次查询
        $data = $msgId == 0 ? $chatRecord : $chatRecord->where('id', '<', $msgId);

        $records = [];
        foreach ($data->order('id', 'desc')->limit(self::MSG_ROWS)->cursor() as $item) {
            // TODO 查询用户头像
            $item['avatarThumbnail'] = null;

            // 如果nicknameMap里面没有找到已经缓存的nickname
            if (!isset($nicknameMap[$item['user_id']])) {
                $nickname = ChatMemberModel::where('user_id', '=', $item['user_id'])->where('chatroom_id', '=', $id)->value('nickname');

                if (!$nickname) { // 如果在聊天室成员表找不到这名用户了（退群了），直接去用户表找
                    $nickname = UserModel::where('id', '=', $item['user_id'])->value('username');
                }

                $nicknameMap[$item['user_id']] = $nickname;
            }
            $item['nickname'] = $nicknameMap[$item['user_id']];
            $records[] = $item;
        }

        return new Result(Result::CODE_SUCCESS, null, Arr::keyToCamel2($records));
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
        $query = Db::table(self::TABLE_PREFIX_CHAT_RECORD . $id)->where('id', '=', $msgId);
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

            // 如果消息不是该用户的，且未读消息数小于100，则递减（未读消息数最多储存到100，因为客户端会显示99+）
            ChatMemberModel::update([
                'unread' => Db::raw('unread-1')
            ], [
                'chatroom_id' => $id,
                'unread'      => Db::raw('BETWEEN 1 AND 100'),
                'user_id'     => Db::raw('!= ' . $userId)
            ]);

            // 提交事务
            Db::commit();
            return new Result(Result::CODE_SUCCESS, null, ['chatroomId' => $id, 'msgId' => $msgId]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN);
        }
    }
}
