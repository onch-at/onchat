<?php
namespace hypergo\utils;

use hypergo\utils\Config;
use Medoo\Medoo;

class Database extends Medoo {
    private static $instance;
    /**
     * Undocumented function
     */
    private function __construct() {
        $config = Config::get("database");
        parent::__construct([
            'database_type' => $config["type"],
            'database_name' => $config["name"],
            'server'        => $config["server"],
            'username'      => $config["username"],
            'password'      => $config["password"],
            'charset'       => $config["charset"],
            'collation'     => $config["collation"],
        ]);
    }
    
    /**
     * Undocumented function
     *
     * @return Database
     */
    public static function getInstance():Database {
      if (!self::$instance instanceof self) {
        self::$instance = new self();
      }
  
      return self::$instance;
    }
    
    private function __clone() {

    }
}