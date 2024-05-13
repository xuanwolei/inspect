<?php
/**
 *  处理消息
 *  @author ybc
 *  @date 2018-7-30
 */
Class Message{

    /**
     *  监听socket消息
     */
    public static function onReceiver($server, $data){
        
    }

    /**
     *  socket连接关闭
     */
    public static function onSocketClose($server, $data, $list = array()){
        
    }

    /**
     *  监听tcp消息
     */
    public static function onTcpReceiver($server, $data){
        $info = json_decode($data['data'], true);
        if (!is_array($info)) {
            return false;
        }
        switch ($info['event']) {
            case 'ping':
                PingLogic::pingClient($info['data'], $data['fd']);
                break;
            
            default:
                
                break;
        }
    }

    /**
     *  tcp连接关闭
     */
    public static function onTcpClose($server, $data){
        
    }
}
