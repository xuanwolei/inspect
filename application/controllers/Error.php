<?php
/**
 * @name ErrorController
 * @desc 错误控制器, 在发生未捕获的异常时刻被调用
 * @see http://www.php.net/manual/en/yaf-dispatcher.catchexception.php
 * @author {&$AUTHOR&}
 */
class ErrorController extends Yaf\Controller_Abstract {

	//从2.1开始, errorAction支持直接通过参数获取异常
	public function errorAction($exception) {
		if (!C('application.dispatcher.throwException')) {
			header('HTTP/1.1 404 Not Found');
			die;
		}
		$this->getView()->assign("exception", $exception);
		//1. assign to view engine
		
		//5. render by Yaf 
	}
}
