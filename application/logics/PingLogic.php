<?php
/*
 * @Author: ybc
 * @Date: 2020-09-24 15:18:37
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-25 16:05:21
 * @Description: file content
 */
/**
 * 接口连接测试
 * @author ybc
 */
Class PingLogic{

	/**
	 * pingClinet
	 * @param  array  $info 接口信息
	 * @param  int $fd   客户端fd
	 * @return bool
	 */
	public static function pingClient(array $project, int $fd){
		$pathCount = count($project['paths']);
		$count = count($project['hosts']) * $pathCount;
		$chan = new Swoole\Coroutine\Channel(1);
		$logic = Factory::getRequestLogic($project['type'] ?? 'http');	
		$logic->request($project, self::succCallback($chan), self::exceptionCallback($chan));
		$num = 0;
		
		while(true){
			$num++;			
			$data = $chan->pop();
			if ($data['exception'] == 1) {
				$count -= ($pathCount -1);
			}
			$rule = $data['rule'];
			$return[] = [
				'host'             => $rule['host'],
				'error_code'       => $rule['error_code'],
				'response_status'  => $rule['response_status'] ?? '',
				'response_body'    => $rule['response_body'] ?: '',
				'response_headers' => $rule['response_headers'] ?? '',
			];

			if ($num >= $count) {
				break;
			}
		}
	    
		//发送消息到客户端
		$return = Tool::jsonEncode($return);
		uecho($return, 'pingClinet');
		return Service::sendToClient($fd, $return);
	}

	public static function succCallback($chan){
        return function(Client $clinet, $startTime, array $rule) use ($chan){
            $requestTime = bcsub(microtime(true), $startTime, 2) * 1000;
            $rule['host_detail']     = $clinet->hostDetail;
            $rule['error_code']      = $clinet->errCode ?? 0;
            $rule['error_msg']       = $clinet->errMsg ?? '';
            $rule['response_time']   = $requestTime;
            $rule['response_status'] = $clinet->statusCode ?? 0;        
            $rule['response_headers']= $clinet->headers ?? '';
			$rule['response_body']   = $clinet->body ?? '';
			$chan->push(['rule' => $rule, 'exception' => 0]);
            return true;
        };
    }

    public static function exceptionCallback($chan){
        return function(Exception $e, array $rule) use ($chan){
			$rule['error_code']  = $rule['response_status'] = $e->getCode();
			$chan->push(['rule' => $rule, 'exception' => 1]);
        };
    }
}