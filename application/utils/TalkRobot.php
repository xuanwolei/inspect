<?php
/*
 * @Author: ybc
 * @Date: 2020-09-26 18:09:39
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-26 18:23:15
 * @Description: file content
 */

/**
 * 钉钉机器人类
 * @author ybc 
 * @date(2018-7-16)
 */
Class TalkRobot{
	use ErrorTrait;

	protected static $instance;
	protected $token;

	CONST API_HOST = 'https://oapi.dingtalk.com/';
	CONST ROBOT_API = 'robot/send?access_token=';

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

	public function token(string $token){
		$this->token = $token;
		return $this;
	}

	/**
	 * 发送文本消息
	 * @param  string  $content   消息内容
	 * @param  string  $atMobiles @手机号
	 * @param  boolean $isAtAll   是否@所有人
	 * @return bool
	 */
	public function sendTextMsg(string $content, string $atMobiles = '', $isAtAll = false){
		$param = [
    		'msgtype' => 'text',
    		'text' => [
    			"content" =>  $content
    		],
    		'at' => [
    			"atMobiles" => [
    				$atMobiles,
    			],
    			'isAtAll' => $isAtAll
    		],
    	];    	
    	$param = json_encode($param);
    	$config = [
    		CURLOPT_HTTPHEADER => ["Content-type: application/json;charset='utf-8'"]
    	];
    	Curl::setConfig($config);
		$url    = $this->parseUrl(self::ROBOT_API, $this->token ?: C('dingtalk_robot_token'));
		$result = Curl::callWebServer($url, $param, 'post');

		return $this->parseResult($result);
	}

	/**
	 * 处理返回结果
	 * @param  mixed $result 返回结果集
	 * @return mixed
	 */
	protected function parseResult($result){
		if ($result['errcode'] != self::SUCC_CODE) {
			return $this->setError($result['errmsg'], $result['errcode']);
		}

		return $result;
	}

	protected function parseUrl(...$param){
		$url = self::API_HOST;
		foreach ($param as $key => $value) {
			$url .= $value;
		}

		return $url;
	}
}