<?php

declare(strict_types=1);

namespace app\controller;

use HTMLPurifier;
use app\model\User;
use think\Response;
use think\facade\Db;
use app\BaseController;
use app\model\Chatroom;
use app\model\UserInfo;
use think\facade\Cache;
use app\core\oss\Client;
use HTMLPurifier_Config;
use app\model\ChatMember;
use app\model\ChatRecord;
use app\model\ChatSession;
use OSS\Core\OssException;
use app\model\FriendRequest;
use app\core\util\Arr as ArrUtil;
use app\core\util\Sql as SqlUtil;
use think\captcha\facade\Captcha;
use app\core\util\Date as DateUtil;
use app\core\oss\Client as OssClient;
use Identicon\Generator\SvgGenerator;
use app\core\service\User as UserService;
use app\core\service\Friend as FriendService;
use app\core\service\Chatroom as ChatroomService;
use app\core\service\Chat as ChatService;
use app\core\identicon\generator\ImageMagickGenerator;
use app\model\ChatRequest;

class Index extends BaseController
{
    public function index()
    {
        dump((int)true);
    }

    /**
     * 验证码
     *
     * @return Response
     */
    public function captcha(): Response
    {
        return Captcha::create();
    }
}
