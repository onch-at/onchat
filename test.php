<?php
require_once './vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;
use hypergo\utils\Code;
use hypergo\utils\Session;
use hypergo\redis\MessageManager;

//$mm = new MessageManager();
var_dump(new MessageManager());
echo "OK";

//use WebSocket\Client;

// $client = new Client("ws://47.100.50.203:9501");
// $client->send("Hello WebSocket.org!");

// echo $client->receive();
?>