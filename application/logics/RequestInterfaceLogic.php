<?php
/*
 * @Author: ybc
 * @Date: 2020-09-23 14:01:06
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-24 17:11:44
 * @Description: file content
 */
abstract Class RequestInterfaceLogic{
    use ErrorTrait;
    
    CONST DEFAULT_RULE = [
        'error_code' => 0,
        'response_time' => 0,
        'response_status' => 0,
        'response_headers' => '',
        'response_body' => '',
    ];

    protected $project;
    
    public function __construct() {
    }

    public function setProject(array $project){
        $this->project = $project;
        return $this;
    }

    public function parseNumber(){
        return date('YmdHis').Tool::randStr(15);
    }

    public function initRule(array $project, $host){
        $rule            = self::DEFAULT_RULE;
        $rule['request_time'] = date('Y-m-d H:i:s');
        $rule['project'] = $project;
        $rule['id']      = $project['id'];
        $rule['name']    = $project['name'];
        $rule['phone']   = $project['phone'];
        $rule['level']   = $project['level'];
        $rule['timeout'] = $project['timeout'];
        $rule['host']    = $host;
        $rule['path']    = $host;
        return $rule;
    }

    abstract public function run();

    abstract public function valid(array $data, array $rule);
}