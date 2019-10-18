<?php
require_once './vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;
use hypergo\utils\Code;
use hypergo\utils\Session;
use hypergo\redis\MessageManager;


$obj = json_decode('{
    "cmd": "chat",
    "data": {
        "msg": "message",
        "style": []
    }
}');
function isCmd($json) {
    $obj = json_decode($json);
    if (is_null($obj->cmd) or is_null($obj->data)) {
        return false;
    } else {
        return true;
    }
}

function inChatCmd(string $json):bool {
    $data = (json_decode($json))->data;
    if (is_null($data->msg) or is_null($data->style) or !is_array($data->style)) {
        return false;
    } else {
        return true;
    }
}

echo "<pre>";
var_dump(inChatCmd('{
    "cmd": "chat",
    "data": {
        "msgs": "message",
        "style": []
    }
}'));

//use WebSocket\Client; //is_null($obj->data->msg) or is_null($obj->data->style)

// $client = new Client("ws://47.100.50.203:9501");
// $client->send("Hello WebSocket.org!");

// echo $client->receive();
?>