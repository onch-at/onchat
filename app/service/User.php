<?php

declare(strict_types=1);

namespace app\service;

use Identicon\Identicon;
use app\core\Result;
use app\core\identicon\ImageMagickGenerator;
use app\core\storage\Storage;
use app\entity\TokenFolder;
use app\facade\AuthService;
use app\facade\ChatroomService;
use app\facade\IndexService;
use app\facade\TokenService;
use app\model\ChatMember as ChatMemberModel;
use app\model\ChatSession as ChatSessionModel;
use app\model\Chatroom as ChatroomModel;
use app\model\User as UserModel;
use app\model\UserInfo as UserInfoModel;
use app\utils\Date as DateUtils;
use app\utils\File as FileUtils;
use app\utils\Str as StrUtils;
use think\Collection;
use think\facade\Db;
use think\facade\Request;

class User
{
    /** 用户已存在 */
    const CODE_USER_EXIST = 1;
    /** 用户不存在 */
    const CODE_USER_NOT_EXIST = 2;
    /** 用户密码错误 */
    const CODE_PASSWORD_ERROR = 3;
    /** 用户密码过短/过长 */
    const CODE_PASSWORD_IRREGULAR = 4;
    /** 个性签名过短/过长 */
    const CODE_SIGNATURE_IRREGULAR = 5;

