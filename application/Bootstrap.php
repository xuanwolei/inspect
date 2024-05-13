<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 15:59:12
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-18 16:10:38
 * @Description: file content
 */

/**
 * @name Bootstrap
 * @author {&$AUTHOR&}
 * @desc 所有在Bootstrap类中, 以_init开头的方法, 都会被Yaf调用,
 * @see http://www.php.net/manual/en/class.yaf-bootstrap-abstract.php
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends Yaf\Bootstrap_Abstract{

    public function _initConfig() {
		//把配置保存起来
		$arrConfig = Yaf\Application::app()->getConfig();
		Yaf\Registry::set('config', $arrConfig);
	}

	public function _initPlugin(Yaf\Dispatcher $dispatcher) {
		//注册一个插件
		$objSamplePlugin = new SamplePlugin();
		$dispatcher->registerPlugin($objSamplePlugin);
	}

	public function _initRoute(Yaf\Dispatcher $dispatcher) {
		$router = Yaf\Dispatcher::getInstance()->getRouter();
		
        /**
         * 添加配置中的路由
         */
        // $router->addConfig(Yaf\Registry::get("config")->routes);
	}
	
	public function _initView(Yaf\Dispatcher $dispatcher){
		//在这里注册自己的view控制器，例如smarty,firekylin		
	}

	public function _initDefine(){
		date_default_timezone_set("PRC");	//设置时区
		//内网地址
		$ip = gethostbyname(gethostname());
		define('HOST_NAME', $ip);
	}

	/**
	 *	加载函数库
	 */
	public function _initFunction(Yaf\Dispatcher $dispatcher){
		Yaf\Loader::import(APP_PATH . 'library/predis1.1/autoload.php');
		Yaf\Loader::import(APP_PATH . 'Function.php');
		/*设置自动包含的目录*/
		$include_path  = get_include_path(); //原来的目录
		$include_path .= PATH_SEPARATOR.APP_PATH.'library/swoole';		
		$include_path .= PATH_SEPARATOR.APP_PATH.'library/exception';
		$include_path .= PATH_SEPARATOR.APP_PATH.'utils';
		$include_path .= PATH_SEPARATOR.APP_PATH.'jobs';
		$include_path .= PATH_SEPARATOR.APP_PATH.'logics';
		$include_path .= PATH_SEPARATOR.APP_PATH.'services';
		set_include_path($include_path);	
		spl_autoload_register(array($this, 'initAutoload'), true, false);
		$GLOBALS['start_time'] = microtime(true);
	}

	public function initAutoload($className){
		include_once($className.'.php');
	}
	
	public function _initError(){
		error_reporting(E_ALL&~E_NOTICE&~E_DEPRECATED);	//屏蔽注意提示、过期方法提示
		if (C('application.dispatcher.throwException')) {
			ini_set('display_errors', 'On'); 			//开启错误输出
		} else {			
			ini_set('display_errors', 'Off'); 			//屏蔽错误输出
			ini_set('log_errors', 'On');             	//开启错误日志，将错误报告写入到日志中
		}
	}
}
