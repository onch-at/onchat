<?php
if (empty($_GET)) exit;

$quantity = 10; //每次读取的历史记录消息条数
$history = $_GET["history"]; //历史记录条数 15 10-15 (9-14)
//$history = 15;

$data = json_decode(file_get_contents("../log.json")); //读取数据文件并将其解析为数组

$array = [];

if ($history <= $quantity) { //如果历史记录条数少于大于10条，则直接输出
    $array["count"] = 0; //历史记录剩余条数
    $history--; //减一位，作为key

    foreach ($data as $key => $value) {
        $array[] = $value;

        if ($key >= $history) {
            exit(json_encode(array_reverse($array))); //反序数组，并输出
        }
    }
}


$var = $history - $quantity;
$array["count"] = $var; //历史记录剩余条数
$history--; //减一位，作为key

foreach ($data as $key => $value) {
    if ($key >= $var and $key <= $history) {
        $array[] = $value;
    }

    if ($key == $history) {
        exit(json_encode(array_reverse($array))); //反序数组，并输出
    }
}
?>