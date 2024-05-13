<?php
/*
 * @Author: ybc
 * @Date: 2020-09-11 16:51:52
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-25 14:30:38
 * @Description: file content
 */

use Yaf\Controller_Abstract;

class IndexController extends Controller_Abstract {

	//server端
	public function indexAction() {	
		$server = new TcpService();
		die;
	}
}
