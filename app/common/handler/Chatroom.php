<?php

declare(strict_types=1);

namespace app\common\handler;

use app\common\Result;
use app\model\Chatroom as ChatroomModel;
use app\model\ChatMember as ChatMemberModel;
use app\common\util\Arr;
use app\model\User as UserModel;

class Chatroom
{
    /** 权限不足 */
    const CODE_NO_ACCESS = 1;
    /** 没有消息 */
    const CODE_NO_RECORD = 2;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_NO_ACCESS => '权限不足',
        self::CODE_NO_RECORD => '没有消息'
    ];

    /** 每次查询的消息行数 */
    const MSG_ROWS = 5;

    public static function getRecords(int $id, int $page = 1): Result
    {
        $userId = User::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_LOGIN);
        }

        // 如果该用户不属于这个聊天室
        $chatMember = UserModel::find($userId)->chatMember()->find(1);
        if (!$chatMember) {
            return new Result(self::CODE_NO_ACCESS, self::MSG[self::CODE_NO_ACCESS]);
        }

        $chatRecord = ChatroomModel::find($id)->chatRecord();
        if ($chatRecord->count() === 0) {
            return new Result(self::CODE_NO_RECORD, self::MSG[self::CODE_NO_RECORD]);
        }

        $data = $chatRecord->paginateX([
            'list_rows' => self::MSG_ROWS,
            'page' => $page,
        ])->each(function ($item) use ($userId, $chatMember) {
            // TODO 查询用户头像
            $item['avatar_thumbnail'] = null;

            $nickname = $item['user_id'] == $userId ? $chatMember->value('nickname') : ChatMemberModel::where('user_id', '=', $item['user_id'])->value('nickname');
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
