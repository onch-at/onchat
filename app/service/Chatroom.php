<?php

declare(strict_types=1);

namespace app\service;

use Identicon\Identicon;
use app\constant\MessageType;
use app\constant\SocketEvent;
use app\constant\SocketRoomPrefix;
use app\core\Result;
use app\core\identicon\ImageMagickGenerator;
use app\core\storage\Storage;
use app\entity\JoinRoomTipsMessage;
use app\entity\Message;
use app\facade\ChatRecordService;
use app\facade\UserService;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatSession as ChatSessionModel;
use app\model\Chatroom as ChatroomModel;
use app\util\Date as DateUtil;
use app\util\File as FileUtil;
use app\util\Str as StrUtil;
use think\Container;
use think\facade\Db;
use think\facade\Request;
use think\swoole\Websocket;

class Chatroom
{
    /** 聊天室名字过长 */
    const CODE_NAME_LONG = 2;
    /** 群介绍长度不符合规范 */
    const CODE_DESCRIPTION_IRREGULAR = 3;
    /** 可创建的聊天室数量已满 */
    const CODE_GROUP_CHAT_COUNT_FULL = 4;
    /** 聊天室人数已满 */
    const CODE_PEOPLE_NUM_FULL = 5;
    /** 昵称过长 */
    const CODE_NICKNAME_LONG = 6;

    /**
     * 获取聊天室名称
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getName(int $id): Result
    {
        $chatroom = ChatroomModel::field(['name', 'type'])->find($id);

        // 如果聊天室类型是私聊的，则聊天室的名称需要返回私聊好友的Nickname
        if ($chatroom->type === ChatroomModel::TYPE_PRIVATE_CHAT) {
            $userId = UserService::getId();

            // 找到自己和好友
            $data = ChatMemberModel::where([
                'chatroom_id' => $id,
                'user_id'     => $userId
            ])->whereOr(function ($query) use ($id, $userId) {
                $query->where([
                    ['chatroom_id', '=', $id],
                    ['user_id', '<>', $userId]
                ]);
            })->field(['user_id', 'nickname'])
                ->limit(2)
                ->select();

            return Result::success($data->where('user_id', '<>', $userId)[0]->nickname);
        }

        return Result::success($chatroom->name);
    }

    /**
     * 设置聊天室名称
     *
     * @param integer $id 聊天室ID
     * @param string $name 名称
     * @return Result
     */
    public function setName(int $id, string $name): Result
    {
        $name = trim($name);
        // 如果长度超出
        if (StrUtil::length($name) > ONCHAT_CHATROOM_NAME_MAX_LENGTH) {
            return Result::create(self::CODE_NAME_LONG, '聊天室名字长度不能大于' . ONCHAT_CHATROOM_NAME_MAX_LENGTH . '位字符');
        }

        ChatroomModel::update([
            'id'   => $id,
            'name' => $name
        ]);

        return Result::success();
    }

    /**
     * 设置群昵称
     *
     * @param integer $id 聊天室ID
     * @param string|null $nickname 昵称
     * @return Result
     */
    public function setNickname(int $id, ?string $nickname): Result
    {
        // 如果有传入昵称
        if ($nickname && !StrUtil::isEmpty($nickname)) {
            $nickname = StrUtil::trimAll($nickname);
            // 如果昵称长度超出
            if (StrUtil::length($nickname) > ONCHAT_NICKNAME_MAX_LENGTH) {
                return Result::create(self::CODE_NICKNAME_LONG, '昵称长度不能大于' . ONCHAT_NICKNAME_MAX_LENGTH . '位字符');
            }
        } else {
            $nickname = UserService::getUsername();
        }

        $userId = UserService::getId();

        $chatMember = ChatMemberModel::where([
            'chatroom_id' => $id,
            'user_id'     => $userId
        ])->find();

        $chatMember->nickname = $nickname;
        $chatMember->save();

        return Result::success($nickname);
    }

