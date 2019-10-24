<?php
namespace hypergo\redis;

use hypergo\redis\RedisManager;

class MessageManager extends RedisManager {
	public $rid; // 房间号

	private $msgData; // 刚刚写入的消息数据
	
	const ONCHAT_MSGRECORD = "ONCHAT:MSGRECORD:"; // 用于记录各个房间的消息
	
	public function __construct() {
		$a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = "__construct" . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
	}
    
    /**
     * Undocumented function
     *
     * @param integer $rid 房间号
     * @return void
     */
	public function __construct1(int $rid) {
		$this->setRid($rid);
		$this->__construct0();
	}
    
    /**
     * 获得房间号
     *
     * @return integer
     */
	public function getRid():int {
		return $this->rid;
	}
    
    /**
     * 设置房间号
     *
     * @param integer $rid 房间号
     * @return void
     */
	public function setRid(int $rid) {
		$this->rid = $rid;
	}
    
    /**
     * 获取刚刚写入的消息数据
     *
     * @return array
     */
	public function getMsgData():array {
		return $this->msgData;
	}
    
    /**
     * 设置刚刚写入的消息数据
     *
     * @param array $msgData 消息数据
     * @return void
     */
	public function setMsgData(array $msgData) {
		$this->msgData = $msgData;
	}
    
    /**
     * 获取该房间消息记录对应的Key
     *
     * @return string
     */
    public function getKey():string {
       return self::ONCHAT_MSGRECORD . $this->getRid();
	}
	
	/**
	 * 获取这个房间聊天记录的消息段条数
	 *
	 * @return boolean
	 */
	public function getLenght():bool {
		$key = $this->getKey();
		return $this->getRedis()->hLen($key);
	}
    
    /**
     * 写入消息到Redis
     *
     * @param array $msgData 消息数据
     * @return void
     */
	public function write(array $msgData) {
        // $msgData = [
		//     "uid"	  => uid,
		// 	   "username" => username,
		// 	   "rid"      => rid
        // ]
        //
		// ONCHAT:MSGRECORD:房间号 [
		//	   1 [ 每行10句消息 ],
		// 	   2 [ 1，2，3代表HASH长度 ],
		// 	   3 [ ],
		//     ...
		// ]
        $key = $this->getKey();
		$lenght = $this->getRedis()->hLen($key); //获取HASH长度
		
		$data = json_decode($this->getRedis()->hGet($key, $lenght)); //获取最新的那段消息
		$count = count($data); //获取最新那段消息的消息条数
		
		$time = time();
		$mid;
		$timeout;
		
		if ($count == 0) { //如果是第一条消息
			$mid = 1;
			$timeout = date("Y-n-j H:i", $time); //如果是整个房间的第一条消息，那肯定要显示一下时间
		} else {
			$lastMsgData = $data[$count - 1];  //拿到最后那条消息的数据
			
			$mid = $lastMsgData->mid + 1;
			//如果当前时间戳减去上一条消息的时间戳大于或大于300s（5分钟），则显示时间，或者为false
			$timeout = (($time - $lastMsgData->time) >= 300) ? date("Y-n-j H:i", $time) : false;
		}
		
		$msgData = [
			"mid"	   => $mid,				 // 消息Msg ID
			"uid"	   => $msgData["uid"],	 // 消息发送者User ID
			"msg"	   => $msgData["msg"],	 // 消息内容
			"time"	   => $time,			 // 消息发送的时间戳
			"timeout"  => $timeout,			 // 是否超时，超时则显示时间
			"style"    => $msgData["style"], // 消息样式
			"isCancel" => false 			 // 是否撤回（默认无撤回）
		];
		
		if ($count < 10) { //如果最新那段消息的消息条数小于10，则继续填充数据
		    $data[] = $msgData;
		
		    $this->getRedis()->hSet($key, $lenght, json_encode($data));
		} else { //否则将递增一个key
		    $this->getRedis()->hSet($key, $lenght + 1, "[" . json_encode($msgData) . "]");
		}
		
		$this->setMsgData($msgData); //储存一下刚刚写入的消息
	}
    
    /**
     * 读取某消息段
     *
     * @param integer $num 消息记录编号（默认为0，获取最新的消息段）
     * @return mixed （JSON / false）
     */
	public function read(int $num = 0) {
		// ONCHAT:MSGRECORD:房间号 [
		//	   1 [ 每行10句消息 ],
		// 	   2 [ 1，2，3代表HASH长度 ],
		// 	   3 [ ],
		//     ...
		// ]
        $key = $this->getKey();
        $lenght = $this->getRedis()->hLen($key); //获取HASH长度
        
        if ($num == 0 or $num == $lenght) { //如果num为0或者num等于lenght，则都返回最新的消息段
            return $this->getRedis()->hGet($key, $lenght); //返回最新的那段消息
        } elseif ($num >= 1 and $num < $lenght) { //如果num在1与lenght之间
            return $this->getRedis()->hGet($key, $num);
        } else {
            return false;
        }
	}
}
?>