<?php
namespace hypergo\utils;

class Command {
    public $cmd;

    public function __construct(string $json) {
        $this->setCmd($json);
    }

    public function getCmd() {
        return $this->cmd;
    }

    public function setCmd(string $cmd) {
        $this->cmd = json_decode($cmd);
    }

    public function isCmd():bool {
        $cmd = $this->getCmd();
        if (is_null($cmd->cmd) or is_null($cmd->data)) {
            return false;
        } else {
            return true;
        }
    }

    public function isChatCmd():bool {
        $data = $this->getCmd()->data;
        if (is_null($data->msg) or is_null($data->style) or !is_array($data->style)) {
            return false;
        } else {
            return true;
        }
    }

    public function isHistoryCmd():bool {
        $data = $this->getCmd()->data;
        if (is_null($data->num) or !is_int($data->num)) {
            return false;
        } else {
            return true;
        }
    }
}
?>