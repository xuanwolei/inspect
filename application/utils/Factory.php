<?php
/*
 * @Author: ybc
 * @Date: 2020-09-23 11:24:41
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-24 15:29:58
 * @Description: file content
 */

Class Factory{
    
    /**
     * Undocumented function
     *
     * @param string $type
     * @return object
     * @author ybc
     */
    public static function getRequestLogic(string $type){
        switch(strtolower($type)){
            case "http":
                $logic = new HttpLogic();
            break;
            case "http2":
                $logic = new Http2Logic();
            break;            
            case "websocket":
                $logic = new WebSocketLogic();
            break;            
            case "tcp":
                $logic = new TcpLogic();
            break;
            case "udp":
                $logic = new UdpLogic();
            break;
        }
        return $logic;
    }
    
}