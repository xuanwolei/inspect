<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 19:32:44
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-24 21:12:44
 * @Description: 请求相关逻辑
 */

Trait ClientLogicTrait{

    public function run(){
        $this->request($this->project, $this->succCallback(), $this->exceptionCallback());
    }

    public function request(array $project, $succCallback, $exceptionCallback){        
        //每批的编号
        $number = $this->parseNumber();
        //遍历hosts
        foreach ($project['hosts'] as $key => $host) {
            go(function () use($number, $host, $project, $succCallback, $exceptionCallback) {
                try {                        
                    $rule           = $this->initRule($project, $host);
                    $rule['number'] = $number;
                    $goTime         = microtime(true);
                    $client         = $this->getClient($host, []); 
                    //遍历hosts下所有path
                    foreach ($project['paths'] as $key => $path) {
                        $startTime            = microtime(true);
                        $rule                 = $path + $rule;
                        $rule['request_time'] = date('Y-m-d H:i:s');
                        $rule['path']         = $host.$path['path'];
                        $rule['position_path'] = $path['path'];
                        $client->request((string)$path['data']);  
                        $succCallback($client, $startTime, $rule);                        
                    }  
                    $client->close();
                //dns异常
                } catch (DnsException $e) {  
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

    public function succCallback(){
        return function(Client $clinet, $startTime, array $rule){
            $requestTime = bcsub(microtime(true), $startTime, 2) * 1000;
            $rule['host_detail']     = $clinet->hostDetail;
            $rule['error_code']      = $clinet->errCode ?? 0;
            $rule['error_msg']       = $clinet->errMsg ?? '';
            $rule['response_time']   = $requestTime;
            $rule['response_status'] = $clinet->statusCode ?? 0;        
            $rule['response_headers']= $clinet->headers ?? '';
            $rule['response_body']   = $clinet->body ?? '';
            Job::dispatch(new NoticeJob($rule));
            return true;
        };
    }

    public function exceptionCallback(){
        return function(Exception $e, array $rule){
            $rule['error_code']  = $rule['response_status'] = $e->getCode();
            Job::dispatch(new NoticeJob($rule));
        };
    }

    public function valid(array $data, array $rule){
        return true;
    }
}