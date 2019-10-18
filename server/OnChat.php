<?php
require_once '../vendor/autoload.php';

use hypergo\utils\Session;
use hypergo\utils\Command;

use hypergo\redis\RoomManager;
use hypergo\redis\ChatterManager;
use hypergo\redis\MessageManager;

use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server as WebSocketServer;

class OnChat {
	public $server;
    public $redis;
    
	const WS_HOST = "0.0.0.0"; //WebSocket服务器的IP
    const WS_PORT = 9501; //WebSocket服务器的端口
    
    const SESSID_PREFIX = "PHPREDIS_SESSION:"; //session id 前缀
	
	public function __construct() {
		Session::start();
		
        $this->setRedis(new \Redis());
        $this->connectRedis();
		
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
		session_decode($this->getRedis()->get(self::SESSID_PREFIX . $sessid));
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
        $rid = $request->get["rid"]; // room id
        $sessid = $request->get["sessid"]; // session id

        if (!$this->hasSession($sessid)) return false; //如果不存在session，即未登录！
        
        $rm = new RoomManager($rid);
        if (!$rm->hasRoom()) return false; //如果不存在该房间

        $cm = new ChatterManager();
		
		// 取得session
		$this->getSession($sessid);
		$info = json_decode($_SESSION["login_info"]);
		
		$rm->addChatter($request->fd); //添加一个聊天者到房间
		
		$userdata = [
			"uid"	   => $info->uid,
			"username" => $info->username,
			"rid"      => $rid
		];
        $cm->setChatter($request->fd, $userdata); //设置一个聊天者的信息
        
        $mm = new MessageManager($rid);
        $this->getServer()->push($request->fd, $mm->read());
		
		echo "服务器与{$request->fd}号客户端握手成功！{$request->fd}号客户端已加入{$rid}号房间\n";
		echo "{$rid}号房间当前在线人数：" . $rm->getChatterNum() . "人\n\n";
    }
    
    /**
     * 关闭连接时
     *
     * @param WebSocketServer $server
     * @param [type] $fd
     * @return void
     */
    public function onClose(WebSocketServer $server, $fd) {
		$cm = new ChatterManager();
		$chatter = $cm->getChatter($fd); // 拿到这个客户端的信息
		
		$rm = new RoomManager($chatter->rid);
		$rm->removeChatter($fd); //将该客户端移除出房间
		
		$cm->removeChatter($fd); //移除掉这个客户端的信息
		
		echo "{$fd}号客户端与服务器连接中断！\n";
		echo "{$chatter->rid}号房间当前在线人数：" . $rm->getChatterNum() . "人\n\n";
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
                if (!$cmd->isChatCmd()) return false; //这不是一个正确的CHAT命令

                $cm = new ChatterManager();
                if (!$cm->hasChatter($frame->fd)) return false; //如果不存在该聊天者

                $chatter = $cm->getChatter($frame->fd); // 拿到这个客户端的信息
                
                $rm = new RoomManager($chatter->rid);
                $mm = new MessageManager($chatter->rid);
                
                $room = json_decode($rm->getRoom()); // 拿到这个客户端所在房间的房间信息
                
                $msg = htmlspecialchars($data->msg); //格式化一下消息
                $msgData = [
                    "uid"	=> $chatter->uid, // 消息发送者User ID
                    "msg"	=> $msg,		  // 消息内容
                    "style" => $data->style   // 样式
                ];
                $mm->write($msgData); //储存消息
                
                $msgJson = json_encode($mm->getMsgData()); //json打包刚刚MessageManager封装好的消息数据
                
                foreach ($room as $fd) { // 给该客户端所在的房间内的所有人广播
                    // 需要先判断是否是正确的websocket连接，否则有可能会push失败
                    if ($this->getServer()->isEstablished($fd)) {
                        $this->getServer()->push($fd, "{$frame->fd}号客户端说：" . $frame->data);
                        $this->getServer()->push($fd, $msgJson);
                    } else {
                        $rm->removeChatter($fd);
                        $cm->removeChatter($fd);
                    }
                }
                break;

            default:
                return false;
                break;
        }
	}
	
}
new OnChat();
?>