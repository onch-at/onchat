<?php
require_once '../vendor/autoload.php';
use hypergo\user\User;
use hypergo\utils\Database;

// CREATE DATABASE IF NOT EXISTS onchat DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
$database = Database::getInstance();
$database->create("account", [
    "uid" => [
        "INT",
        "UNSIGNED",
        "NOT NULL",
        "AUTO_INCREMENT",
        "PRIMARY KEY"
    ],
    "username" => [
        "VARCHAR(30)",
        "NOT NULL"
    ],
    "password" => [
        "VARCHAR(60)",
        "NOT NULL"
    ]
], [
    "ENGINE" => "INNODB"
]);

$database->create("user_info", [
    "uid" => [ //UID外键（NOT NULL）
        "INT",
        "UNSIGNED",
        "NOT NULL",
        "PRIMARY KEY"
    ],
    "nickname" => [ //昵称（NOT NULL）
        "VARCHAR(30)",
        "NOT NULL"
    ],
    "avatar" => [ //头像
        "VARCHAR(100)",
        "NULL"
    ],
    "signature" => [ //个性签名
        "VARCHAR(50)",
        "NULL"
    ],
    "mood" => [ //心情
        "TINYINT",
        "UNSIGNED",
        "NULL"
    ],
    "birthday" => [ //生日（NOT NULL）
        "DATE",
        "NOT NULL"
    ],
    "sex" => [ //性别
        "TINYINT",
        "UNSIGNED",
        "DEFAULT 0"
    ],
    // "age" => [ //年龄（NOT NULL） 年龄需要动态计算
    //     "TINYINT",
    //     "UNSIGNED",
    //     "NOT NULL"
    // ],
    "constellation" => [ //星座（NOT NULL）
        "TINYINT",
        "UNSIGNED",
        "NOT NULL"
    ],
    "email" => [ //邮箱
        "VARCHAR(50)",
        "NULL"
    ],
    "FOREIGN KEY (uid) REFERENCES account(uid) ON DELETE CASCADE"
], [
    "ENGINE" => "INNODB"
]);

// $datas = $database->select("account", ["uid", "username"]);
// $timestamp = time();
// $birthday = getdate($timestamp);
// foreach ($datas as $data) {
//     $database->insert("user_info", [ 
//         "uid" => $data["uid"],
//         "nickname" => $data["username"],
//         "birthday" => date("Y-m-d", $timestamp),
//         "constellation" => User::getConstellation($birthday["mon"], $birthday["mday"])
//     ]);
// }
// echo "<pre>";
// var_dump($data);
// echo "<hr>";
// print_r($database->error());
// echo "<hr>";
// echo $database->last();
?>