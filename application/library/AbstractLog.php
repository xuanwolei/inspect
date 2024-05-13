<?php
/**
 *	日志基类
 *	@author ybc 2016-8-12
 */
abstract Class AbstractLog{
	public abstract function write($parameter);
	public abstract function writeAll(array $parameter);	

	public function __construct(array $config){
		if (empty($config)) {
			return false;
		}
		$this->config = array(
			'name' => 'log',
			//日志存储命名方式
			'name_type' => C('name_type'),
			//文件存储路径
			'file_path' => C('default_log_path'),
		);
		
		foreach ($config as $k => $v) {
			$this->set($k, $v);
		}

		return true;
	}
	
	/**
	 *	修改配置
	 */
	public function set($key,$value){
		if (isset($this->config[$key])) {
			return $this->config[$key] = $value;
		}
		return false;
	}
}