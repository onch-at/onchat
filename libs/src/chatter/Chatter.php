<?php
namespace hypergo\chatter;

class Chatter {
    public $uid; //用户User ID
    public $rid; //用户所在房间的房间号
    public $isLogin; //用户是否登录

    public function __construct(int $uid, int $rid, bool $isLogin) {
        $this->setUid($uid);
        $this->setRid($rid);
        $this->setLoginStatus($isLogin);
    }

    public function getUid():int {
        return $this->uid;
    }

    public function setUid(int $uid) {
        $this->uid = $uid;
    }

    public function getRid():int {
        return $this->rid;
    }

    public function setRid(int $rid) {
        $this->rid = $rid;
    }

    public function isLogin():bool {
        return $this->isLogin;
    }

    public function setLoginStatus(bool $isLogin) {
        $this->isLogin = $isLogin;
    }
}
?>