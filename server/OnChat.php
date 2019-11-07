<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

use hypergo\ai\TencentAI;

use hypergo\utils\Session;
use hypergo\utils\Command;
use hypergo\utils\Database;

use hypergo\room\RoomManager;
use hypergo\redis\MessageManager;

use hypergo\chatter\Chatter;
use hypergo\chatter\ChatterManager;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class OnChat {
	public $server;
    public $redis;

    public $roomManager;
    public $chatterManager;
    public $messageManager;

    public $ai;

	const WS_HOST = "0.0.0.0"; //WebSocket服务器的IP
    const WS_PORT = 9501; //WebSocket服务器的端口

    const ERROR_UNKONW = 0;
    const ERROR_NO_LOGIN = 1; //没有登录
    const ERROR_NO_ROOM = 2; //没有房间
    
    const SESSID_PREFIX = "PHPREDIS_SESSION:"; //session id 前缀
	
	public function __construct() {
		Session::start();
		
        $this->setRedis(new \Redis());
        $this->connectRedis();

        $rooms = [
            0 => [],
            1 => [],
            2 => []
        ];
        $this->setRoomManager(new RoomManager($rooms));
        $this->setChatterManager(new ChatterManager());
        $this->setMessageManager(new MessageManager());

        $this->ai = new TencentAI();
		
        $this->setServer(new WebSocketServer(self::WS_HOST, self::WS_PORT));
        $server = $this->getServer();
        $server->on("open", [$this, "onOpen"]);
        $server->on("close", [$this, "onClose"]);
        $server->on("message", [$this, "onMessage"]);
        $server->start();
    }
    
    /**
     * 获取WebSocket服务器实例
     *
     * @return WebSocketServer
     */
    public function getServer():WebSocketServer {
        return $this->server;
    }

    /**
     * 设置WebSocket服务器
     *
     * @param WebSocketServer $server
     * @return void
     */
    public function setServer(WebSocketServer $server) {
        $this->server = $server;
    }

    public function getRoomManager():RoomManager {
        return $this->roomManager;
    }

    public function setRoomManager(RoomManager $rm) {
        $this->roomManager = $rm;
    }

    public function getChatterManager():ChatterManager {
        return $this->chatterManager;
    }

    public function setChatterManager(ChatterManager $cm) {
        $this->chatterManager = $cm;
    }

    public function getMessageManager(int $rid):MessageManager {
        $this->messageManager->setRid($rid);
        return $this->messageManager;
    }

    public function setMessageManager(MessageManager $mm) {
        $this->messageManager = $mm;
    }

    /**
     * 获取Redis实例
     *
     * @return \Redis
     */
    public function getRedis() {
        return $this->redis;
    }

    /**
     * 设置Redis
     *
     * @param \Redis $redis
     * @return void
     */
    public function setRedis(\Redis $redis) {
        $this->redis = $redis;
    }

    /**
     * 连接Redis
     *
     * @return void
     */
    public function connectRedis() {
        $this->getRedis()->pconnect("127.0.0.1", 6379);
    }

    /**
     * 通过SESSION ID找到对应的SESSION并将其填充到$_SESSION
     *
     * @param string $sessid
     * @return void
     */
    public function getSession(string $sessid) {
        $_SESSION = []; //先清空掉session
	 	return session_decode($this->getRedis()->get(self::SESSID_PREFIX . $sessid));
    }
    
    /**
     * 是否存在该session
     *
     * @param string $sessid
     * @return boolean
     */
    public function hasSession(string $sessid):bool {
        $session = $this->getRedis()->get(self::SESSID_PREFIX . $sessid);
        return ($session == false) ? false : true;
    }

    public function broadcast(int $rid, string $msg) {
        $server = $this->getServer();
        $cm = $this->getChatterManager();
        $rm = $this->getRoomManager();

        foreach ($rm->getRoom($rid) as $fd) { // 给该客户端所在的房间内的所有人广播
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($server->isEstablished($fd)) {
                $server->push($fd, $msg);
            } else {
                $rm->removeChatter($rid, $fd);
                $cm->removeChatter($fd);
            }
        }
    }


    public function selectiveBroadcast(int $rid, string $msg, array $fds) {
        $server = $this->getServer();
        $cm = $this->getChatterManager();
        $rm = $this->getRoomManager();

        foreach ($rm->getRoom($rid) as $fd) { // 给该客户端所在的房间内的所有人广播
            // 需要先判断是否是正确的websocket连接，否则有可能会push失败
            if ($server->isEstablished($fd)) {
                if (!in_array($fd, $fds)) $server->push($fd, $msg); //如果fd不属于fds才发消息过去
            } else {
                $rm->removeChatter($rid, $fd);
                $cm->removeChatter($fd);
            }
        }
    }

    
    /**
     * 建立连接时
     *
     * @param WebSocketServer $server
     * @param Request $request
     * @return void
     */
	public function onOpen(WebSocketServer $server, Request $request) {
        $rid = (int) $request->get["rid"]; // room id
        $sessid = $request->get["sessid"]; // session id

        $cm = $this->getChatterManager();
        $rm = $this->getRoomManager();
        if (!$rm->hasRoom($rid)) { //如果不存在该房间
            $msgJson = json_encode([
                "cmd" => "error",
                "data" => [
                    "code" => self::ERROR_NO_ROOM,
                ]
            ]);
            $this->getServer()->push($request->fd, $msgJson);
            return false;
        }

        $rm->addChatter($rid, $request->fd); //将该聊天者添加到房间

        $this->getSession($sessid);

        $isLogin = User::checkLogin();
        if ((Database::getInstance()->error())[1] == 2006) { //如果数据库单例与数据库失去连接，则销毁该单例，并重新创建单例
        	Database::destroyInstance();
        	$isLogin = User::checkLogin();
        }

        if ($isLogin) { //如果不存在session，即未登录！
            $info = json_decode($_SESSION["login_info"]);

            $cm->setChatter($request->fd, new Chatter($info->uid, $rid, $isLogin)); //设置一个聊天者的信息

            $msgJson = json_encode([
                "cmd" => "info",
                "data" => [
                    "uid" => $info->uid,
                    "username" => $info->username
                ]
            ]);
            $this->getServer()->push($request->fd, $msgJson);

            $msgJson = json_encode([
                "cmd" => "join",
                "data" => [
                    "username" => $info->username
                ]
            ]);
            $this->selectiveBroadcast($rid, $msgJson, [$request->fd]);
        } else {
            $cm->setChatter($request->fd, new Chatter(0, $rid, $isLogin)); //设置一个聊天者的信息

            $msgJson = json_encode([
                "cmd" => "error",
                "data" => [
                    "code" => self::ERROR_NO_LOGIN,
                ]
            ]);
            $this->getServer()->push($request->fd, $msgJson);

            $msgJson = json_encode([
                "cmd" => "join",
                "data" => [
                    "username" => "游客{$request->fd}"
                ]
            ]);
            $this->selectiveBroadcast($rid, $msgJson, [$request->fd]);
        }
        
        $mm = $this->getMessageManager($rid);
        $lenght = $mm->getLenght();
        if ($lenght > 0) { //如果有消息记录
            $data = json_decode($mm->read());
            $data["lenght"] = $lenght;
            $msgJson = json_encode([
                "cmd" => "last",
                "data" => $data
            ]);
            $this->getServer()->push($request->fd, $msgJson);
        }
		
		echo "服务器与{$request->fd}号客户端握手成功！{$request->fd}号客户端已加入{$rid}号房间\n";
        echo "{$rid}号房间当前在线人数：" . $rm->getChatterNum($rid) . "人\n\n";
    }
    
    /**
     * 关闭连接时
     *
     * @param WebSocketServer $server
     * @param [type] $fd
     * @return void
     */
    public function onClose(WebSocketServer $server, $fd) {
        $cm = $this->getChatterManager();
        if (!$cm->hasChatter($fd)) return false;
        
        $chatter = $cm->getChatter($fd); // 拿到这个客户端的信息
        $uid = $chatter->getUid();
        $rid = $chatter->getRid();

        $rm = $this->getRoomManager();
		
		$rm->removeChatter($rid, $fd); //将该客户端移除出房间
        $cm->removeChatter($fd); //移除掉这个客户端的信息

        $username = User::getUsernameByUid($uid);
        if ((Database::getInstance()->error())[1] == 2006) { //如果数据库单例与数据库失去连接，则销毁该单例，并重新创建单例
        	Database::destroyInstance();
        	$username = User::getUsernameByUid($uid);
        }

        $msgJson = json_encode([
            "cmd" => "quit",
            "data" => [
                "username" => ($uid == 0) ? "游客{$fd}" : $username
            ]
        ]);
        $this->broadcast($rid, $msgJson);
        
		echo "{$fd}号客户端与服务器连接中断！\n";
		echo $rid . "号房间当前在线人数：" . $rm->getChatterNum($rid) . "人\n\n";
	}
    
    /**
     * 收到消息时
     *
     * @param WebSocketServer $server
     * @param Frame $frame
     * @return void
     */
	public function onMessage(WebSocketServer $server, Frame $frame) {
        $cmd = new Command($frame->data);
        $data = $cmd->getCmd()->data;

        if (!$cmd->isCmd()) return false; //这不是一个正确的命令

        switch ($cmd->getCmd()->cmd) {
            case "chat":
                if (!$cmd->isChatCmd() or (str_replace(" ", "", $data->msg) == "")) return false; //这不是一个正确的CHAT命令 / 该消息内容全为空格

                $cm = $this->getChatterManager();
                if (!$cm->hasChatter($frame->fd)) return false; //如果不存在该聊天者

                $chatter = $cm->getChatter($frame->fd); // 拿到这个客户端的信息

                if (!$chatter->isLogin()) { //如果他未登录
                    $msgJson = json_encode([
                        "cmd" => "error",
                        "data" => [
                            "code" => self::ERROR_NO_LOGIN,
                        ]
                    ]);
                    $this->getServer()->push($frame->fd, $msgJson);
                    return false;
                }
                
                $rm = $this->getRoomManager();
                $mm = $this->getMessageManager($chatter->getRid());
                
                $msg = htmlspecialchars($data->msg); //格式化一下消息
                $msgData = [
                    "uid"	=> $chatter->getUid(), // 消息发送者User ID
                    "msg"	=> $msg,		  // 消息内容
                    "style" => $data->style   // 样式
                ];
                $mm->write($msgData); //储存消息
                
                $msgJson = json_encode([
                    "cmd" => "chat",
                    "data" => $mm->getMsgData() //刚刚MessageManager封装好的消息数据
                ]);
                
                $this->broadcast($chatter->getRid(), $msgJson);
                
                if ($chatter->getRid() == 2) { //为2号房间接入TencentAI
                    $msg = $this->ai->dialog($data->msg);
                    if ($msg === false) return false;
                    
                    $msg = htmlspecialchars($msg); //格式化一下消息
                    $msgData = [
                        "uid"	=> "tencent-ai", // 消息发送者User ID
                        "msg"	=> $msg,		  // 消息内容
                        "style" => []   // 样式
                    ];
                    $mm->write($msgData); //储存消息
                    
                    $msgJson = json_encode([
                        "cmd" => "chat",
                        "data" => $mm->getMsgData() //刚刚MessageManager封装好的消息数据
                    ]);
                    
                    $this->broadcast($chatter->getRid(), $msgJson);
                    echo "TencentAI成功解析消息并发起回复\n";
                }

                break;

            case "history":
                if (!$cmd->isHistoryCmd()) return false; //这不是一个正确的HISTORY命令

                $mm = $this->getMessageManager($data->rid);
                
                $msgJson = json_encode([
                    "cmd" => "history",
                    "data" => json_decode($mm->read($data->num))
                ]);
                $this->getServer()->push($frame->fd, $msgJson);
                break;

            case "ping":
                $this->getServer()->push($frame->fd, '{"cmd":"ping","data":{}}');
                break;

            default:
                return false;
                break;
        }
	}
	
}
new OnChat();
?>