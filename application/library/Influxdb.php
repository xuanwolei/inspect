<?php
/*
 * @Author: ybc
 * @Date: 2020-09-14 10:54:36
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-23 20:57:13
 * @Description: file content
 */

Class Influxdb{
    use ErrorTrait;

    protected static $instance;
    public $client;
    public $dbClient;

	CONST SUCC_CODE = 0;
	
	/**
	 * 获取实例
	 * @return self
	 */
	public static function getInstance(){
		if (!self::$instance instanceof self) {
			self::$instance = new self();
		}

		return self::$instance;
    }
    
    protected function __construct(){
        $this->client = new \InfluxDB\Client(C('influx_host'), C('influx_port'));
		$this->dbClient = $this->client->selectDB(C('influx_database'));
    }

    public static function __callStatic($name, $arguments){
        $instance = static::getInstance();
        if (method_exists($instance->dbClient, $name)) {
            return $instance->dbClient->$name(...$arguments);
        }

        throw new Exception("method not fund", 0);       
    }
}