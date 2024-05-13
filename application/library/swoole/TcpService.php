<?php
/**
 *	tcp service
 *	@author ybc
 *	@date 2017-1-12
 */

Class TcpService extends Service{		

	public function __construct($host = "0.0.0.0", $port = 9501){
		$server = new swoole_server($host, $port, SWOOLE_PROCESS);
		$server->set(array(
            'worker_num'         => C('worker_num'),
            'daemonize'          =>  C('daemonize'),
            'max_request'        => C('max_request'),
            'dispatch_mode'      => C('dispatch_mode'),  //数据包分发策略：固定模式，同一个连接每次分配到同一个worker进程
            'task_worker_num'    => C('task_worker_num'),  //task进程的数量
            'task_ipc_mode'      => C('task_ipc_mode'),  //使用消息队列通信
            'log_file'           => C('socket_log_path'),//日志
            'task_max_request'   => C('task_max_request'),
            'buffer_output_size' => 32 * 1024 *1024, //发送输出缓存区内存尺寸 字节
        ));
        
        $this->init($server);
        //绑定事件
        $server->on('Start', array(&$this, 'onStart')); 
        $server->on('WorkerStart', array(&$this, 'onWorkerStart'));        
        $server->on('WorkerStop', array(&$this, 'onWorkerStop'));
        $server->on('WorkerExit', array(&$this, 'onWorkerExit'));
        $server->on('WorkerError', array(&$this, 'onWorkerError'));
        $server->on('PipeMessage', array(&$this, 'onPipeMessage'));
        $server->on('Task', array(&$this, 'onTask'));
        $server->on('Finish', array(&$this, 'onFinish')); 
		$server->on('Connect', array(&$this,'onTcpConnect'));
		$server->on('Receive', array(&$this,'onTcpReceive'));
		$server->on('Close', array(&$this,'onTcpClose'));

		$server->start();
	}
}