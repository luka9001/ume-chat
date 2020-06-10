<?php
class DRedis {

    private $redis;
    private static $_instance = null; //定义单例模式的变量
    public static function getInstance() {
        if(empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct(){
        $this->redis = new \Redis();
        $result = $this->redis->connect(REDIS_HOST,REDIS_PORT);
        if($result === false) {
            throw new \Exception('redis connect error');
        }
    }

    public function set($key,$value){
        return $this->redis->set($key,$value);
    }

    public function setex($key, $value, $expire){
        return $this->redis->setex($key, $expire, $value);
    }

    public function setnx($key,$value){
        return $this->redis->setnx($key,$value);
    }

    public function seget($key){
        return $this->redis->get($key);
    }

    public function del($key){
        return $this->redis->del($key);
    }

    //其他操作

    /**
     * 防止clone多个实例
     */
    private function __clone(){

    }

    /**
     * 防止反序列化
     */
    private function __wakeup(){

    }
}
