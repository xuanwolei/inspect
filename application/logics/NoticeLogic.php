<?php

/**
 * 通知处理
 * @author  ybc 
 * @date(2018-7-16)
 */
Class NoticeLogic{
	use ErrorTrait;

	protected $notice;
	protected $requestlogic;
	protected $redis;
	protected $data;

	//通知规则
	protected $rule = [
		'min_notice_time' => 3000, //超时通知起始时间
		'notice_levels' => [
			1 => [
				'interval_time'     => 120,  //每次通知间隔
				'limit_time'        => 3600,    //通知限制时间
				'limit_num'         => 30,		 //限制时间内最多通知次数
				'ip_exception_num'  => 2,
				'ip_exception_time' => 3* 60,
				'exception_num'     => 2,
				'exception_time'    => 3 * 60,
			],
			2 => [
				'interval_time'  => 900,  //每次通知间隔
				'limit_time'     => 3600,    //通知限制时间
				'limit_num'      => 5,		 //限制时间内最多通知次数
				'ip_exception_num'  => 2,
				'ip_exception_time' => 3* 60,
				'exception_num'  => 4,
				'exception_time' => 6 * 60,
			],
			3 => [
				'interval_time'  => 1800,  //每次通知间隔
				'limit_time'     => 3600,    //通知限制时间
				'limit_num'      => 2,		 //限制时间内最多通知次数
				'ip_exception_num'  => 3,
				'ip_exception_time' => 4* 60,
				'exception_num'  => 6,
				'exception_time' => 7 * 60,
			],
		],
	];

	protected $httpErrorCode = [
		'-1' => '连接超时，服务器未监听端口或网络丢失',
		'-2' => '接口响应超时',
		'-3' => '客户端请求发出后，服务器强制切断了连接'
	];

	protected $redisKey = [];
	//默认通知级别
	CONST DEFAULT_NOTICE_LEVEL = 3;
	//请求成功http code
	CONST REQUEST_SUCC_CODE = 200;
	//固定格式
	CONST RESPONES_VALIDATE_TYPE_1 = 1;
	//类型验证
	CONST RESPONES_VALIDATE_TYPE_2 = 2;
	//not validate time
	CONST NOT_VALIDATE_TIME = 10500;

	CONST STATUS_SUCC = 0;
	CONST STATUS_ERR = 1;
	CONST STATUS_ERR_NOT_NOTICE = 2;

	/**
	 * 构造
	 * @param NoticeInterfaceLogic $notice
	 * @param array $data 通知信息
	 */
	public function __construct(NoticeInterfaceLogic $notice, RequestInterfaceLogic $requestlogic, array $data){
		$this->redis 					= Tool::getRedis();
		$this->notice                   = $notice;
		$this->requestlogic 	        = $requestlogic;
		$this->data                     = $data;
		$this->redisKey['interval_key'] = parsePrefix("notcie_{$data['path']}");
		$this->redisKey['limit_key']    = parsePrefix("notcie_num_{$data['path']}");
		$this->redisKey['judge_key']    = parsePrefix(date('mdHi',strtotime($this->data['request_time'])).md5($this->data['path']));
		$this->redisKey['exception_key'] = parsePrefix("exception_{$data['path']}");
	}

	public function run(){
		//验证是否有异常
		$bool = $this->validate($this->data);
		if ($bool) {
			return $this->addLog(self::STATUS_SUCC);
		}
		//验证是否需要通知
		$this->addLog(self::STATUS_ERR, 'requestError');
		if (!$this->isNotice()) {
			return true;
		}

		//发送通知
		$redis = Tool::getRedis();
		$ip 	 = HOST_NAME;
		$outcome = json_encode($redis->hGetAll($this->redisKey['judge_key']));
		$head    = $this->getError();
		$hostIp = '';
		if (!empty($this->data['host_detail']['host_ip'])) {
			$hostIp = "\n请求IP：{$this->data['host_detail']['host_ip']}";
		}
		if (!empty($this->data['host_detail']['is_domain'])) {
			$dnsParsingTime = "\nDNS解析时间：{$this->data['host_detail']['dns_parsing_time']}ms";
		}
		$addr = getAdminProjectAddr($this->data['id']);
		$content = "项目：{$this->data['project']['name']}\n类型：{$this->data['project']['type']}\n监控机器：{$ip}{$hostIp}\n判定结果：{$outcome}\n{$head}\n编号：{$this->data['number']}\n请求时间：{$this->data['request_time']}\nhost：{$this->data['path']}{$dnsParsingTime}\n响应时间：{$this->data['response_time']}ms\nbody length：".mb_strlen($this->data['response_body'])."\n查看详情：{$addr}";
		$result  = $this->notice->send($content);
		return $result;		
	}

	/**
	 * 验证是否异常
	 * @param  array  $data 
	 * @return bool
	 */
	protected function validate(array $data){		
		
		$level = $data['level'] ?? self::DEFAULT_NOTICE_LEVEL;
		$rule = $this->rule['notice_levels'][$level];
		if (!$rule){
			uecho("level:{$level} not not found,host:{$data['path']}", 'noticeValidateError');
			return true;
		}
		//DNS解析错误
		if ($data['response_status'] == ErrorCode::ERROR_DNS_PARSE_FAIL) {
			return $this->setError("DNS解析失败"); 
		}
		if (isset($this->httpErrorCode[$data['response_status']])) {
			return $this->setError("请求错误：".$this->httpErrorCode[$data['response_status']]); 
		}
		if (!$this->requestlogic->valid($data, $rule)){
			return $this->setError($this->requestlogic->getError());
		}
		if ($data['error_code'] > 0) {
			return $this->setError("网络错误：错误码({$data['error_code']})"); 
		}
		//接口返回内容验证
		if ($data['validate_type'] == self::RESPONES_VALIDATE_TYPE_1 && $data['response_body'] != $data['validate_rule']) {
			return $this->setError("返回内容和预期不一致\n预期返回：{$data['validate_rule']}\n实际返回：{$data['response_body']}");
		} 
		//返回类型验证
		$type = $this->getType($data['response_body']);
		if ($data['validate_type'] == self::RESPONES_VALIDATE_TYPE_2 &&  $type != $data['validate_rule']) {
			return $this->setError("返回类型和预期不一致\n预期返回：{$data['validate_rule']}\n实际返回：{$type}");
		}
		//是否达到通知起始时间
		$timeout = $data['timeout'] ?? $this->rule['min_notice_time'];
		$bool = (strtotime($data['response_headers']['date']) - strtotime($data['request_time'])) >= ($timeout / 1000);		
		if ($data['response_time'] >= $timeout && $data['response_time'] < self::NOT_VALIDATE_TIME) {
			//处理超时返回
			if (isset($data['response_headers']['date']) && !$bool) {
				return true;
			} 
			return $this->setError("接口响应超时");
		}
		
		return true;
	}

	/**
	 * 是否需要触发通知
	 *
	 * @return boolean
	 * @author ybc
	 */
	protected function isNotice(){
		$level = $this->data['level'] ?? self::DEFAULT_NOTICE_LEVEL;
		$rule = $this->rule['notice_levels'][$level];
		$redis = Tool::getRedis();
		//通知间隔验证
		if ($redis->exists($this->redisKey['interval_key'])) {
			return false;
		}
		//通知次数验证
		$num = (int)$redis->get($this->redisKey['limit_key']);
		if ($rule['limit_num'] > 0 && $num >= $rule['limit_num']) {
			return false;
		}

		$ip 	 = HOST_NAME;
		$redis->hIncrby($this->redisKey['judge_key'], $ip, 1);
		$redis->expire($this->redisKey['judge_key'], 120);
		if (!$this->allServerConfirm()) {
			return false;
		}

		//所有监控服务器判断异常时才通知
		$exceptionNum = $rule['exception_num'];
		$expireTime = $rule['exception_time'];
		//Ip时
		if (empty($this->data['host_detail']['is_domain'])) {
			$exceptionNum = $rule['ip_exception_num'];
			$expireTime = $rule['ip_exception_time'];
		}

		//达到一定次数错误才会上报
		$incr = $this->redis->incr($this->redisKey['exception_key']);
		$incr == 1 && $redis->expire($this->redisKey['exception_key'], $expireTime);
		if ($incr < $exceptionNum) {	
			return false;				
		}
		$this->redis->del($this->redisKey['exception_key']);

		//记录
		$level = $this->data['level'] ?? self::DEFAULT_NOTICE_LEVEL;
		$rule  = $this->rule['notice_levels'][$level];
		if ($rule['interval_time'] > 0) {
			$redis->setex($this->redisKey['interval_key'], $rule['interval_time'], time());
		}
		if ($rule['limit_num'] > 0) {
			$incr = $redis->incrby($this->redisKey['limit_key'], 1);
			$incr == 1 && $redis->expire($this->redisKey['limit_key'], $rule['limit_time']);
		}	
		
		return true;
	}

	protected function getType($content){
		if ( is_array(json_decode($content, true)) ) {
			return 'json';
		}
		if (is_string($content)) {
			return 'string';
		}

		return 'other';
	}

	/**
	 * 所有服务器检测异常的次数
	 *
	 * @return integer
	 * @author ybc
	 */
	protected function getTickServerNum():int{
		$sum = 0;
		$all = $this->redis->hGetAll($this->redisKey['judge_key']);
		if (empty($all)) {
			return $sum;
		}
		foreach ($all as $ip => $num) {
			$sum += $num;
		}
		return $sum;
	}

	/**
	 * 是否确认
	 *
	 * @return void
	 * @author ybc
	 */
	protected function allServerConfirm():bool{
		$judgeNum = C('judge_num');
		$num = $this->getTickServerNum();
		$len = (int)$this->redis->hLen($this->redisKey['judge_key']);
		if ($len == $judgeNum && ($num / $judgeNum) == 1) {
			return true;
		}
		return false;
	}

	/**
	 * 记录日志
	 *
	 * @param integer $status
	 * @param string $logName
	 * @return void
	 * @author ybc
	 */
	protected function addLog(int $status, $logName = 'requestError'){
		$isError = $status > 0;
		$responseDataLength = mb_strlen($this->data['response_body']);
		$responseHeader = (!empty($this->data['response_headers']) && $isError) ? (json_encode($this->data['response_headers']) ?: '') : '';
		
		$points = [
			new \InfluxDB\Point(
				'request_log_'.(int)$this->data['id'], // name of the measurement
				null, // the measurement value
				[
					'request_host'    => (string)$this->data['host'],
					'request_path'    => (string)$this->data['position_path'],
					'response_status' => (string)$this->data['response_status'],
					'network_code'    => $this->data['error_code'],
					'keep_server_ip'  => (string)HOST_NAME,
					'error_status'    => $status,
				], // optional tags
				[
					'request_number'  	   => (string)$this->data['number'],
					'response_time'   	   => (float)$this->data['response_time'],	
					'type'                 => $this->data['project']['type'],
					'request_data'         => $this->data['data'],
					'response_header'      => $responseHeader,
					'response_data'        => $isError ? mb_substr($this->data['response_body'], 0, min(1024 * 1024 * 15, $responseDataLength)) : '',
					'response_data_length' => $responseDataLength,
					'error_msg' 		   => $isError ? $this->getError() : '',
					'status'          	   => $status,
					'target_ip'			   => $this->data['host_detail']['host_ip'],
					'dns_parsing_time'	   => (float)$this->data['host_detail']['dns_parsing_time'],
				], // optional additional fields
				strtotime($this->data['request_time']) // Time precision has to be set to seconds!
			),
		];
		try{
			$result = Influxdb::writePoints($points, \InfluxDB\Database::PRECISION_SECONDS);
			if (!$result) {
				uecho($this->data, 'addLogFail');
				return false;
			}
		} catch(Exception $e){
			uecho($e->getMessage(), 'influxdbException');
		}

		return true;
	}
}