<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 19:32:44
 * @LastEditors: ybc
 * @LastEditTime: 2020-11-05 11:23:33
 * @Description: file content
 */

Class HttpLogic extends RequestInterfaceLogic{
    use ClientLogicTrait;

    CONST REQUEST_SUCC_CODE = 200;

    public function request(array $project, $succCallback, $exceptionCallback){        
        //每批的编号
        $number = $this->parseNumber();
        //遍历hosts
        foreach ($project['hosts'] as $key => $host) {
            $config = [
                'headers' => [
                    'Referer' => "inspect-{$number}"
                ]
            ];
            if ( !empty($project['http_host']) ) {
                $config['headers']['Host'] = $project['http_host'];
            }
            go(function () use($number, $host, $config, $project, $succCallback, $exceptionCallback) {
                try {                        
                    $rule           = $this->initRule($project, $host);
                    $rule['number'] = $number;
                    $goTime         = microtime(true);
                    $http           = $this->getClient($host, $config); 
                    //遍历hosts下所有path
                    foreach ($project['paths'] as $key => $path) {
                        $startTime            = microtime(true);
                        $rule                 = $path + $rule;
                        $rule['request_time'] = date('Y-m-d H:i:s');
                        $rule['path']         = $host.$path['path'];
                        $rule['position_path'] = $path['path'];
                        $http->request($path['path'], $path['method'], (string)$path['data']);  
                        $succCallback($http, $startTime, $rule);                        
                    }  
                    $http->close();
                //dns异常
                } catch (DnsException $e) {                    
                    $rule['request_time'] = date('Y-m-d H:i:s');
                    $rule['host_detail'] = [
                        'is_domain' => true,
                        'host_ip' => '',
                        'dns_parsing_time' => bcsub(microtime(true), $goTime, 2) * 1000,
                    ];
                    $exceptionCallback($e, $rule);
                }
            });
        }
    }

    public function getClient($host, $config){
        return new CoroutineHttpClient($host, $config); 
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
        $data['true_http_code'] = self::REQUEST_SUCC_CODE;
        //接口返回状态码验证
		if ($data['response_status'] != 0 && $data['response_status'] != $data['true_http_code']) { 
			return $this->setError("状态码异常：{$data['response_status']}");
        }
        
        return true;
    }
}