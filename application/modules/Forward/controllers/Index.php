<?php
/**
 * @name IndexController
 * @author {&$AUTHOR&}
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class IndexController extends Yaf_Controller_Abstract {

	/** 
     * 默认动作
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     * 对于如下的例子, 当访问http://yourhost/{&$APP_NAME&}/index/index/index/name/{&$AUTHOR&} 的时候, 你就会发现不同
     */
	public function indexAction() {
		$subsystem  = $this->getRequest()->getParam('subsystem');
		$module     = $this->getRequest()->getParam('module');
		$controller = $this->getRequest()->getParam('controller');
		$method     = $this->getRequest()->getParam('method');
		$arr = array(
			'subsystem' => $subsystem,
			'module'	=> $module,
			'controller'=> $controller,
			'method'	=> $method
		);
		
		echo json_encode($arr);
		return FALSE;
	}

	
}
