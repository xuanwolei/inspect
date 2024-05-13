<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 19:32:44
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-23 20:28:54
 * @Description: file content
 */

Class UdpLogic extends RequestInterfaceLogic{
    use ClientLogicTrait;

    public function getClient($host, $config){
        $config['connect_type'] = SWOOLE_SOCK_UDP;
        return new CoroutineClient($host, $config); 
    }
}