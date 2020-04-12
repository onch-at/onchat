<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRecord as ChatRecordModel;
use app\core\util\Arr;
use app\model\User as UserModel;

class Chatroom
{
    /** 没有消息 */
    const CODE_NO_RECORD = 1;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_NO_RECORD => '没有消息'
    ];

    /** 每次查询的消息行数 */
    const MSG_ROWS = 15;

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

        $chatRecord = ChatRecordModel::where('chatroom_id', '=', $id);
        if ($chatRecord->count() === 0) { // 如果没有消息
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        // 如果msgId为0，则代表初次查询
        $data = $msgId == 0 ? $chatRecord : $chatRecord->where('id', '<', $msgId);

        $data = $data->order('id', 'desc')->limit(self::MSG_ROWS)->select()->each(function ($item) use ($nickname, $id, &$nicknameMap) {
            // TODO 查询用户头像
            $item['avatar_thumbnail'] = null;

            // 如果nicknameMap里面没有找到已经缓存的nickname
            if (!isset($nicknameMap[$item['user_id']])) {
                $nickname = ChatMemberModel::where('user_id', '=', $item['user_id'])->where('chatroom_id', '=', $id)->value('nickname');

                if (!$nickname) { // 如果在聊天室成员表找不到这名用户了（退群了），直接去用户表找
                    $nickname = UserModel::where('id', '=', $item['user_id'])->value('username');
                }

                $nicknameMap[$item['user_id']] = $nickname;
            }
            $item['nickname'] = $nicknameMap[$item['user_id']];
        })->toArray();

        return new Result(Result::CODE_SUCCESS, null, Arr::keyToCamel2($data));
    }
}
