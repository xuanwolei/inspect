<?php
/*
 * @Author: ybc
 * @Date: 2020-09-14 15:48:42
 * @LastEditors: ybc
 * @LastEditTime: 2021-09-26 11:58:58
 * @Description: event
 */
Class Event{

    public static function onWorkerStart($serv, $worker_id){
        //绑定定时器
        if ($worker_id == 0) {
            //加入锁，保证只有一个进程执行,worker reload后也不会执行
            if (Service::$initLock->trylock()){
                self::initStart($serv);
            }
            //执行定时器 
            Tick::run($serv);
        }
    }

    /**
     *  服务器启动前的事件通知
     */
    public static function initStart($serv){
    }

    public static function onWorkerStop($server, $worker_id){
    }

    /**
     *  仅在开启reload_async特性后有效。异步重启特性，会先创建新的Worker进程处理新请求，旧的Worker进程自行退出
     */
    public static function onWorkerExit($server, $worker_id){
        secho("onWorkerExit:{$worker_id}");
    }

    /**
     *  当worker/task_worker进程发生异常后会在Manager进程内回调此函数
     *  此函数主要用于报警和监控
     *  @param $worker_id 是异常进程的编号
     *  @param $worker_pid 是异常进程的ID
     *  @param $exit_code 退出的状态码，范围是 1 ～255
     *  @param $signal 进程退出的信号
     */
    public static function onWorkerError($server, int $worker_id, int $worker_pid, int $exit_code, int $signal){
        
    }

    /**
     *  Task进程
     */
    public static function onTask($server, $task_id, $from_id, $data) {        
        if (is_object($data) && $data instanceof Job) {
            $data->runJobs($task_id, $from_id);
            unset($data);
        }
        //通知worker进程
        $server->finish("finish:{$task_id}");
    }

    public static function onPipeMessage($server, $src_worker_id, $data){
        
    }

    public static function onFinish($serv, $task_id, $data){
        
    }

    /**
     *  tcp连接
     */
    public static function onTcpConnect($serv, $fd, $from_id){
        
    }

    /**
     *  Websocket连接
     */
    public function onOpen(swoole_websocket_server $server, $request) {      
          
    }	
}