    /**
     * 根据聊天室ID获取聊天室
     *
     * @param integer $id
     * @return Result
     */
    public function getChatroom(int $id): Result
    {
        $chatroom = ChatroomModel::find($id);

        if (!$chatroom) {
            return Result::create(Result::CODE_ERROR_PARAM);
        }

        // 如果聊天室类型是私聊的，则聊天室的名称需要返回私聊好友的Nickname
        if ($chatroom->type === ChatroomModel::TYPE_PRIVATE_CHAT) {
            $userId = UserService::getId();

            // 找到自己
            $self = ChatMemberModel::where([
                'chatroom_id' => $id,
                'user_id'     => $userId
            ])->find();

            // 如果找不到，则代表自己没有进这个群
            if (!$self) {
                return Result::create(Result::CODE_ERROR_NO_PERMISSION);
            }

            // 查找加入了这个房间的另一个好友的信息
            $friendInfo = ChatMemberModel::join('user_info', 'user_info.user_id = chat_member.user_id')->where([
                ['chat_member.chatroom_id', '=', $id],
                ['chat_member.user_id', '<>', $userId]
            ])->field(['chat_member.nickname', 'user_info.avatar'])->find();

            $chatroom->name = $friendInfo->nickname;
            $chatroom->avatar = $friendInfo->avatar;
        }

        $storage = Storage::getInstance();
        $avatar  = $chatroom->avatar;

        $chatroom->avatar          = $storage->getUrl($avatar);
        $chatroom->avatarThumbnail = $storage->getThumbnailUrl($avatar);

        return Result::success($chatroom);
    }

