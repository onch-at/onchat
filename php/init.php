<?php
require_once '../vendor/autoload.php';
use hypergo\utils\Database;

// CREATE DATABASE IF NOT EXISTS onchat DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
$database = Database::getInstance();
$database->create("account", [
    "uid" => [
        "INT",
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
]);


?>