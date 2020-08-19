<?php

declare(strict_types=1);

namespace app\core\handler;

use app\core\Result;
use think\facade\Db;
use Identicon\Identicon;
use app\model\User as UserModel;
use app\core\util\Arr as ArrUtil;
use app\core\util\Sql as SqlUtil;
use app\core\util\Date as DateUtil;
use app\core\oss\Client as OssClient;
use app\model\Chatroom as ChatroomModel;
use app\model\UserInfo as UserInfoModel;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatRecord as ChatRecordModel;
use app\core\identicon\generator\ImageMagickGenerator;

class User
{
    /** 用户名最小长度 */
    const USERNAME_MIN_LENGTH = 5;
    /** 用户名最大长度 */
    const USERNAME_MAX_LENGTH = 30;
    /** 用户密码最小长度 */
    const PASSWORD_MIN_LENGTH = 8;
    /** 用户密码最大长度 */
    const PASSWORD_MAX_LENGTH = 50;

    /** 用户已存在 */
    const CODE_USER_EXIST = 1;
    /** 用户不存在 */
    const CODE_USER_NOT_EXIST = 2;
    /** 用户密码错误 */
    const CODE_PASSWORD_ERROR = 3;
    /** 用户名过短 */
    const CODE_USERNAME_SHORT = 5;
    /** 用户名过长 */
    const CODE_USERNAME_LONG = 6;
    /** 用户密码过短 */
    const CODE_PASSWORD_SHORT = 7;
    /** 用户密码过长 */
    const CODE_PASSWORD_LONG = 8;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_USER_EXIST     => '用户已存在',
        self::CODE_USER_NOT_EXIST => '用户不存在',
        self::CODE_PASSWORD_ERROR => '密码错误',
        self::CODE_USERNAME_SHORT => '用户名长度必须在' . self::USERNAME_MIN_LENGTH . '~' . self::USERNAME_MAX_LENGTH . '位字符之间',
        self::CODE_USERNAME_LONG  => '用户名长度必须在' . self::USERNAME_MIN_LENGTH . '~' . self::USERNAME_MAX_LENGTH . '位字符之间',
        self::CODE_PASSWORD_SHORT => '密码长度必须在' . self::PASSWORD_MIN_LENGTH . '~' . self::PASSWORD_MAX_LENGTH . '位字符之间',
        self::CODE_PASSWORD_LONG  => '密码长度必须在' . self::PASSWORD_MIN_LENGTH . '~' . self::PASSWORD_MAX_LENGTH . '位字符之间',
    ];

    /** 用户登录SESSION名 */
    const SESSION_USER_LOGIN = 'user_login';

    /** 是否开放注册 */
    const CAN_REGISTER = true;

    /** User 字段 */
    const USER_FIELDS = [
        'user.id',
        'user.username',
        'user.email',
        'user.telephone',
        'user.create_time',
        'user.update_time',
        'user_info.nickname',
        'user_info.signature',
        'user_info.mood',
        'user_info.login_time',
        'user_info.birthday',
        'user_info.gender',
        'user_info.age',
        'user_info.constellation',
        'user_info.avatar',
        'user_info.background_image',
    ];

    /**
     * 获取储存在SESSION中的用户ID
     *
     * @return integer|null
     */
    public static function getId(): ?int
    {
        return session(self::SESSION_USER_LOGIN . '.id');
    }

    /**
     * 获取储存在SESSION中的用户名
     *
     * @return string|null
     */
    public static function getUsername(): ?string
    {
        return session(self::SESSION_USER_LOGIN . '.username');
    }

    /**
     * 注册账户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return Result
     */
    public static function register(string $username, string $password): Result
    {
        if (!self::CAN_REGISTER) {
            return new Result(Result::CODE_ERROR_UNKNOWN, '暂不开放注册！');
        }

        $result = self::checkUsername($username);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户名不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $result = self::checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        if (!empty(self::getIdByUsername($username))) { // 如果已经有这个用户了
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_EXIST]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) { // 如果密码散列创建失败
            return new Result(Result::CODE_ERROR_UNKNOWN, '密码散列创建失败');
        }

        $timestamp = time() * 1000;
        $identicon = new Identicon(new ImageMagickGenerator());
        $bucket = 'onchat';

        // 启动事务
        Db::startTrans();
        try {
            $user = UserModel::create([
                'username'    => $username,
                'password'    => $hash,
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            $ossClient = OssClient::getInstance();
            // 如果为调试模式，则将数据存放到dev/目录下
            $object = OssClient::getRootPath() . 'avatar/user/' . $user->id . '/' . md5((string) DateUtil::now()) . '.png';
            // 根据用户ID创建哈希头像
            $content = $identicon->getImageData($user->id, 256, null, '#f5f5f5');
            // 上传到OSS
            $ossClient->putObject($bucket, $object, $content, OssClient::$imageHeadersOptions);

            // 暂存一下用户信息，便于最后直接返回给前端
            $userInfo = [
                'user_id'          => $user->id,
                'nickname'         => $user->username,
                'login_time'       => $timestamp,
                'avatar'           => $object,
                'background_image' => 'http://static.hypergo.net/img/rkph.jpg', // TODO
            ];

            UserInfoModel::create($userInfo);

            self::saveLoginStatus($user->id, $username, $hash); // 保存登录状态

            Chatroom::addChatMember(1, $user->id); // 添加新用户到默认聊天室

            unset($user->password); // 删掉密码

            $avatar = OssClient::getDomain() . $object;
            $userInfo['avatar'] = $avatar . OssClient::getOriginalImgStylename();
            $userInfo['avatarThumbnail'] = $avatar . OssClient::getThumbnailImgStylename();

            // 提交事务
            Db::commit();

            return new Result(Result::CODE_SUCCESS, '注册成功！即将跳转…', ArrUtil::keyToCamel($user->toArray() + $userInfo));
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return Result
     */
    public static function login(string $username, string $password): Result
    {
        $result = self::checkUsername($username);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户名不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $result = self::checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[$result]);
        }

        $fields = self::USER_FIELDS;
        $fields[] = 'user.password';

        $user = self::getInfoByKey('username', $username, $fields);

        if (empty($user)) { // 如果用户不存在
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        if (!password_verify($password, $user['password'])) { // 如果密码错误
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_PASSWORD_ERROR]);
        }

        self::saveLoginStatus($user['id'], $user['username'], $user['password']); // 保存登录状态

        unset($user['password']);

        $avatar = OssClient::getDomain() . $user['avatar'];
        $user['avatar'] = $avatar . OssClient::getOriginalImgStylename();
        $user['avatarThumbnail'] = $avatar . OssClient::getThumbnailImgStylename();

        return new Result(Result::CODE_SUCCESS, '登录成功！即将跳转…', ArrUtil::keyToCamel($user));
    }

    /**
     * 清除登录Session，退出登录
     *
     * @return void
     */
    public static function logout(): void
    {
        session(self::SESSION_USER_LOGIN, null);
    }

    /**
     * 设置用户登录Session，用于保存登录状态
     *
     * @param integer $id 用户ID
     * @param string $username 用户名
     * @param string $hashPassword 密码密文
     * @return void
     */
    public static function saveLoginStatus(int $id, string $username, string $hashPassword): void
    {
        session(self::SESSION_USER_LOGIN, [
            'id'       => $id,
            'username' => $username,
            'password' => $hashPassword,
        ]);
    }

    /**
     * 通过用户标识获取用户信息
     *
     * @param string $key 用户标识名
     * @param [type] $value 用户标识值
     * @param string|array $field 需要获取的字段名
     * @return array
     */
    public static function getInfoByKey(string $key, $value, $field): array
    {

        return UserModel::where($key == 'id' ? 'user.id' : $key, '=', $value)->join('user_info', 'user_info.user_id = user.id')
            ->field($field)->findOrEmpty()->toArray();
    }

    /**
     * 通过用户ID获取User
     *
     * @param integer $id
     * @return Result
     */
    public static function getUserById(int $id): Result
    {
        $user = UserModel::where('user.id', '=', $id)->join('user_info', 'user_info.user_id = user.id')
            ->field(self::USER_FIELDS)->find();

        if (!$user) {
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $avatar = OssClient::getDomain() . $user->avatar;
        $user->avatar = $avatar . OssClient::getOriginalImgStylename();
        $user->avatarThumbnail = $avatar . OssClient::getThumbnailImgStylename();

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($user->toArray()));
    }

    /**
     * 通过用户名获取User
     *
     * @param string $username
     * @return Result
     */
    public static function getUserByUsername(string $username): Result
    {
        $user = UserModel::where('user.username', '=', $username)->join('user_info', 'user_info.user_id = user.id')
            ->field(self::USER_FIELDS)->find();

        if (!$user) {
            return new Result(Result::CODE_ERROR_PARAM, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $avatar = OssClient::getDomain() . $user->avatar;
        $user->avatar = $avatar . OssClient::getOriginalImgStylename();
        $user->avatarThumbnail = $avatar . OssClient::getThumbnailImgStylename();

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($user->toArray()));
    }

    /**
     * 获得用户在聊天室中的昵称
     *
     * @param integer $id 用户ID
     * @param integer $chatroomId
     * @return string|null
     */
    public static function getNicknameInChatroom(int $id, int $chatroomId): ?string
    {
        return ChatMemberModel::where([
            'user_id'     => $id,
            'chatroom_id' => $chatroomId
        ])->value('nickname');
    }

    /**
     * 通过用户ID获取用户名
     *
     * @param integer $id 用户ID
     * @return string|null
     */
    public static function getUsernameById(int $id): ?string
    {
        return UserModel::where('id', '=', $id)->value('username');
    }

    /**
     * 通过用户名获取用户ID
     *
     * @param string $username 用户名
     * @return integer
     */
    public static function getIdByUsername(string $username): ?int
    {
        return UserModel::where('username', '=', $username)->value('id');
    }

    /**
     * 获取用户ID
     *
     * @return Result
     */
    public static function getUserId(): Result
    {
        $id = self::getId();
        if (!$id) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }
        return new Result(Result::CODE_SUCCESS, null, $id);
    }

    /**
     * 检查用户是否已经登录/处于登录状态
     * 如果已登录，则返回User, 否则返回false
     *
     * @return Result
     */
    public static function checkLogin(): Result
    {
        $session = session(self::SESSION_USER_LOGIN);
        if (empty($session)) { // 如果没有登录的Session
            return new Result(Result::CODE_SUCCESS, null, false);
        }

        $fields = self::USER_FIELDS;
        $fields[] = 'user.password';
        $user = self::getInfoByKey('id', $session['id'], $fields);

        if ($session['password'] !== $user['password']) { // 如果密码错误
            return new Result(Result::CODE_SUCCESS, null, false);
        }

        $avatar = OssClient::getDomain() . $user['avatar'];
        $user['avatar'] = $avatar . OssClient::getOriginalImgStylename();
        $user['avatarThumbnail'] = $avatar . OssClient::getThumbnailImgStylename();

        unset($user['password']);

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel($user));
    }

    /**
     * 检查用户名是否符合规范
     *
     * @param string $username
     * @return integer
     */
    public static function checkUsername(string $username): int
    {
        $length = mb_strlen($username, 'utf-8');

        if ($length < self::USERNAME_MIN_LENGTH) {
            return self::CODE_USERNAME_SHORT;
        } elseif ($length > self::USERNAME_MAX_LENGTH) {
            return self::CODE_USERNAME_LONG;
        } else {
            return Result::CODE_SUCCESS;
        }
    }

    /**
     * 检查用户密码是否符合规范
     *
     * @param string $password
     * @return integer
     */
    public static function checkPassword(string $password): int
    {
        $length = mb_strlen($password, 'utf-8');

        if ($length < self::PASSWORD_MIN_LENGTH) {
            return self::CODE_PASSWORD_SHORT;
        } elseif ($length > self::PASSWORD_MAX_LENGTH) {
            return self::CODE_PASSWORD_LONG;
        } else {
            return Result::CODE_SUCCESS;
        }
    }

    public static function avatar(): Result
    {
        $id = self::getId();
        if (!$id) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $bucket = 'onchat';

        // 如果为调试模式，则将数据存放到dev/目录下
        $root = OssClient::getRootPath();

        // 由于搜索用户所有历史头像
        $options = [
            // 文件路径前缀
            'prefix' => $root . 'avatar/user/' . $id . '/',
            // 最大数量
            'max-keys' => 20,
        ];

        // 用户头像数量最大值
        $maxCount = 10;

        try {
            $image = request()->file('image');

            $mine = $image->getMime();

            if (!in_array($mine, ['image/webp', 'image/jpeg', 'image/png'])) {
                return new Result(Result::CODE_ERROR_PARAM, '文件格式错误，仅接受格式为webp/jpeg/png的图片文件');
            }

            if ($image->getSize() > 1048576) { // 1MB
                return new Result(Result::CODE_ERROR_PARAM, '文件体积过大，仅接受体积为1MB以内的文件');
            }

            $ossClient = OssClient::getInstance();

            $object = $root . 'avatar/user/' . $id . '/' . md5((string) DateUtil::now()) . '.' . substr($mine, 6);
            // 上传到OSS
            $ossClient->uploadFile($bucket, $object, $image->getRealPath(), OssClient::$imageHeadersOptions);

            // 列举用户所有头像
            $objectList = $ossClient->listObjects($bucket, $options)->getObjectList();

            $count = count($objectList);

            // 如果用户的头像大于10张
            if ($count > $maxCount) {
                // 按照时间进行升序
                usort($objectList, function ($a, $b) {
                    return strtotime($a->getLastModified()) - strtotime($b->getLastModified());
                });

                // 需要删除的OBJ
                $objects = [];

                $num = $count - $maxCount;
                for ($i = 0; $i < $num; $i++) {
                    $objects[] = $objectList[$i]->getKey();
                }

                // 把超过的删除
                $ossClient->deleteObjects($bucket, $objects);
            }

            // 更新新头像
            $userInfo = UserInfoModel::where('user_id', '=', $id)->field('avatar')->find();
            $userInfo->avatar = $object;
            $userInfo->save();

            $domain = OssClient::getDomain();

            return new Result(Result::CODE_SUCCESS, null, [
                'avatar'          => $domain . $object . OssClient::getOriginalImgStylename(),
                'avatarThumbnail' => $domain . $object . OssClient::getThumbnailImgStylename()
            ]);
        } catch (\Exception $e) {
            return new Result(Result::CODE_ERROR_UNKNOWN, $e->getMessage());
        }
    }

    /**
     * 查询该用户下所有的聊天室
     *
     * @return Result
     */
    public static function getChatrooms($userId = null): Result
    {
        $data = UserModel::find($userId ?: self::getId())->chatrooms()->select()->toArray();
        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel2($data));
    }

    /**
     * 查询该用户下的聊天列表
     *
     * @return Result
     */
    public static function getChatList(): Result
    {
        $userId = self::getId();
        if (!$userId) {
            return new Result(Result::CODE_ERROR_NO_ACCESS);
        }

        $data = ChatMemberModel::where([
            'chat_member.user_id' => $userId,
            'chat_member.is_show' => true
        ])->join('chatroom', 'chat_member.chatroom_id = chatroom.id')
            ->field([
                'chat_member.id',
                'chat_member.chatroom_id',
                'chat_member.unread',
                'chat_member.sticky',
                'chat_member.create_time',
                'chat_member.update_time',
                'chatroom.name',
                'chatroom.avatar',
                'chatroom.type',
            ])
            // ->order('chat_member.update_time', 'DESC') 由于前端需要即时排序，则将这一步交给前端
            ->select()->toArray();

        // 查询每个聊天室的最新那条消息，并且查到消息发送者的昵称
        $latestMsg = null;
        $nickname = null;
        $privateChatroomIdList = []; // 私聊聊天室的ID列表
        $chatroomId = null;

        foreach ($data as $key => $value) {
            $chatroomId = $value['chatroom_id'];
            // 如果是私聊聊天室
            if ($value['type'] == ChatroomModel::TYPE_PRIVATE_CHAT) {
                $privateChatroomIdList[] = $chatroomId;
            }

            $latestMsg = ChatRecordModel::opt($chatroomId)->where('chatroom_id', '=', $chatroomId)->order('id', 'DESC')->findOrEmpty()->toArray();
            if (!$latestMsg) {
                continue;
            }

            $nickname = User::getNicknameInChatroom($latestMsg['user_id'], $chatroomId);
            if (!$nickname) { // 如果在聊天室成员表找不到这名用户了（退群了），直接去用户表找
                $nickname = self::getUsernameById($latestMsg['user_id']);
            }
            $latestMsg['nickname'] = $nickname;
            $latestMsg['data'] = json_decode($latestMsg['data']);
            $data[$key]['latestMsg'] = ArrUtil::keyToCamel($latestMsg);
        }

        // 如果其中有私聊聊天室
        if (count($privateChatroomIdList) > 0) {
            // chatroomId => nickname
            $privateChatroomNameMap = [];
            // chatroomId => friend user id
            $friendIdMap = [];
            // chatroomId => avatar
            $privateChatroomAvatarMap = [];

            // 找到私聊聊天室，室友（好友）的nickname
            $list = ChatMemberModel::where('chatroom_id', 'IN', $privateChatroomIdList)
                ->where('user_id', '<>', $userId)->field('chatroom_id, user_id, nickname')->select();

            foreach ($list as $item) {
                $privateChatroomNameMap[$item->chatroom_id] = $item->nickname;
                $friendIdMap[$item->chatroom_id] = $item->user_id;
            }

            // 找到私聊聊天室，室友（好友）的头像
            $list = UserInfoModel::where('user_id', 'IN', array_values($friendIdMap))
                ->field('user_id, avatar')->select();

            $domain = OssClient::getDomain();
            $stylename = OssClient::getThumbnailImgStylename();

            foreach ($list as $item) {
                $privateChatroomAvatarMap[array_search($item->user_id, $friendIdMap)] = $domain . $item->avatar . $stylename;
            }

            foreach ($data as $key => $value) {
                if ($value['type'] == ChatroomModel::TYPE_PRIVATE_CHAT) {
                    $data[$key]['name'] = $privateChatroomNameMap[$value['chatroom_id']];
                    $data[$key]['avatarThumbnail'] = $privateChatroomAvatarMap[$value['chatroom_id']];
                }
            }
        }

        return new Result(Result::CODE_SUCCESS, null, ArrUtil::keyToCamel2($data));
    }

    /**
     * 置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public static function sticky(int $id, $sticky = true): Result
    {
        $num = UserModel::find(User::getId())->chatMember()->update([
            'id'          => $id,
            'sticky'      => $sticky
        ]);

        if ($num == 0) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return new Result(Result::CODE_SUCCESS);
    }

    /**
     * 取消置顶聊天列表子项
     *
     * @param integer $id 聊天室成员表ID
     * @return Result
     */
    public static function unsticky(int $id): Result
    {
        return self::sticky($id, false);
    }

    /**
     * 将聊天列表子项设置为已读（通过用户ID+房间号）
     *
     * @param integer $chatroomId 聊天室ID
     * @param integer $unread
     * @return Result
     */
    public static function readed(int $chatroomId, int $unread = 0): Result
    {
        $num = UserModel::find(User::getId())->chatMember()->where('chatroom_id', '=', $chatroomId)->update([
            'unread' => $unread
        ]);

        if ($num == 0) {
            return new Result(Result::CODE_ERROR_PARAM);
        }

        return new Result(Result::CODE_SUCCESS);
    }

    /**
     * 将聊天列表子项设置为未读
     *
     * @param integer $chatroomId 聊天室ID
     * @return Result
     */
    public static function unread(int $chatroomId): Result
    {
        return self::readed($chatroomId, 1);
    }
}
