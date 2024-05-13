<?php
/**
 *	日志驱动
 *	@author ybc 2016-8-12
 */
Class YunLog{
	public $object = null;
	public static $objectAll = array();
	private function __construct(){}
	
	//单例模式连接
	public static function getInstance(array $config = array()){
		
		if (!$config['log_type']) {
			$log_type = C('log_type');
		} else {
			$log_type = $config['log_type'];	
		}
		//类型判断
		$class = ucfirst($log_type).'Log';
		if (!class_exists($class)) {
			throw new Exception("class:{$class}不存在", 1);			
		}
		if (!empty(self::$objectAll[$class])) {
			foreach ($config as $key => $value) {
				self::$objectAll[$class]->set($key, $value);
			}
			return self::$objectAll[$class];
		}
		$GLOBALS['end_time'] = microtime(true);
        $GLOBALS['run_time'] = round(($GLOBALS['end_time'] - $GLOBALS['start_time']) , 2);
		self::$objectAll[$class] = new $class($config);	
		return self::$objectAll[$class];
	}

	/**
	 *	写日志
	 *	@param string $content 日志内容
	 *	@param string $name 
	 */
	public static function logWrite($content, string $name,string $file_path = '',int $name_type = 2){
		switch ($name_type) {
			case '1':
				$name_type = 'Y';
				break;
			case '3':
				$name_type = 'Y_m_d';
				break;
			default:
				$name_type = C('name_type');
				break;
		}
		$config = array(
            'log_type' => 'file',
            'name' => $name ? $name : 'default',
            'name_type' => $name_type,
            'file_path' => $file_path ? C('default_log_path').$file_path.'/' : C('default_log_path').'baby_socket/',
        );
        $obj = self::createObj($config);
       
        return $obj->write($content);
	}
}