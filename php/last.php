<?php
$data = json_decode(file_get_contents("../log.json")); //读取数据文件并将其解析为数组
$count = count($data); //消息记录条数

if ($count < 5) { //如果消息记录少于5条
    $data["count"] = $count;
    exit(json_encode($data)); //直接返回全部消息记录
}

$array = [];
$start = $count - 5; //得到起点key

$array["count"] = $count; //消息记录条数

foreach ($data as $key => $value) { //取最后5条消息存为一个记录
    if ($key >= $start) {
        $array[] = $value;
    }
}

exit(json_encode($array)); //返回最后的5条消息记录
?>