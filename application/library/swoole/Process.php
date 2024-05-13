<?php
/**
 * process 
 */
Class Process{
	protected $process = [];

	/**
	 *	获取实例
	 */
	public function getInstance(swoole_server $server){
		if (!static::$instance) {
			static::$instance = new static($server);
		}
		return static::$instance;
	}

	/**
	 * 获取单个进程
	 * @param  string $name 进程名称
	 * @return [type]       [description]
	 */
	public function getProcess(string $name){
		if (isset($this->process[$name])) {
			return $this->process[$name];
		}

		return false;
	}

	/**
	 * 获取所有进程
	 * @return array
	 */
	public function getAllProcess(){
		return $this->process;
	}

	public function pushProcess(string $name,swoole_process $process){
		$this->process[$name] = $process;
		return true;
	}

	public function exitProcess(string $name){
		$process = $this->process[$name];
		if (empty($process)) {
			return false;
		}
		$process->exit();
		unset($this->process[$name]);
	}
}