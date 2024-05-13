<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 19:32:44
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-25 18:15:23
 * @Description: file content
 */

Class WebSocketLogic extends RequestInterfaceLogic{
    use ClientLogicTrait;
    
    CONST REQUEST_SUCC_CODE = 101;

    public function getClient($host, $config){
        return new CoroutineWebSocketClient($host, $config); 
    }

    /**
     * 异常验证
     *
     * @param array $data
     * @param array $rule
     * @return void
     * @author ybc
     */
    public function valid(array $data, array $rule){
        if (!isset($data['true_http_code'])) {
            $data['true_http_code'] = self::REQUEST_SUCC_CODE;
        }
        //接口返回状态码验证
		if ($data['response_status'] != 0 && $data['response_status'] != $data['true_http_code']) { 
			return $this->setError("状态码异常：{$data['response_status']}");
        }
        
        return true;
    }
}