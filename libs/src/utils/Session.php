<?php
namespace hypergo\utils;

use hypergo\utils\Config;

class Session {
    public static function start() {
        $config = Config::get("session");
        
        if (ini_get("session.save_handler") !== $config["save_handler"]) ini_set("session.save_handler", $config["save_handler"]);
        if (ini_get("session.save_path") !== $config["save_path"]) ini_set("session.save_path", $config["save_path"]);
        if (ini_get("session.gc_maxlifetime") !== "{$config['gc_maxlifetime']}") ini_set("session.gc_maxlifetime", $config["gc_maxlifetime"]);
        
        if (!session_id()) session_start();
    }
}
?>