    /** 响应消息预定义 */
    const MSG = [
        self::CODE_USER_EXIST          => '用户已存在',
        self::CODE_USER_NOT_EXIST      => '用户不存在',
        self::CODE_PASSWORD_ERROR      => '密码错误',
        self::CODE_PASSWORD_IRREGULAR  => '密码长度必须在' . ONCHAT_PASSWORD_MIN_LENGTH . '~' . ONCHAT_PASSWORD_MAX_LENGTH . '位字符之间',
        self::CODE_SIGNATURE_IRREGULAR => '个性签名长度必须在' . ONCHAT_SIGNATURE_MIN_LENGTH . '~' . ONCHAT_SIGNATURE_MAX_LENGTH . '位字符之间',
    ];

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
        'user_info.constellation',
        'user_info.avatar',
        'user_info.background_image',
    ];

    /**
     * 获取储存在SESSION中的用户ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        $token = Request::header('Authorization');

        return TokenService::parse($token)->sub;
    }

    /**
     * 获取储存在SESSION中的用户名.
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        $token = Request::header('Authorization');

        return TokenService::parse($token)->usr->username;
    }

    /**
     * 注册账户.
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $captcha  验证码
     *
     * @return Result
     */
    public function register(string $username, string $password, string $email, string $captcha): Result
    {
        if (!ONCHAT_CAN_REGISTER) {
            return Result::unknown('暂不开放注册！');
        }

        if (!IndexService::checkEmail($email)->data) { // 如果邮箱不可用
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        if (!IndexService::checkEmailCaptcha($email, $captcha)) {
            return Result::create(Result::CODE_PARAM_ERROR, '验证码错误！');
        }

        $username = StrUtils::trimAll($username);
        $password = StrUtils::trimAll($password);

        if (!preg_match(ONCHAT_USERNAME_PATTERN, $username)) {
            return Result::create(Result::CODE_PARAM_ERROR, '用户名格式不规范！');
        }

        $result = $this->checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[$result]);
        }

        if (!IndexService::checkUsername($username)->data) { // 如果已经有这个用户了
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_USER_EXIST]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) { // 如果密码散列创建失败
            return Result::unknown('密码散列创建失败');
        }

        $timestamp = time() * 1000;
        $identicon = new Identicon(new ImageMagickGenerator());

        // 启动事务
        Db::startTrans();

        try {
            $user = UserModel::create([
                'username'    => $username,
                'password'    => $hash,
                'email'       => strtolower($email),
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            $storage = Storage::create();
            // 根据用户ID创建哈希头像
            $imageData = $identicon->getImageData($user->id, 256, null, '#f5f5f5');

            $path = $storage->getRootPath() . 'avatar/user/' . $user->id . '/';
            $file = md5((string) DateUtils::now()) . '.png';

            $result = $storage->save($path, $file, $imageData);

            if ($result->isError()) {
                return $result;
            }

            $filename = $path . $file;

            // 暂存一下用户信息，便于最后直接返回给前端
            $userInfo = [
                'user_id'          => $user->id,
                'nickname'         => $user->username,
                'login_time'       => $timestamp,
                'avatar'           => $filename,
                'background_image' => 'http://static.hypergo.net/img/rkph.jpg', // TODO
            ];

            UserInfoModel::create($userInfo);

            ChatroomService::addMember(1, $user->id); // 添加新用户到默认聊天室

            unset($user->password); // 删掉密码

            $userInfo['avatar'] = $storage->getUrl($filename);
            $userInfo['avatarThumbnail'] = $storage->getThumbnailUrl($filename);

            // 创建一个聊天室通知会话
            ChatSessionModel::create([
                'user_id'     => $user->id,
                'type'        => ChatSessionModel::TYPE_CHATROOM_NOTICE,
                'data'        => [],
                'visible'     => false,
                'create_time' => $timestamp,
                'update_time' => $timestamp,
            ]);

            $tokenFolder = $this->issueTokens($user->id, $username);
            $user->access = $tokenFolder->access;
            $user->refresh = $tokenFolder->refresh;

            // 提交事务
            Db::commit();

            return Result::success($user->toArray() + $userInfo);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 用户登录.
     *
     * @param string $username 用户名
     * @param string $password 密码
     *
     * @return Result
     */
    public function login(string $username, string $password): Result
    {
        if (!preg_match(ONCHAT_USERNAME_PATTERN, $username)) {
            return Result::create(Result::CODE_PARAM_ERROR, '用户名格式不规范！');
        }

        $result = $this->checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[$result]);
        }

        $fields = self::USER_FIELDS;
        $fields[] = 'user.password';

        $user = $this->getByKey('username', $username, $fields);

        if (empty($user)) { // 如果用户不存在
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        if (!password_verify($password, $user->password)) { // 如果密码错误
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_PASSWORD_ERROR]);
        }

        unset($user->password);

        $storage = Storage::create();

        $user->avatarThumbnail = $storage->getThumbnailUrl($user->avatar);
        $user->avatar = $storage->getUrl($user->avatar);

        $tokenFolder = $this->issueTokens($user->id, $username);
        $user->access = $tokenFolder->access;
        $user->refresh = $tokenFolder->refresh;

        return Result::success($user);
    }

    /**
     * 修改密码
     *
     * @param string $oldPassword 原密码
     * @param string $newPassword 新密码
     *
     * @return Result
     */
    public function changePassword(string $oldPassword, string $newPassword): Result
    {
        $result = $this->checkPassword($newPassword);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[$result]);
        }

        $id = $this->getId();

        $user = UserModel::find($id);

        if (!password_verify($oldPassword, $user->password)) { // 如果密码错误
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_PASSWORD_ERROR]);
        }

        $newPassword = StrUtils::trimAll($newPassword);
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        if (!$hash) { // 如果密码散列创建失败
            return Result::unknown('密码散列创建失败');
        }

        $user->password = $hash;
        $user->update_time = time() * 1000;
        $user->save();

        AuthService::logout();

        return Result::success();
    }

    /**
     * 通过用户名发送邮件.
     *
     * @param string $username
     *
     * @return Result
     */
    public function sendEmailCaptcha(string $username): Result
    {
        $email = UserModel::where('username', '=', $username)->value('email');

        if (!$email) {
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        return IndexService::sendEmailCaptcha($email);
    }

    /**
     * 重置密码
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $captcha  验证码
     *
     * @return Result
     */
    public function resetPassword(string $username, string $password, string $captcha): Result
    {
        if (!preg_match(ONCHAT_USERNAME_PATTERN, $username)) {
            return Result::create(Result::CODE_PARAM_ERROR, '用户名格式不规范！');
        }

        $result = $this->checkPassword($password);
        if ($result !== Result::CODE_SUCCESS) { // 如果用户密码不符合规范
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[$result]);
        }

        $user = UserModel::where('username', '=', $username)->find();

        if (!$user) {
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        if (!IndexService::checkEmailCaptcha($user->email, $captcha)) {
            return Result::create(Result::CODE_PARAM_ERROR, '验证码错误！');
        }

        $password = StrUtils::trimAll($password);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if (!$hash) { // 如果密码散列创建失败
            return Result::unknown('密码散列创建失败');
        }

        $user->password = $hash;
        $user->update_time = time() * 1000;
        $user->save();

        return Result::success();
    }

    /**
     * 颁发令牌.
     *
     * @param int    $id
     * @param string $username
     *
     * @return TokenFolder
     */
    private function issueTokens(int $id, string $username): TokenFolder
    {
        /** @var Token */
        $tokenService = TokenService::instance();

        // 首先生成续签令牌，保证JTI的缓存时间
        $payload = $tokenService->generate($id, ONCHAT_REFRESH_TOKEN_TTL);
        $payload->usr = ['username' => $username];
        $refreshToken = $tokenService->issue($payload);

        $payload = $tokenService->generate($id, ONCHAT_ACCESS_TOKEN_TTL);
        $payload->usr = ['username' => $username];
        $accessToken = $tokenService->issue($payload);

        return new TokenFolder($accessToken, $refreshToken);
    }

    /**
     * 通过用户标识获取用户信息
     * $field为数组时，返回数据集对象；为单个字段时只返回该字段的数据.
     *
     * @param string       $key   用户标识名
     * @param mixed        $value 用户标识值
     * @param string|array $field 需要获取的字段名
     *
     * @return mixed
     */
    public function getByKey(string $key, $value, $field)
    {
        $query = UserModel::join('user_info', 'user_info.user_id = user.id')
            ->where($key === 'id' ? 'user.id' : $key, '=', $value);

        if (is_array($field)) {
            return $query->field($field)->find();
        }

        return $query->value($field);
    }

    /**
     * 通过用户ID获取User.
     *
     * @param int $id
     *
     * @return Result
     */
    public function getUserById(int $id): Result
    {
        $user = UserModel::join('user_info', 'user_info.user_id = user.id')
            ->where('user.id', '=', $id)
            ->field(self::USER_FIELDS)
            ->find();

        if (!$user) {
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $storage = Storage::create();

        $user->avatarThumbnail = $storage->getThumbnailUrl($user->avatar);
        $user->avatar = $storage->getUrl($user->avatar);

        return Result::success($user);
    }

    /**
     * 通过用户名获取User.
     *
     * @param string $username
     *
     * @return Result
     */
    public function getUserByUsername(string $username): Result
    {
        $user = UserModel::where('user.username', '=', $username)->join('user_info', 'user_info.user_id = user.id')
            ->field(self::USER_FIELDS)->find();

        if (!$user) {
            return Result::create(Result::CODE_PARAM_ERROR, self::MSG[self::CODE_USER_NOT_EXIST]);
        }

        $storage = Storage::create();

        $user->avatarThumbnail = $storage->getThumbnailUrl($user->avatar);
        $user->avatar = $storage->getUrl($user->avatar);

        return Result::success($user);
    }

    /**
     * 获得用户在聊天室中的昵称.
     *
     * @param int $id         用户ID
     * @param int $chatroomId
     *
     * @return string|null
     */
    public function getNicknameInChatroom(int $id, int $chatroomId): ?string
    {
        return ChatMemberModel::where([
            'user_id'     => $id,
            'chatroom_id' => $chatroomId,
        ])->value('nickname');
    }

    /**
     * 通过用户ID获取用户名.
     *
     * @param int $id 用户ID
     *
     * @return string|null
     */
    public function getUsernameById(int $id): ?string
    {
        return UserModel::where('id', '=', $id)->value('username');
    }

    /**
     * 通过用户名获取用户ID.
     *
     * @param string $username 用户名
     *
     * @return int
     */
    public function getIdByUsername(string $username): ?int
    {
        return UserModel::where('username', '=', $username)->value('id');
    }

    /**
     * 检查用户密码是否符合规范.
     *
     * @param string $password
     *
     * @return int
     */
    public function checkPassword(string $password): int
    {
        $length = StrUtils::length($password);

        if ($length < ONCHAT_PASSWORD_MIN_LENGTH || $length > ONCHAT_PASSWORD_MAX_LENGTH) {
            return self::CODE_PASSWORD_IRREGULAR;
        }

        return Result::CODE_SUCCESS;
    }

    /**
     * 上传头像.
     *
     * @return Result
     */
    public function avatar(): Result
    {
        $userId = $this->getId();

        try {
            $storage = Storage::create();
            $image = Request::file('image');
            $path = $storage->getRootPath() . 'avatar/user/' . $userId . '/';
            $file = $image->md5() . '.' . $image->getOriginalExtension();

            $result = $storage->save($path, $file, $image);
            $storage->clear($path, Storage::AVATAR_MAX_COUNT);

            if ($result->isError()) {
                return $result;
            }

            $filename = $path . $file;

            // 更新新头像
            UserInfoModel::update(['avatar' => $filename], [
                'user_id' => $userId,
            ]);

            return Result::success([
                'avatar'          => $storage->getUrl($filename),
                'avatarThumbnail' => $storage->getThumbnailUrl($filename),
            ]);
        } catch (\Exception $e) {
            return Result::unknown($e->getMessage());
        }
    }

    /**
     * 查询该用户下所有的聊天室.
     *
     * @return Collection
     */
    public function getChatrooms($userId = null): Collection
    {
        return ChatMemberModel::join('chatroom', 'chat_member.chatroom_id = chatroom.id')
            ->where('chat_member.user_id', '=', $userId ?: $this->getId())
            ->field('chatroom.*')
            ->select();
    }

    /**
     * 获取私聊聊天室列表.
     *
     * @return Result
     */
    public function getPrivateChatrooms(): Result
    {
        $userId = $this->getId();
        $storage = Storage::create();

        $data = ChatMemberModel::join('user_info', 'user_info.user_id = chat_member.user_id')
            ->where('chat_member.chatroom_id', 'IN', function ($query) use ($userId) {
                // 私聊聊天室ID列表
                $query->table('chatroom')->join('chat_member', 'chatroom.id = chat_member.chatroom_id')->where([
                    'chatroom.type'       => ChatroomModel::TYPE_PRIVATE_CHAT,
                    'chat_member.user_id' => $userId,
                ])->field('chatroom.id');
            })
            ->where('chat_member.user_id', '<>', $userId)
            ->field([
                'chat_member.id',
                'chat_member.user_id as friendId',
                'chat_member.chatroom_id',
                'chat_member.nickname AS title',
                'chat_member.create_time',
                'chat_member.update_time',
                'user_info.signature AS content',
                'user_info.avatar AS avatarThumbnail',
            ])
            ->select();

        foreach ($data as $item) {
            $item->userId = $userId;
            $item->type = ChatSessionModel::TYPE_CHATROOM;
            $item->data = [
                'chatroomId'   => $item->chatroom_id,
                'chatroomType' => ChatroomModel::TYPE_PRIVATE_CHAT,
                'userId'       => $item->friendId,
            ];
            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatarThumbnail);

            unset($item->friendId, $item->chatroom_id);
        }

        return Result::success($data);
    }

    /**
     * 获取群聊聊天室列表.
     *
     * @return Result
     */
    public function getGroupChatrooms(): Result
    {
        $userId = $this->getId();
        $storage = Storage::create();

        $data = ChatMemberModel::join('chatroom', 'chatroom.id = chat_member.chatroom_id')
            ->where([
                'chat_member.user_id' => $userId,
                'chatroom.type'       => ChatroomModel::TYPE_GROUP_CHAT,
            ])
            ->field([
                'chat_member.id',
                'chat_member.chatroom_id',
                'chat_member.create_time',
                'chat_member.update_time',
                'chatroom.name AS title',
                'chatroom.description AS content',
                'chatroom.avatar AS avatarThumbnail',
            ])
            ->select();

        foreach ($data as $item) {
            $item->userId = $userId;
            $item->type = ChatSessionModel::TYPE_CHATROOM;
            $item->data = [
                'chatroomId'   => $item->chatroom_id,
                'chatroomType' => ChatroomModel::TYPE_GROUP_CHAT,
            ];
            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatarThumbnail);

            unset($item->chatroom_id);
        }

        return Result::success($data);
    }

    /**
     * 保存用户信息.
     *
     * @return Result
     */
    public function saveUserInfo(): Result
    {
        $userId = $this->getId();

        $nickname = Request::param('nickname/s') ?: $this->getUsername();
        $signature = Request::param('signature/s');
        $mood = Request::param('mood/d');
        $birthday = Request::param('birthday/d');
        $gender = Request::param('gender/d');
        $constellation = isset($birthday) ? DateUtils::getConstellation((int) $birthday / 1000) : null;

        if ($signature) {
            if (StrUtils::isEmpty($signature)) {
                $signature = null;
            } else {
                $signature = trim($signature);

                if (StrUtils::length($signature) > ONCHAT_SIGNATURE_MAX_LENGTH) {
                    return Result::create(self::CODE_SIGNATURE_IRREGULAR, self::MSG[self::CODE_SIGNATURE_IRREGULAR]);
                }
            }
        }

        if (!isset($mood)) {
            $mood = 0;
        }

        if (!isset($gender)) {
            $gender = 2;
        }

        $userInfo = UserInfoModel::update([
            'nickname'      => $nickname,
            'signature'     => $signature,
            'mood'          => $mood,
            'birthday'      => $birthday,
            'gender'        => $gender,
            'constellation' => $constellation,
        ], [
            'user_id' => $userId,
        ]);

        return Result::success($userInfo);
    }

    /**
     * 绑定电子邮箱.
     *
     * @param string $email   邮箱
     * @param string $captcha 验证码
     *
     * @return Result
     */
    public function bindEmail(string $email, string $captcha): Result
    {
        $userId = $this->getId();

        if (!IndexService::checkEmail($email)->data) { // 如果邮箱不可用
            return Result::create(Result::CODE_PARAM_ERROR);
        }

        if (!IndexService::checkEmailCaptcha($email, $captcha)) {
            return Result::create(Result::CODE_PARAM_ERROR, '验证码错误！');
        }

        $email = strtolower($email);

        UserModel::update([
            'id'          => $userId,
            'email'       => $email,
            'update_time' => time() * 1000,
        ]);

        return Result::success($email);
    }

    /**
     * 模糊搜索用户.
     *
     * @param string $keyword
     * @param int    $page
     *
     * @return Result
     */
    public function search(string $keyword, int $page): Result
    {
        $storage = Storage::create();

        $expression = "%{$keyword}%";
        $data = UserModel::join('user_info', 'user.id = user_info.user_id')->whereOr([
            ['user_info.nickname', 'LIKE', $expression],
            ['user.username', 'LIKE', $expression],
            ['user.id', 'LIKE', $expression],
        ])->page($page, 15)
            ->field(self::USER_FIELDS)
            ->select();

        foreach ($data as $item) {
            $item->avatarThumbnail = $storage->getThumbnailUrl($item->avatar);
            $item->avatar = $storage->getUrl($item->avatar);
        }

        return Result::success($data);
    }
}
