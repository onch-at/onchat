<?php
$oldCount = $_POST["count"]; //旧消息记录条数

$data = json_decode(file_get_contents("../log.json")); //读取数据文件并将其解析为数组
$count = count($data); //消息记录条数

$array = [];

if ($count > $oldCount) { //如果当前记录条数大于旧记录条数，即有新消息
    $array["count"] = $count ; //消息记录条数

    foreach ($data as $key => $value) {
        if ($key >= $oldCount) {
            $array[] = $value;
        }
    }
}

exit(json_encode($array));
?>