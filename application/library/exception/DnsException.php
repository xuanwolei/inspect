<?php
/*
 * @Author: ybc
 * @Date: 2020-09-18 15:53:17
 * @LastEditors: ybc
 * @LastEditTime: 2020-09-18 16:08:13
 * @Description: dns异常
 */

class DnsException extends Exception{
    
    public function __construct($message = "", $code = ErrorCode::ERROR_DNS_PARSE_FAIL, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function  __toString() {
        return $this->message;
    }
}