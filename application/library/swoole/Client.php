<?php

/**
 * 客户端
 * @author  ybc
 * @date 2018-7-13
 * 
 * http response statusCode
 *   -1：连接超时，服务器未监听端口或网络丢失，可以读取$errCode获取具体的网络错误码
 *   -2：请求超时，服务器未在规定的timeout时间内返回response
 *   -3：客户端请求发出后，服务器强制切断连接
 *
 */
Abstract Class Client{

	protected $client;

	public $hostDetail = [
		'is_domain' => false,
		'host_ip' => '',//实际请求ip
		'dns_parsing_time' => 0, //dns解析时间
	];

	//配置
	protected $config = [
		'host' => '',
		'port' => '80',
		'ssl'  => false,
		'headers' => [
			"User-Agent" => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
		],
		'set' => [
			//超时时间（秒）
			'timeout' => 10.0
		],
		'cookies' => [
		],
	];

	/**
	 * 发送请求
	 */
	abstract public function request(string $path);

	/**
	 * 构造方法
	 * @param string|float $host  需要请求的host
	 * @param array  $config 配置
	 */
	public function __construct($host, array $config = []) {		
		$this->parseHost($host);
		$this->config = array_merge($this->config, $config);	
		$this->init();	
		return $this;
	}

	/**
	 * 获取配置
	 * @param  string $key
	 * @return mixed
	 */
	public function getConfig(string $key){
		if (isset($this->config[$key])) {
			return $this->config[$key];
		}

		return false;
	}

	/**
	 *	调用swoole_http_client中的方法
	 */
	public function __call($name , $args = array()){			
		if ( !method_exists($this->client, $name) ) {	
			return false;
		}

		$result = $this->client->$name(...$args);
		return $result;
	}

	public function __get($key){
		if (isset($this->client->$key)) {
			return $this->client->$key;
		}

		return false;
	}

	protected function parseHost($host) {
		$host = rtrim($host, '/');
		$address = explode('//', $host);
		//兼容不带协议的情况
		count($address) == 1 && $address['1'] = $address['0'];
		$array = explode(':', $address['1']);
		$this->config['host'] = current($array);
		$this->config['port'] = count($array) > 1 ? $array['1'] : 80;
		$this->config['ssl'] = $address['0'] == 'https:' ? true : false;
		if ($this->config['ssl'] && $this->config['port'] == 80) {
			$this->config['port'] = 443;
		}
		$this->hostDetail['host_ip'] = $this->config['host'];
		$this->hostDetail['is_domain'] = $this->isDomain($this->config['host']);
		//域名DNS解析
		if ($this->hostDetail['is_domain']) {
			$startTime = microtime(true);
			$ip = Swoole\Coroutine\System::gethostbyname($this->config['host'], AF_INET, 6);
			if (!$ip) {
				throw new DnsException("DNS解析超时，域名：{$this->config['host']}");
			}
			$this->hostDetail['host_ip'] = $ip;
			$this->hostDetail['dns_parsing_time'] = bcsub(microtime(true), $startTime, 2) * 1000;
		}
		
		return $this->config;
	}

	protected function parseData($data){
		if (empty($data)) {
			return $data;
		}
		if (is_array($data)) {
			return $data;
		}
		$param = explode('&', $data);
		$return = [];
		foreach ($param as $key => $value) {
		    if ($value == '') {
		        continue;
		    }
		    list($k,$v) = explode('=', $value);
		    $return[$k] = $v;
		}

		return $return;
	}

	protected function isDomain($str):bool{
		return (bool)preg_match("/[a-z]+/is", $str);
	}
}