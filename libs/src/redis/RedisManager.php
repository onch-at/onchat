<?php
namespace hypergo\redis;

abstract class RedisManager {
    public $redis; // Redis对象实例

    public function __construct0() {
		$this->setRedis(new \Redis());
		$this->connectRedis();
    }
    
	/**
     * 获取Redis实例
     *
     * @return \Redis
     */
    public function getRedis() {
        return $this->redis;
    }

    /**
     * 设置Redis
     *
     * @param \Redis $redis
     * @return void
     */
    public function setRedis(\Redis $redis) {
        $this->redis = $redis;
	}
	
	/**
     * 连接Redis
     *
     * @return void
     */
    public function connectRedis() {
		$this->getRedis()->pconnect("127.0.0.1", 6379);
  }
}
?>