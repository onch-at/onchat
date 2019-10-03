<?php
/**
 * Undocumented function
 *
 * @param [type] $key
 * @return void
 */
function getConfig($key) {
    $config = [
        "database" => [
            "type"      => "mysql",
            "name"      => "onchat",
            "server"    => "localhost",
            "username"  => "root",
            "password"  => "root",
            "charset"   => "utf8mb4",
            "collation" => "utf8mb4_general_ci",
        ],

        "session" => [
            "save_handler"   => "redis",
            "save_path"      => "tcp://127.0.0.1:6379",
            "gc_maxlifetime" => 86400,
        ]
    ];

    return $config[$key];
}
?>