<?php

declare(strict_types=1);

namespace app\controller;

use HTMLPurifier;
use app\model\User;
use think\Response;
use think\facade\Db;
use app\BaseController;
use app\model\Chatroom as ChatroomModel;
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
use app\core\util\Throttle;
use app\core\util\Tpl;
use app\listener\task\SendMail;
use app\model\ChatRequest;
use think\facade\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Swoole\Server;
use think\swoole\Job;

class Index extends BaseController
{

    public function index(Server $server)
    {
        $path = root_path() . '/resource/tpl/mail/captcha.html';

        $server->task(new Job([SendMail::class, 'handle'], [
            'from'      => ['system@chat.hypergo.net', 'OnChat'],
            'addresses' => ['hyperlife1119@qq.com'],
            'isHTML'    => true,
            'subject'   => 'OnChat：电子邮箱验证',
            'body'      => Tpl::replace(file_get_contents($path), ['captcha' => 6666])
        ]));
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
