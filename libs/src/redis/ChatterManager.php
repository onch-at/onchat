<?php
namespace hypergo\redis;

use hypergo\redis\RedisManager;

class ChatterManager extends RedisManager {

    const ONCHAT_CHATTER = "ONCHAT:CHATTER"; // 由于记录客户端的信息

    public function __construct() {
		$a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = "__construct" . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    /**
     * 获得一个聊天者对象
     *
     * @param integer $fd
     * @return object
     */
    public function getChatter(int $fd) {
        $chatter = json_decode($this->getRedis()->hGet(self::ONCHAT_CHATTER, $fd));
        return $chatter;
    }

    /**
     * 设置一个聊天者
     *
     * @param integer $fd
     * @param array $chatterData
     * @return void
     */
    public function setChatter(int $fd, array $chatterData) {
        $this->getRedis()->hSet(self::ONCHAT_CHATTER, $fd, json_encode($chatterData));
    }

    /**
     * 移除一个聊天者
     *
     * @param integer $fd
     * @return void
     */
    public function removeChatter(int $fd) {
        $this->getRedis()->hDel(self::ONCHAT_CHATTER, $fd);
    }

    /**
     * 是否存在该聊天者
     *
     * @param integer $fd
     * @return boolean
     */
    public function hasChatter(int $fd):bool {
        $chatter = json_decode($this->getRedis()->hGet(self::ONCHAT_CHATTER, $fd));
        return ($chatter == false) ? false : true;
    }
 }
?>