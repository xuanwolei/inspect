<?php
/**
 *	写入日志到文件
 *	@author ybc 2016-8-12
 */
Class FileLog extends AbstractLog{	
	protected $file = null;

	public function __construct(array $config){
		parent::__construct($config);		
	}

	/**
	 *	写日志
	 */
	public function write($parameter){
		$this->_parsePath();
		$str = $this->_connectStr($parameter);
		return file_put_contents($this->file, $str, FILE_APPEND);
	}

	/**
	 *	同时写入多条
	 */
	public function writeAll(array $parameter){
		$this->_parsePath();
		$str = '';
		foreach ($parameter as $v) {
			$str .= $this->_connectStr($v);
		}
		return file_put_contents($this->file, $str, FILE_APPEND);
	}

	private function _connectStr($parameter){
		if (is_array($parameter)) {
			$str = '';
			foreach ($parameter as $k => $v) {
				$str .= "{$k}={$v}||";
			}
			$str = trim($str, '||') . PHP_EOL;
		} else {
			$str = $parameter;
		}
		
		//记录日期、ip、运行时间
		$str = 'date='.date('Y-m-d H:i:s').'||'.$str.PHP_EOL;
		return $str;
	}

	private function _parsePath(){
		//检查目录
		if (!file_exists($this->config['file_path'])) {
			mkdir($this->config['file_path'], '0755', true);
		}
		$this->file = "{$this->config['file_path']}{$this->config['name']}_" . date($this->config['name_type'], $_SERVER['REQUEST_TIME']).'.log';

		return $this->file;
	}
}