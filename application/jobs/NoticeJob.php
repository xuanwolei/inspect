<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 17:18:42
 * @LastEditors: ybc
 * @LastEditTime: 2021-11-25 14:17:34
 * @Description: file content
 */
/**
 *  @author ybc
 *	通知任务
 */
Class NoticeJob extends Job{

	/**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){
        $log = [
            'number'      => $this->data['number'],
            'type'        => $this->data['project']['type'],
            'host'        => $this->data['path'],
            'request_time' => $this->data['request_time'],
            'time'        => $this->data['response_time'],
            'header_date' => $this->data['response_headers']['date'] ?? '',
            'length'      => mb_strlen($this->data['response_body']),
            'status'      => $this->data['response_status']
        ];        
        uecho($log, 'request');
        $requestlogic = Factory::getRequestLogic($this->data['project']['type'] ?? 'http');
        
        $this->data['project']['notice_token'] = $this->data['notice_token'];
		$notice   = new RebotNoticeLogic($this->data);
		$instance = new NoticeLogic($notice, $requestlogic, $this->data);
		$instance->run();
    }
}