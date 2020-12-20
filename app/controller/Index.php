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
use app\core\identicon\generator\ImageMagickGenerator;

class Index extends BaseController
{
    public function index()
    {
        $a1 = [];
        $a2 = array(
            0 => 3,
            1 => 12,
        );
        dump(
            array_diff($a1, $a2)
        );

        // dump(Db::execute("SHOW TABLES LIKE 'chat_record_1_0'"));
        // ChatroomService::addChatRecordTable((string)2000);
        // return response($content, 200, ['Content-Length' => strlen($content)])->contentType('image/png');



        // $identicon = new \Identicon\Identicon(new ImageMagickGenerator());

        // // 存储空间名称
        // $bucket = "onchat";
        // $ossClient = Client::getInstance();
        // echo $ossClient->signUrl($bucket, 'dev/avatar/user/1/9275585a6dd2afdb2034fff04a6d2d7d.webp', 3600, 'GET', [
        //     'x-oss-process' => 'style/original'
        // ]);
        // $user = User::select()->toArray();
        // foreach ($user as $item) {
        //     $object = Client::getRootPath() . 'avatar/user/' . $item['id'] . '/' . md5((string) DateUtil::now()) . '.png';
        //     $content = $identicon->getImageData($item['id'], 256, null, '#f0f0f0');
        //     try {
        //         $ossClient->putObject($bucket, $object, $content);
        //         UserInfo::update([
        //             'avatar' => $object,
        //         ], ['id' => $item['id']]);
        //     } catch (OssException $e) {
        //         printf(__FUNCTION__ . ": FAILED\n");
        //         printf($e->getMessage() . "\n");
        //     }
        // }

        // return 'okk';
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
