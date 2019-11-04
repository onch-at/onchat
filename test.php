<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<?php
require_once './vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;
use hypergo\utils\Code;
use hypergo\utils\Session;
use hypergo\redis\MessageManager;

$db = Database::getInstance();
echo User::getUsernameByUid(1);
echo "<hr>";
echo "<pre>";
var_dump($db->info());
echo "<hr>";
var_dump($db->error());
//echo phpinfo();
//use WebSocket\Client; //is_null($obj->data->msg) or is_null($obj->data->style)

// $client = new Client("ws://47.100.50.203:9501");
// $client->send("Hello WebSocket.org!");

// echo $client->receive();
?>