<?php
namespace hypergo\utils;

class Config {
    public static function get($key) {
        return \getConfig($key);
    }
}
?>