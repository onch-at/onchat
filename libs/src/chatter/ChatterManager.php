<?php
namespace hypergo\chatter;

use hypergo\chatter\Chatter;

class ChatterManager {
    public $chatters;

    public function __construct() {
		$this->setChatters([]);
    }


    public function getChatters():array {
        return $this->chatters;
    }

    public function setChatters(array $chatters) {
        $this->chatters = $chatters;
    }

    /**
     * 获得一个聊天者对象
     *
     * @param integer $fd
     * @return Chatter
     */
    public function getChatter(int $fd) {
        $chatters = $this->getChatters();
        
        return $chatters[$fd];
    }

    /**
     * 设置一个聊天者
     *
     * @param integer $fd
     * @param Chatter $chatterData
     * @return void
     */
    public function setChatter(int $fd, Chatter $chatter) {
        $chatters = $this->getChatters();
        $chatters[$fd] = $chatter;

        $this->setChatters($chatters);
    }

    /**
     * 移除一个聊天者
     *
     * @param integer $fd
     * @return void
     */
    public function removeChatter(int $fd) {
        $chatters = $this->getChatters();
        unset($chatters[$fd]);
        
        $this->setChatters($chatters);
    }

    /**
     * 是否存在该聊天者
     *
     * @param integer $fd
     * @return boolean
     */
    public function hasChatter(int $fd):bool {
        $chatters = $this->getChatters();

        return isset($chatters[$fd]);
    }
 }
?>