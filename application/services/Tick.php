<?php
/*
 * @Author: ybc
 * @Date: 2020-09-14 13:53:08
 * @LastEditors: ybc
 * @LastEditTime: 2021-11-30 11:16:09
 * @Description: file content
 */
/**
 *  定时器
 *  @date(2018-7-13)
 *  @author ybc
 */
Class Tick{

    /**
	 *	绑定定时器
     */
    public static function run($server){      
        $server->tick(86390000, function($id){
    		self::certInspect($id);
        });
    	$server->tick(30000, function($id){
    		self::apiInspect($id);
        });
        
        //定时重启，防止内存泄漏
        $server->tick(86400000, function($id) use($server){
            $server->stop();
    	});
    }

    /**
     * 健康检查
     * @param   $id 
     * @return  
     */
    public static function apiInspect(int $id){
        $field = 'id,type,name,http_host,hosts,paths,phone,level,timeout,notice_token';
        $where = ['state' => 1];
        $list = db('config')->where($where)->field($field)->order('id ASC')->select();
        foreach ($list as $key => $project) {
            $project['hosts'] = json_decode($project['hosts'], true);
            $project['paths'] = json_decode($project['paths'], true);
           
            $logic = Factory::getRequestLogic($project['type'] ?? 'http');
            $logic->setProject($project)->run();
        }
        return true;
    }

    /**
     * 证书检测
     *
     * @param integer $id
     * @return void
     * @author ybc
     */
    public static function certInspect(int $id){
        $field = 'id,type,name,http_host,hosts,paths,phone,level,timeout,notice_token';
        $where = ['state' => 1,'hosts' => ['like','%https%'],'http_host' => ['neq','']];
        $list = db('config')->where($where)->field($field)->order('id ASC')->select();
        
        foreach ($list as $key => $project) {
            Job::dispatch(new CertValidateJob($project));            
        }
    }
}
