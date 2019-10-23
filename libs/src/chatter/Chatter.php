<?php
namespace hypergo\chatter;

class Chatter {
    public $uid; //用户User ID
    public $rid; //用户所在房间的房间号

    public function __construct(int $uid, $rid) {
        $this->setUid($uid);
        $this->setRid($rid);
    }

    public function getUid():int {
        return $this->uid;
    }

    public function setUid(int $uid) {
        $this->uid = $uid;
    }

    public function getRid() {
        return $this->rid;
    }

    public function setRid($rid) {
        $this->rid = $rid;
    }
}
?>