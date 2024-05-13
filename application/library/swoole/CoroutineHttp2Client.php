<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 14:42:08
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-18 19:47:06
 * @Description: 协程http2.0 客户端
 * @doc:https://wiki.swoole.com/#/coroutine_client/http2_client?id=recv
 */
Class CoroutineHttp2Client extends Client{

    public $request; 
    public $response;
    public $body = '';

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
	public function request(string $path, $method = 'GET', $data = ''){
		$this->config['request'] = [
			'path'   => $path,
			'method' => $method,
			'data'   => $data,
        ];
        $this->request->path   = $path;
		$this->request->method = strtoupper($method);
		if (!empty($data)) {
			$data = $this->parseData($data);
			$this->request->data =$data;
        }
        $streamId = $this->client->send($this->request);
        
        $this->response = $this->client->recv();
        $this->body = $this->response->data;
        return $this->response;
	}

	protected function init(){
        $this->client = new Swoole\Coroutine\Http2\Client($this->config['host'], $this->config['port'], $this->config['ssl']);
        $this->client->set($this->config['set']); 
        $this->request = new Swoole\Http2\Request;
		$this->request->headers = $this->config['headers'];
        $this->request->cookies = $this->config['cookies'];
    }
    
    public function __get($key){
		if (isset($this->client->$key)) {
			return $this->client->$key;
        }
        if (isset($this->response->$key)) {
			return $this->response->$key;
		}

		return false;
	}
}