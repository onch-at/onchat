<?php
if (empty($_POST)) exit;

require_once '../vendor/autoload.php';

use hypergo\utils\Session;
$time = time();

Session::start();

$msg = htmlspecialchars($_POST["msg"]); //接收提交的消息
$data = json_decode(file_get_contents("../log.json")); //读取数据文件并将其解析为数组

$count = count($data); //消息条数
$displayTime;

if ($count == 0) {
    $displayTime = date("Y-n-j H:i", $time);
} else {
    $lastMsgObj = $data[$count - 1]; //获得上一条消息的对象实例
    $displayTime = (($time - $lastMsgObj->time) >= 300) ? date("Y-n-j H:i", $time) : false;
}

if (!empty($_SESSION["login_info"])) {
    $info = json_decode($_SESSION["login_info"]);

    $data[] = [ //添加新消息数据进消息记录
        "name" => $info->username,
        "msg" => $msg,
        "time" => $time,
        "displayTime" => $displayTime, //如果当前时间戳减去上一条消息的时间戳大于或大于300s（5分钟）
    ];
    
    file_put_contents("../log.json", json_encode($data)); //更新数据
    
    exit(json_encode(true));
} else {
    exit(json_encode(false));
}
?>