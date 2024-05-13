<?php
/*
 * @Author: ybc
 * @Date: 2020-09-26 18:10:57
 * @LastEditors: ybc
 * @LastEditTime: 2021-11-25 14:22:48
 * @Description: file content
 */

/**
 * 钉钉机器人通知
 * @author  ybc 
 * @date(2018-7-16)
 */
Class RebotNoticeLogic implements NoticeInterfaceLogic{

	protected $data;

	/**
	 * [__construct description]
	 * @param array $data 通知信息
	 */
	public function __construct(array $data){
		$this->data = $data;
	}

	/**
	 * 发送通知
	 * @param  $content 内容
	 * @return mixed
	 */
	public function send(string $content){
		$instance = TalkRobot::getInstance();
		$result = $instance->token($this->data['project']['notice_token'] ?? '')->sendTextMsg($content, $this->data['phone']);
		if (!$result) {
			//记录错误
			uecho('apiError:'.$instance->getError().',notice:'.$content, 'rebotNoticeFail');
			return false;
		}

		return $result;
	}
}