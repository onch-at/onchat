<?php
namespace app\controller;

use app\BaseController;
use think\facade\Db;

class Init extends BaseController {
    public function index() {
        // 先去MySQL创建数据库
        // CREATE DATABASE IF NOT EXISTS onchat DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
        Db::execute("
            CREATE TABLE IF NOT EXISTS user (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                username    VARCHAR(30) NOT NULL UNIQUE KEY,
                password    VARCHAR(255) NOT NULL,
                email       VARCHAR(50) NULL UNIQUE KEY,
                telephone   CHAR(11) NULL UNIQUE KEY,
                create_time DATETIME NOT NULL,
                update_time DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Db::execute("
        //     CREATE TABLE IF NOT EXISTS user (
        //         id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        //         username    VARCHAR(30) NOT NULL,
        //         password    VARCHAR(60) NOT NULL,
        //         email       VARCHAR(50) NULL,
        //         telephone   CHAR(11) NULL,
        //         create_time DATE NOT NULL,
        //         update_time DATE NOT NULL,
        //         FOREIGN KEY (id) REFERENCES account(uid) ON DELETE CASCADE
        //     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        // ");
    }
}
