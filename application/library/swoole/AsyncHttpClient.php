<?php
/**
 * async http 客户端
 * @author  ybc
 * @date 2018-7-13
 * 
 * http response statusCode
 *   -1：连接超时，服务器未监听端口或网络丢失，可以读取$errCode获取具体的网络错误码
 *   -2：请求超时，服务器未在规定的timeout时间内返回response
 *   -3：客户端请求发出后，服务器强制切断连接
 *
 */
Class AsyncHttpClient extends Client{

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
	 * @param  callable $callable 回调函数
	 * @param  string   $method   method
	 * @param  mixed    $data     数据
	 * @return minxed
	 */
	public function request(string $path, $callable = '', $method = 'get', $data = ''){
		$this->init();
		if (strtolower($method) == 'get') {
			return $this->httpGet($path, $callable);
		}

		return $this->httpPost($path, $data, $callable);
	}

	/**
	 * get请求
	 * @param  string   $path     请求路径
	 * @param  callable $callable 回调函数
	 * @return minxed
	 */
	protected function httpGet(string $path, $callable = ''){
		return $this->client->get($path, $callable);
	}

	/**
	 * post请求
	 * @param  string   $path     请求路径
	 * @param  mixed   $data     数据
	 * @param  callable $callable 回调函数
	 * @return minxed
	 */
	protected function httpPost(string $path, $data = '', $callback = ''){
		$data = $this->parseData($data);
		return $this->client->post($path, $data, $callback);
	}

	protected function init(){
		$this->client = new swoole_http_client($this->config['host'], $this->config['port'], $this->config['ssl']);
		$this->client->setHeaders($this->config['headers']);
        $this->client->set($this->config['set']); 
        $this->client->setCookies($this->config['cookies']);
	}
}