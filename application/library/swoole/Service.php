<?php
/**
 *	service
 *	@author ybc
 *	@date 2018-7-13
 */
set_time_limit(0);
ini_set('default_socket_timeout', '-1');
Class Service{
	//server
	public static $server;
	//tcp server
	public static $tcpServer;
	//lock
	public static $initLock;
	//atomic 原子计数器

	/**
	 * 初始化
	 * @param  swoole_server $server 
	 * @return object swoole_lock
	 */
	protected function init(swoole_server $server){
		//托管子进程
        $processList = SysProcess::getInstance($server)->getAllProcess();
        foreach ($processList as $key => $process) {
        	$server->addProcess($process);
        }
        //初始化锁
        self::$initLock = new swoole_lock(SWOOLE_RWLOCK);
        //修改进程名
        swoole_set_process_name(C('service_name').'-manager');
        swoole_async_set(array(
		    'disable_dns_cache' => true, //关闭dns缓存
		    'dns_lookup_random' => true, //dns随机
		));
        return $initLock;
	}

	/**
	 * Server启动在主进程的主线程回调此函数
	 * 在此事件之前Swoole Server已进行了如下操作
 	 * 已创建了manager进程
     * 已创建了worker子进程
     * 已监听所有TCP/UDP/UnixSocket端口，但未开始Accept连接和请求
     * 已监听了定时器
	 * @param  swoole_server $server
	 */
	public function onStart(swoole_server $server){
		//修改主进程名
        swoole_set_process_name(C('service_name').'-master');
	}

	public function onWorkerStart(swoole_server $server, int $worker_id) {
		//清除apc缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
		C(null,null,true);
		$type = $server->taskworker ? 'Task' : 'Worker';
		swoole_set_process_name(C('service_name')."-{$type}");
        secho("onWorkerStart:{$worker_id},type:{$type}");
        self::$server = &$server;
        Event::onWorkerStart($server, $worker_id);
    }

    /**
	 *	在worker进程终止时发生,在此函数中可以回收worker进程申请的各类资源。
     */
    public function onWorkerStop(swoole_server $server, int $worker_id){
    	Event::onWorkerStop($server, $worker_id);
    }
    
    /**
	 *	仅在开启reload_async特性后有效。异步重启特性，会先创建新的Worker进程处理新请求，旧的Worker进程自行退出
     */
    public function onWorkerExit(swoole_server $server, int $worker_id){
    	Event::onWorkerExit($server, $worker_id);
    }

    /**
	 *	当worker/task_worker进程发生异常后会在Manager进程内回调此函数
	 *	此函数主要用于报警和监控
	 *  @param $worker_id 是异常进程的编号
	 *	@param $worker_pid 是异常进程的ID
	 *	@param $exit_code 退出的状态码，范围是 1 ～255
	 *	@param $signal 进程退出的信号
     */
    public function onWorkerError(swoole_server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal){
    	secho("onWorkerError,worker_id:{$worker_id},worker_pid:{$worker_pid},exit_code:{$exit_code},signal:{$signal}",'sysError');
    	Event::onWorkerError($server, $worker_id, $worker_pid, $exit_code, $signal);
    }

    /**
	 *	当工作进程收到由 sendMessage 发送的管道消息时会触发onPipeMessage事件。worker/task进程都可能会触发
     */
    public function onPipeMessage(swoole_server $server, $src_worker_id, $message){
    	Event::onPipeMessage($server, $src_worker_id, $message);
    }

    /**
	 *	Task进程
     */
    public function onTask(swoole_server $server, $task_id, $from_id, $data) {
    	Event::onTask($server, $task_id, $from_id, $data);
    }

    public function onFinish(swoole_server $server, $task_id, $data) {
    	Event::onFinish($server, $task_id, $data);
    }

	public function onTcpConnect(swoole_server $server, $fd, $from_id){
        secho("onTcpConnect,fd:{$fd},from_id:{$from_id}");
		Event::onTcpConnect($server, $fd, $from_id);
	}

	public function onTcpClose(swoole_server $server, $fd, $reactor_id){
		secho("TcpClose:fd:{$fd},reactor_id:{$reactor_id}");
		$array['fd']         = $fd;
		$array['reactor_id'] = $reactor_id;
		$array['worker_id']  = $server->worker_id;
		Message::onTcpClose($server, $array);
	}

	public function onTcpReceive(swoole_server $server, $fd, $from_id, $data){
		secho("onTcpReceive:fd:{$fd},from_id:{$from_id},data:{$data}");
		$array['fd']        = $fd;
		$array['from_id']   = $from_id;
		$array['data']      = $data;
		$array['worker_id'] = $server->worker_id;
		Message::onTcpReceiver($server, $array);
	}

	/**
	 *	开启会话时
	 */
	public function onOpen(swoole_websocket_server $server, $request) {
		secho("onOpen:with fd{$request->fd}");
		Event::onOpen($server, $request);
	}

	/**
	 * 
	 *	收到消息时
	 */
	public function onMessage(swoole_websocket_server $server, $frame) {
		secho("onMessage,fd:{$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}");
		$array = json_decode($frame->data, true);
        if (!is_array($array)) {
        	return false;
        }
		$array['fd']        = $frame->fd;
		$array['worker_id'] = $server->worker_id;
        Message::onReceiver($server, $array);
	}

	/**
	 *	断开连接时
	 */
	public function onClose(swoole_server $server, $fd, $fd_list = []){
		secho("client {$fd} closed");
		$data['fd']        = $fd;
		$data['worker_id'] = $server->worker_id;
		Message::onSocketClose($server, $data);
	}

	/**
	 *	 发送消息到tcp客户端
	 */
	public static function sendToClient($fd, $data){
		if (!self::$server->exist($fd)) {
			return false;
		}
		$result = self::$server->send($fd, $data);
		secho("sendMsgToTcp：fd:{$fd},result:".intval($result));
		return $result;
	}

	/**
	 *	 发送消息到socket客户端
	 */
	public static function sendToSocketClient($fd, array $data){
		if (!self::$server->exist($fd)) {
			return false;
		}
		$result =  self::$server->push($fd, json_encode($data));
		return $result;
	}

	/**
	 * [sendErrorToSocket 发送错误消息到socket客户端]
	 * @param  [type] $fd   [description]
	 * @param  [type] $msg  [description]
	 * @param  string $code [description]
	 * @return [type]       [description]
	 */
	public static function sendErrorToSocket($fd, $msg, $code = '-1'){
		$data = array(
			'event' => 'error_msg',
			'code'  => $code,
			'msg'   => $msg
		);
		return self::sendToSocketClient($fd,$data);
	}

	public static function addTask($data){
		return self::$server->task($data);
	}

	public static function getServer(){
		return self::$server;
	}

	/**
	 *	关闭连接
	 *	$reset设置为true会强制关闭连接，丢弃发送队列中的数据
	 */
	public static function close($fd, $reset = false){
		return self::$server->close($fd, $reset);
	}
}