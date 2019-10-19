<?php
require_once './vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;
use hypergo\utils\Code;
use hypergo\utils\Session;
use hypergo\redis\MessageManager;
echo "<PRE>";
var_dump(json_decode('{"cmd":"last","data":[{"mid":51,"uid":"1","msg":"2222","time":1571415572,"timeout":false,"style":[],"isCancel":false},{"mid":52,"uid":"1","msg":"11111","time":1571416707,"timeout":"2019-10-19 00:38","style":[],"isCancel":false},{"mid":53,"uid":"1","msg":"111111111111111","time":1571416773,"timeout":false,"style":[],"isCancel":false},{"mid":54,"uid":"1","msg":"2222","time":1571417485,"timeout":"2019-10-19 00:51","style":[],"isCancel":false},{"mid":55,"uid":"1","msg":"3333333333333","time":1571417616,"timeout":false,"style":[],"isCancel":false},{"mid":56,"uid":"1","msg":"1111","time":1571454676,"timeout":"2019-10-19 11:11","style":[],"isCancel":false}]}'));

//use WebSocket\Client; //is_null($obj->data->msg) or is_null($obj->data->style)

// $client = new Client("ws://47.100.50.203:9501");
// $client->send("Hello WebSocket.org!");

// echo $client->receive();
?>