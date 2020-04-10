<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
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
    public static function getName(int $id): Result {
        $name = ChatroomModel::where('id', '=', $id)->value('name');
        if (!$name) {
            return new Result(Result::CODE_ERROR_PARAM);
        }
        return new Result(Result::CODE_SUCCESS, null, $name);
    }

    /**
     * 查询消息记录
     *
     * @param integer $id 聊天室ID
     * @param integer $page 页码
     * @return Result
     */
    public static function getRecords(int $id, int $page = 1): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        // 拿到当前用户在这个聊天室的昵称
        $nickname = ChatMemberModel::where('user_id', '=', $userId)->where('chatroom_id', '=', $id)->value('nickname');
        if (!$nickname) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $chatRecord = ChatroomModel::find($id)->chatRecord();
        if ($chatRecord->count() === 0) {
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        $data = $chatRecord->paginateX([
            'list_rows' => self::MSG_ROWS,
            'page' => $page,
        ])->each(function ($item) use ($userId, $nickname, $id) {
            // TODO 查询用户头像
            $item['avatar_thumbnail'] = null;

            // 如果这条消息不是该用户发的
            if ($item['user_id'] !== $userId) {
                $nickname = ChatMemberModel::where('user_id', '=', $item['user_id'])->where('chatroom_id', '=', $id)->value('nickname');
            }

            if (!$nickname) { // 如果在聊天室成员表找不到这名用户了（退群了），直接去用户表找
                $nickname = UserModel::where('id', '=', $item['user_id'])->value('username');
            }

            $item['nickname'] = $nickname;
        })->toArray()['data'];

        if (count($data) === 0) {
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        return new Result(Result::CODE_SUCCESS, null, Arr::keyToCamel2($data));
    }
}
