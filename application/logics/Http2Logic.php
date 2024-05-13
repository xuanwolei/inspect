<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 19:32:44
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-23 15:04:58
 * @Description: file content
 */

Class Http2Logic extends HttpLogic{

    public function getClient($host, $config){
        return new CoroutineHttpClient($host, $config); 
    }
}