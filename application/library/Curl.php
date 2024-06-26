<?php

/**
 * CURL工具
 */
Class Curl {
    public static  $debug = false;
    private static $_ch;
    private static $_header;
    private static $_body;
    private static $_cookie  = array();
    private static $_options = array();
    private static $_url     = array();
    private static $_referer = array();
    private static $_optArray = array();

    /**
     * 调用外部url
     *
     * @param
     *            $queryUrl
     * @param $param 参数            
     * @param string $method            
     * @return bool|mixed
     */
    public static function callWebServer($queryUrl, $param = '', $method = 'get', $is_json = true, $is_urlcode = false) {
        if (empty($queryUrl)) {
            return false;
        }
        $method = strtolower($method);
        $ret    = '';
        $param  = empty($param) ? array() : $param;
        self::_init();
        if ($method == 'get') {
            $ret = self::_httpGet($queryUrl, $param);
        } elseif ($method == 'post') {
            $ret = self::_httpPost($queryUrl, $param, $is_urlcode);
        }
        if (!empty($ret)) {
            if ($is_json) {
                return json_decode($ret, true);
            } else {
                return $ret;
            }
        }
        return true;
    }

    public static function setConfig($_optArray = array()){
        self::$_optArray = $_optArray;
    }

    private static function setOption($optArray = array()) {
        
        foreach ($optArray as $key => $value) {          
            curl_setopt(self::$_ch, $key, $value);
        }
    }

    private static function _init() {

        self::$_ch = curl_init();
        if (!empty(self::$_optArray)) {
            self::setOption(self::$_optArray);
        }
        curl_setopt(self::$_ch, CURLOPT_HEADER, true);
        curl_setopt(self::$_ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt(self::$_ch, CURLOPT_FRESH_CONNECT, true);
    }

    private static function _close() {
        if (is_resource(self::$_ch)) {
            curl_close(self::$_ch);
        }
        self::$_optArray = [];
        return true;
    }

    private static function _httpGet($url, $query = array()) {
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= is_array($query) ? http_build_query($query) : $query;
        }

        curl_setopt(self::$_ch, CURLOPT_URL, $url);
        curl_setopt(self::$_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(self::$_ch, CURLOPT_HEADER, 0);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 1);

        $ret = self::_execute();
        self::_close();
        return $ret;
    }

    private static function _httpPost($url, $query = array(), $is_urlcode = true) {
        if (is_array($query)) {
            foreach ($query as $key => $val) {
                if ($is_urlcode) {
                    $encode_key = urlencode($key);
                } else {
                    $encode_key = $key;
                }
                if ($encode_key != $key) {
                    unset($query[$key]);
                }
                if ($is_urlcode && is_string($val)) {
                    $query[$encode_key] = urlencode($val);
                } else {
                    $query[$encode_key] = $val;
                }
            }
            
            $query = http_build_query($query);
        }
        curl_setopt(self::$_ch, CURLOPT_URL, $url);
        curl_setopt(self::$_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(self::$_ch, CURLOPT_HEADER, 0);
        curl_setopt(self::$_ch, CURLOPT_POST, true);
        curl_setopt(self::$_ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt(self::$_ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt(self::$_ch, CURLOPT_SSLVERSION, 1);

        $ret = self::_execute();
        self::_close();
        return $ret;
    }

    private static function _put($url, $query = array()) {
        curl_setopt(self::$_ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        return self::_httpPost($url, $query);
    }

    private static function _delete($url, $query = array()) {
        curl_setopt(self::$_ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

        return self::_httpPost($url, $query);
    }

    private static function _head($url, $query = array()) {
        curl_setopt(self::$_ch, CURLOPT_CUSTOMREQUEST, 'HEAD');

        return self::_httpPost($url, $query);
    }

    private static function _execute() {
        $response = curl_exec(self::$_ch);
        $errno    = curl_errno(self::$_ch);
        if ($errno > 0 && self::$debug) {
            secho("curlError code:{$errno},msg".curl_error(self::$_ch));
            return false;
        }
        return $response;
    }
}
