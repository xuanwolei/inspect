<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 14:42:08
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-25 18:13:23
 * @Description: file content
 */

/**
 * 协程http 客户端
 * @author  ybc
 * @date 2018-7-13
 * 
 * http response statusCode
 *   -1：连接超时，服务器未监听端口或网络丢失，可以读取$errCode获取具体的网络错误码
 *   -2：请求超时，服务器未在规定的timeout时间内返回response
 *   -3：客户端请求发出后，服务器强制切断连接
 *
 */
Class CoroutineWebSocketClient extends Client{

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
	 * @param  string    $data     数据
	 * @return minxed
	 */
	public function request(string $data){
        if(!$this->client->connected){
			return false;
		}
		$this->client->push($data);
        $this->body = $this->client->recv();
        return $this->body;
	}

	protected function init(){
        $this->client = new Swoole\Coroutine\Http\Client($this->config['host'], $this->config['port'], $this->config['ssl']);
        
        $ret = $this->client->upgrade("/");
        $this->client->set($this->config['set']); 
	}
}