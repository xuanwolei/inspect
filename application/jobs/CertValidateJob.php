<?php
/*
 * @Author: ybc
 * @Date: 2021-11-25 13:44:33
 * @LastEditors: ybc
 * @LastEditTime: 2021-12-01 11:21:33
 * @Description: 证书检测
 */

Class CertValidateJob extends Job{

	/**
     * Execute the job.
     *
     * @return void
     */
    public function handle(){

        // [subject] => Array
        // (
        //     [C] => CN
        //     [ST] => 浙江
        //     [L] => 杭州
        //     [OU] => IT
        //     [O] => 杭州xxx有限公司
        //     [CN] => *.xxx.com
        // )
        $cert = Tool::getCertValidity($this->data['http_host']);
        if (empty($cert)) {
            return $cert;
        }
        $overTime = $cert['validTo_time_t'] - 86400 * 14;
        $nowTime = time();
        if ($nowTime < $overTime) {
            return false;
        }
        $lockKey = "certValidateJob:{$this->data['http_host']}";
        if (!Tool::lock($lockKey, 86400 * 3)){
            return false;
        }
        uecho($cert['subject']);
        $subject = $cert['subject'];
        $startDate = date("Y-m-d",$cert['validFrom_time_t']);
        $overData = date("Y-m-d",$cert['validTo_time_t']);
        $ip 	 = HOST_NAME;
        $region = "{$subject['ST']}-{$subject['L']}";
        $content = "项目：{$this->data['name']}\n当前使用证书即将过期，请注意更换！\n业务域名：{$this->data['http_host']}\n证书域名：{$subject['CN']}\n组织：{$subject['O']}\n地区：{$region}\n起始时间：{$startDate}\n过期时间：{$overData}\n监控机器：{$ip}\n";
        $notice   = new RebotNoticeLogic($this->data);
        $result  = $notice->send($content);
    }
}