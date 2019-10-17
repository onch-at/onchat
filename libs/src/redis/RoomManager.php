<?php
namespace hypergo\redis;

use hypergo\redis\RedisManager;

class RoomManager extends RedisManager {
    public $rid; // 房间号

    const ONCHAT_ROOMS = "ONCHAT:ROOMS"; // 用于记录各个房间当前的客户端ID

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
     * @param mixed $rid 房间号
     * @return void
     */
	public function __construct1($rid) {
		$this->setRid($rid);
		$this->__construct0();
	}
    
    /**
     * 获得房间号
     *
     * @return mixed
     */
	public function getRid() {
		return $this->rid;
	}
    
    /**
     * 设置房间号
     *
     * @param mixed $rid 房间号
     * @return void
     */
	public function setRid($rid) {
		$this->rid = $rid;
    }

    /**
     * 获取房间
     *
     * @return string JSON
     */
    public function getRoom():string {
        return $this->getRedis()->hGet(self::ONCHAT_ROOMS, $this->getRid());
    }

    /**
     * 设置房间
     *
     * @param string $room
     * @return void
     */
    public function setRoom(string $room) {
        $this->getRedis()->hSet(self::ONCHAT_ROOMS, $this->getRid(), $room);
    }

    /**
     * 是否存在该房间
     *
     * @return boolean
     */
    public function hasRoom():bool {
        $room = $this->getRedis()->hGet(self::ONCHAT_ROOMS, $this->getRid());
        return ($room == false) ? false : true;
    }
    
    /**
     * 添加聊天者
     *
     * @param integer $fd frame id
     * @return void
     */
    public function addChatter(int $fd) {
        $room = json_decode($this->getRoom());
		$room[] = $fd;
		
        $this->setRoom(json_encode($room));
    }

    /**
     * 移除聊天者
     *
     * @param integer $fd frame id
     * @return void
     */
    public function removeChatter(int $fd) {
        $room = json_decode($this->getRoom()); // 拿到这个客户端所在房间的房间信息
		
		// 将该客户端移除出房间
		$key = array_search($fd, $room); //取得key
		unset($room[$key]);
		
		// 使用array_values()给数组重新排序，json数组的key必须是0，1，2...，否则json数组会变成对象
        $this->setRoom(json_encode(array_values($room)));
    }

    /**
     * 获取该房间聊天人数
     *
     * @return integer
     */
    public function getChatterNum():int {
        $room = json_decode($this->getRoom());

        return count($room);
    }
}
?>