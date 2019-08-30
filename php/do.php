<?php
require_once '../vendor/autoload.php';

use hypergo\utils\Session;

Session::start();

$msg = $_POST["msg"]; //接收提交的消息
$data = json_decode(file_get_contents("../log.json")); //读取数据文件并将其解析为数组
$info = json_decode($_SESSION["login_info"]);

$data[] = [ //添加新消息数据进消息记录
    "name" => $info->username,
    "msg" => $msg,
    "time" => time(),
];

file_put_contents("../log.json", json_encode($data)); //更新数据

exit(json_encode($data));
?>