<?php
require_once './vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;
use hypergo\utils\Code;
use hypergo\utils\Session;
use hypergo\redis\MessageManager;

var_dump(User::getAge(2000,11,19));
//echo phpinfo();
//use WebSocket\Client; //is_null($obj->data->msg) or is_null($obj->data->style)

// $client = new Client("ws://47.100.50.203:9501");
// $client->send("Hello WebSocket.org!");

// echo $client->receive();
?>