<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 15:56:09
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-18 16:44:27
 * @Description: 全局错误码
 */

Class ErrorCode{

    CONST ERROR_DNS_PARSE_FAIL = -4;

    CONST ERROR_MAP = [
        self::ERROR_DNS_PARSE_FAIL => 'DNS解析超时',
    ];
}