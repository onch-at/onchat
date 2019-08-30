<?php
function getConfig($key) {
    $config = [
        "database" => [
            "type"      => "mysql",
            "name"      => "onchat",
            "server"    => "localhost",
            "username"  => "root",
            "password"  => "",
            "charset"   => "utf8mb4",
            "collation" => "utf8mb4_general_ci",
        ],

        "session" => [
            "save_handler"   => "memcache",
            "save_path"      => "tcp://127.0.0.1:11211",
            "gc_maxlifetime" => 86400,
        ]
    ];

    return $config[$key];
}
?>