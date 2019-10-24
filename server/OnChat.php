<?php
require_once '../vendor/autoload.php';

use hypergo\user\User;

use hypergo\utils\Session;
use hypergo\utils\Command;

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

        $isLogin = User::checkLogin();

        if ($this->getSession($sessid) and $isLogin) { //如果不存在session，即未登录！
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
        } else {
            $cm->setChatter($request->fd, new Chatter(0, $rid, $isLogin)); //设置一个聊天者的信息

            $msgJson = json_encode([
                "cmd" => "error",
                "data" => [
                    "code" => self::ERROR_NO_LOGIN,
                ]
            ]);
            $this->getServer()->push($request->fd, $msgJson);
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
        $rm = $this->getRoomManager();
		$cm = $this->getChatterManager();
		$chatter = $cm->getChatter($fd); // 拿到这个客户端的信息
		
		$rm->removeChatter($chatter->getRid(), $fd); //将该客户端移除出房间
        $cm->removeChatter($fd); //移除掉这个客户端的信息

		echo "{$fd}号客户端与服务器连接中断！\n";
		echo $chatter->getRid() . "号房间当前在线人数：" . $rm->getChatterNum($chatter->getRid()) . "人\n\n";
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

                if (!$chatter->isLogin()) return false; //如果他未登录
                
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
                
                foreach ($rm->getRoom($chatter->getRid()) as $fd) { // 给该客户端所在的房间内的所有人广播
                    // 需要先判断是否是正确的websocket连接，否则有可能会push失败
                    if ($this->getServer()->isEstablished($fd)) {
                        $this->getServer()->push($fd, $msgJson);
                    } else {
                        $rm->removeChatter($chatter->getRid(), $fd);
                        $cm->removeChatter($fd);
                    }
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