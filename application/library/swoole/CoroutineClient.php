<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 14:42:08
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-24 16:47:27
 * @Description: tcp/udp客户端
 */
Class CoroutineClient extends Client{

	/**
	 * 构造方法
	 * @param string|float $host  需要请求的host
	 * @param array  $config 配置
	 */
	public function __construct($host, array $config = []) {
		parent::__construct($host, $config);		
		return $this;
	}

	/**
	 * 发送请求
	 * @param  string   $path     请求路径
	 * @param  string   $method   method
	 * @param  mixed    $data     数据
	 * @return minxed
	 */
	public function request(string $data){
		if(!$this->client->connected){
			return false;
		}
		$this->client->send($data);
		$res = $this->client->recv($this->config['timeout']);
		$this->body = $res ?? '';
		return $res;
	}

	protected function init(){
        $this->client = new Swoole\Coroutine\Client($this->config['connect_type']);
        $this->client->set($this->config['set']); 
		$this->client->connect($this->config['host'], $this->config['port'], 3); 
	}

	public function __get($key){
		$val = parent::__get($key);
		if ($val !== false) {
			return $val;
		}
		if ($key == 'statusCode'){
			return $this->client->errCode;
		}
		return false;
	}
}