    /**
     * 创建一个聊天室
     *
     * @param string $name 聊天室名称
     * @param integer $type 聊天室类型
     * @param integer $description 聊天室描述、简介
     * @return Result
     */
    public function creatChatroom(string $name = null, int $type = ChatroomModel::TYPE_GROUP_CHAT, ?string $description = null): Result
    {
        if ($name) {
            $name = trim($name);
            // 如果长度超出
            if (StrUtil::length($name) > ONCHAT_CHATROOM_NAME_MAX_LENGTH) {
                return Result::create(self::CODE_NAME_LONG, '聊天室名字长度不能大于' . ONCHAT_CHATROOM_NAME_MAX_LENGTH . '位字符');
            }
        }

        if ($description) {
            $description = trim($description);
            $length = StrUtil::length($description);
            // 如果长度超出
            if ($length < ONCHAT_CHATROOM_DESCRIPTION_MIN_LENGTH || $length > ONCHAT_CHATROOM_DESCRIPTION_MAX_LENGTH) {
                return Result::create(self::CODE_DESCRIPTION_IRREGULAR, '聊天室介绍长度必须在' . ONCHAT_CHATROOM_DESCRIPTION_MIN_LENGTH  . '~' . ONCHAT_CHATROOM_DESCRIPTION_MAX_LENGTH  . '位字符之间');
            }
        }


        $timestamp = time() * 1000;
        $peopleLimit = [
            ChatroomModel::TYPE_SINGLE_CHAT  => 1,
            ChatroomModel::TYPE_PRIVATE_CHAT => 2,
            ChatroomModel::TYPE_GROUP_CHAT   => 1000,
        ][$type];

        // 创建一个聊天室
        $chatroom = ChatroomModel::create([
            'name'           => $name,
            'type'           => $type,
            'description'    => $description,
            'people_limit'   => $peopleLimit,
            'create_time'    => $timestamp,
            'update_time'    => $timestamp,
        ]);

        if ($type === ChatroomModel::TYPE_GROUP_CHAT) {
            $storage = Storage::getInstance();
            $identicon = new Identicon(new ImageMagickGenerator());

            // 根据用户ID创建哈希头像
            $imageData = $identicon->getImageData($chatroom->id, 256, null, '#f5f5f5');
            $path      = $storage->getRootPath() . 'avatar/chatroom/' . $chatroom->id . '/';
            $file      = md5((string) DateUtil::now()) . '.png';
            $result    = $storage->save($path, $file, $imageData);

            if (!$result->isSuccess()) {
                return $result;
            }

            $filename = $path . $file;

            ChatroomModel::update([
                'id' => $chatroom->id,
                'avatar' => $filename
            ]);

            $chatroom->avatar = $storage->getUrl($filename);
            $chatroom->avatarThumbnail = $storage->getThumbnailUrl($filename);
        }

        return Result::success($chatroom);
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
    public function addMember(int $id, int $userId, string $nickname = null, int $role = ChatMemberModel::ROLE_NORMAL): Result
    {
        $username = UserService::getUsernameById($userId);
        $chatroom = ChatroomModel::find($id);
        // 如果没有这个房间，或者没有这个用户，或者这个用户已经加入了这个房间
        if (
            !$chatroom || !$username ||
            $this->isMember($id, $userId)
        ) {
            return Result::create(Result::CODE_ERROR_PARAM);
        }

        if ($this->isPeopleNumFull($id)) {
            return Result::create(self::CODE_PEOPLE_NUM_FULL, '聊天室人数已满!');
        }

        $timestamp = time() * 1000;

        ChatMemberModel::create([
            'chatroom_id' => $id,
            'user_id'     => $userId,
            'nickname'    => $nickname ?: $username,
            'role'        => $role,
            'create_time' => $timestamp,
            'update_time' => $timestamp,
        ]);

        $data = ChatSessionModel::create([
            'user_id'     => $userId,
            'type'        => ChatSessionModel::TYPE_CHATROOM,
            'data'        => ['chatroomId' => $id],
            'unread'      => 1,
            'create_time' => $timestamp,
            'update_time' => $timestamp
        ]);

        // 非私聊则发送入群消息
        if ($chatroom->type !== ChatroomModel::TYPE_PRIVATE_CHAT) {
            $websocket = Container::getInstance()->make(Websocket::class);

            // 添加入群消息
            $msg = new Message(MessageType::TIPS);
            $msg->userId     = $userId;
            $msg->chatroomId = $id;
            $msg->data       = new JoinRoomTipsMessage();

            $result = ChatRecordService::addRecord($msg);

            if ($result->isSuccess()) {
                $websocket->to(SocketRoomPrefix::CHATROOM . $id)->emit(SocketEvent::MESSAGE, $result);
            }
        }

        return Result::success($data);
    }

    /**
     * 创建群聊聊天室
     *
     * @param string $name
     * @param string $description
     * @param integer $userId
     * @param string $username
     * @return Result
     */
    public function create(string $name, ?string $description, int $userId, string $username): Result
    {
        if (!$name) {
            return Result::create(Result::CODE_ERROR_PARAM);
        }

        $count = ChatMemberModel::where([
            'user_id' => $userId,
            'role' => ChatMemberModel::ROLE_HOST
        ])->count();

        if ($count >= ONCHAT_USER_MAX_GROUP_CHAT_COUNT) {
            return Result::create(self::CODE_GROUP_CHAT_COUNT_FULL, '你可创建的聊天室数量已满！');
        }

        // 启动事务
        Db::startTrans();
        try {
            $result = $this->creatChatroom($name, ChatroomModel::TYPE_GROUP_CHAT, $description);
            if (!$result->isSuccess()) {
                Db::rollback();
                return $result;
            }

            $chatroom = $result->data;

            // 将自己添加到聊天室，角色为主人
            $result = $this->addMember($chatroom['id'], $userId, $username, ChatMemberModel::ROLE_HOST);
            if (!$result->isSuccess()) {
                Db::rollback();
                return $result;
            }

            $data = $result->data;

            // 补充一些信息
            $data['title'] = $name;
            $data['avatarThumbnail'] = $chatroom['avatarThumbnail'];
            $data['data']['chatroomType'] = ChatroomModel::TYPE_GROUP_CHAT;

            Db::commit();

            // 这里就不用转骆驼峰了，因为上面已经转过了
            return Result::success($data);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return Result::create(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 获取群聊所有成员
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function getChatMembers(int $id)
    {
        $data = ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')
            ->join('user_info', 'user_info.user_id = chat_member.user_id')
            ->where([
                'chatroom.id' => $id,
                'chatroom.type' => ChatroomModel::TYPE_GROUP_CHAT
            ])
            ->field([
                'chat_member.id',
                'chat_member.nickname',
                'chat_member.user_id',
                'user_info.avatar AS avatarThumbnail',
                'chat_member.role',
                'chat_member.create_time',
                'chat_member.update_time',
            ])
            ->select();

        $storage = Storage::getInstance();

        foreach ($data as $item) {
            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatarThumbnail);
        }

        return Result::success($data);
    }

    /**
     * 上传聊天室头像
     *
     * @param integer $id 聊天室ID
     * @return Result
     */
    public function avatar(int $id): Result
    {
        try {
            $storage = Storage::getInstance();
            $image   = Request::file('image');
            $path    = $storage->getRootPath() . 'avatar/chatroom/' . $id . '/';
            $file    = $image->md5() . '.' . FileUtil::getExtension($image);

            $result = $storage->save($path, $file, $image);
            $storage->clear($path, Storage::AVATAR_MAX_COUNT);

            if (!$result->isSuccess()) {
                return $result;
            }

            $filename = $path . $file;

            // 更新新头像
            $chatroom = ChatroomModel::field('avatar')->find($id);
            $chatroom->avatar = $filename;
            $chatroom->save();

            return Result::success([
                'avatar'          => $storage->getUrl($filename),
                'avatarThumbnail' => $storage->getThumbnailUrl($filename)
            ]);
        } catch (\Exception $e) {
            return Result::create(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 是否是聊天室成员
     *
     * @param integer $id 聊天室ID
     * @param integer $userId 用户ID
     * @return boolean
     */
    public function isMember(int $id, int $userId): bool
    {
        return !!ChatMemberModel::where([
            'chatroom_id' => $id,
            'user_id'     => $userId
        ])->find();
    }

    /**
     * 聊天室人数满了吗
     *
     * @param integer $id 聊天室ID
     * @return boolean
     */
    public function isPeopleNumFull(int $id): bool
    {
        $data = ChatroomModel::join('chat_member', 'chat_member.chatroom_id = chatroom.id')
            ->where('chatroom.id', '=', $id)
            ->field('chatroom.people_limit AS peopleLimit')
            ->fieldRaw('COUNT(*) AS peopleNum')->find();

        if (!$data) {
            return false;
        }

        return $data->peopleNum >= $data->peopleLimit;
    }

    /**
     * 获得成员角色
     *
     * @param integer $id 聊天室ID
     * @param integer $userId 成员ID
     * @return integer
     */
    public function getMemberRole(int $id, int $userId): ?int
    {
        return ChatMemberModel::where([
            'chatroom_id' => $id,
            'user_id'     => $userId
        ])->value('role');
    }

    /**
     * 获取群聊聊天室群主和管理员的用户ID列表
     *
     * @param integer $id 聊天室ID
     * @return array|null
     */
    public function getHostAndManagerIdList(int $id): ?array
    {
        return ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')
            ->where([
                ['chatroom.id', '=', $id],
                ['chatroom.type', '=', ChatroomModel::TYPE_GROUP_CHAT]
            ])
            ->where(function ($query) {
                $query->whereOr([
                    ['chat_member.role', '=',  ChatMemberModel::ROLE_HOST],
                    ['chat_member.role', '=',  ChatMemberModel::ROLE_MANAGE],
                ]);
            })
            ->column('user_id');
    }

    /**
     * 模糊搜索聊天室
     *
     * @param string $keyword
     * @param integer $page
     * @return Result
     */
    public function search(string $keyword, int $page): Result
    {
        $storage = Storage::getInstance();

        $expression = "%{$keyword}%";
        $data = ChatroomModel::where('type', '=', ChatroomModel::TYPE_GROUP_CHAT)
            ->where(function ($query) use ($expression) {
                $query->whereOr([
                    ['name', 'LIKE', $expression],
                    ['description', 'LIKE', $expression],
                    ['id', 'LIKE', $expression],
                ]);
            })
            ->page($page, 15)
            ->select();

        foreach ($data as $item) {
            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatar);
            $item->avatar          = $storage->getUrl($item->avatar);
        }

        return Result::success($data);
    }
}
