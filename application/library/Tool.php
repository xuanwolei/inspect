<?php
/**
 *	工具类
 *	author : ybc
 *	date : 2016-4-22
 */
use Predis\Connection\ConnectionException;
Class Tool{

	private static $redisObject;

	/**
	 * 获取redis对象
	 * @param  integer $database  
	 * @return object
	 */
	public static function getRedis($database = 1){
		while (true) {
			try {
				if (!isset(self::$redisObject)) {
					$host = C('cache_host');
					$port = C('cache_port');
					$parameters = "tcp://{$host}:{$port}";
					$options = [
						'persistent' => true,//长连接
					    'service' => 'mymaster',
						'parameters' => [					        
					        'database' => $database,
					    ],
					];
					$auth = C('redis_auth');
					if (!empty($auth)) {
						$options['parameters']['password'] = $auth;
					}
					self::$redisObject = new Predis\Client($parameters, $options);					
				}
			
				$result = self::$redisObject->ping('ping');
				if (!$result) {
					throw new ConnectionException('ping fail', 0);
				}
				return self::$redisObject;
			} catch (ConnectionException $e) {
				secho("redis ConnectionException:".$e->getMessage(), 'redisError');
				// 重连redis
				self::$redisObject = null;			
				usleep(100000);
				continue;
			}
		}
	}

	/**
	 *	过滤注入代码js、html
	 */
	public static function cleanHtml($str,$tags='<img><a><p><br><div><strong><b><section>'){
	    $search = array(
	        '@<script[^>]*?>.*?</script>@si',  // Strip out javascript
	        '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly 
	        '@<![\s\S]*?--[ \t\n\r]*>@',         // Strip multi-line comments including CDATA 
	        '@<[^>]+?\s+on[a-zA-Z]+=[\'"\s]+[^>]+?>@is'  //过滤节点中的onload等
	    ); 
	    
	    $str = preg_replace($search, '', $str);
	    if ($tags !== false) {
	    	$str = strip_tags($str,$tags);
	    }    
	    return $str;
	}

	/**
	 * 加载目录
	 * @param  string $dir 目录路径
	 * @return bool
	 */
	public static function includeDir($dir){
		if (!file_exists($dir)) {
			return false;
		}
		if (!function_exists('scandir')){
			return false;
		}
		$files = scandir($dir);
        unset($files['0']);
        unset($files['1']);
        foreach ($files as $key => $file) {
        	$path = "{$dir}/{$file}";
        	// 检测类型
            if (pathinfo($path, PATHINFO_EXTENSION) != 'php') {
                continue;
            }
        	include_once($path);
        }

        return true;
	}
	
	/**
	 *  @der 缓存函数(缓存方式配置文件CACHE_TYPE设置)
	 *  @param string $name 缓存名称
	 *  @param mixed $data 缓存数据
	 *  @param int $time [缓存时间](秒) default 1天
	 *  @return mixed
	 */
	public static function cache($name='',$data='',$time='86400'){

		//缓存方式
		switch (strtolower(C('cache_type'))) {
			case 'redis':	
				return self::redisCache($name , $data , $time);		
				break;		
			default:
				return self::fileCache($name , $data , $time);
				break;
		}	
	}

	/**
	 *  redis缓存函数(缓存方式配置文件CACHE_TYPE设置)
	 *  @param string $name 缓存名称
	 *  @param mixed $data 缓存数据
	 *  @param int $time [缓存时间](秒) default 1天
	 *  @return mixed
	 */
	public static function redisCache($name='',$data='',$time='86400') {		
		//获取redis对象
		$redis = self::getRedis();
		//写入数据时
		if ($data) {
			if ($time == 0) {
				return $redis->set($name, $data);
			} elseif($time == -1) {
				return $redis->del($name);
			} else {
				return $redis->setex($name, $time, $data);
			}
			
		}
		return $redis->get($name);
	}

	/**
	 *  file缓存函数(缓存方式配置文件CACHE_TYPE设置)
	 *  @param string $name 缓存名称
	 *  @param mixed $data 缓存数据
	 *  @param int $time [缓存时间](秒) default 1天
	 *  @return  mixed
	 */
	public static function fileCache($name='',$data='',$time='86400') {
		//缓存目录
		$cachePath = APP_PATH.'Runtime/S_cache';

		/*写入数据时*/
		if (!empty($data)) {
			//创建目录
			if (!file_exists($cachePath)) {
				mkdir($cachePath);
			}
			/*缓存时间*/
			$time == 0 && $time = 999999999;
			$cacheTime = sprintf('%010d',time()+$time);		
			return file_put_contents("{$cachePath}/{$name}.txt",$cacheTime.json_encode($data));  
		}
		if(!file_exists($path)) {
			return false;
		}
		/*查看是否到了缓存时间*/
		$path = "{$cachePath}/{$name}";
		$file = file_get_contents($path);
		$time = intval(substr($file,0,10));

		//缓存时间已到时
		if (time() > $time) {
			unlink($path);
			return false;
	 	}

	    return json_decode(substr($file, 10));
	}

	public static function getPhpRedis($index = 0){
		
		try {
			static $redisObj;
			if (!$redisObj) {
				$redisObj = new redis();
				$redisObj->pconnect(C('cache_host'), C('cache_port'));		
				$redisObj->auth(C('redis_auth'));
				$redisObj->select($index);
			}
			return $redisObj;
		} catch (RedisException $e) {
			unset($redisObj);
			usleep(100000);
			self::getRedis();
		}
	}

    /**
	 *	生成签名sign
	 *	@return string 签名
     */
    public static  function getSign($arr , $apiSecret = ''){   
    	if (empty($apiSecret)) {
    		$apiSecret = C('cryptSecret');
    	}
    	ksort($arr);
    	$sign = self::my_http_build_query($arr)."&key={$apiSecret}";

    	$sign = strtoupper(md5($sign));       	
    	return $sign;
    }

    /**
	 * @der 生成随机字符串
	 * @param int $length [长度]
	 * @return string
	 */
	public static function randStr($length=20){
	    $str = '0987654321qwertyuioplkjhgfdsazxcvbnmZXCVBNMLKJHGFDSAQWERTYUIOP';
	    $str_length = strlen($str)-1;
	    $string = '';
	    for ($i = 0; $i<$length; $i++) {
	      $string .= $str{mt_rand(0,$str_length)};
	    } 
	    
	    return $string;
	}

	/**
	 *	组合 url
	 */
	public static function my_http_build_query(array $url){
		$str = '';		
		foreach ($url as $key => $v) {
			if ($v && $key != 'sign') {
				$str .= "{$key}=$v&";
			}
		}
		$str = rtrim($str,'&');
		
		return $str;
	}

	/**
	 * @der 获取IP地址
	 */
	public static function get_client_ip(){
		return !empty($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP']:
			   !empty($_SERVER['HTTP_XFORWARDED_FOR']) ? $_SERVER['HTTP_XFORWARDED_FOR']:
			   !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']:'';
	}
    
    /**
     * 数组转XML
     */
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
        	 if (is_numeric($val))
        	 {
        	 	$xml.="<".$key.">".$val."</".$key.">"; 
        	 }
        	 else{
        	 	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
        	 } 
        }
        $xml.="</xml>";
        return $xml; 
    }  

    /**
     * 格式化json
     * @param   $data 数据
     * @return json
     */
    public static function jsonEncode($data){
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

	/**
	 * 获取证书有效期
	 */
	public static function getCertValidity($domain){		
		$context = stream_context_create(array("ssl" => array("capture_peer_cert_chain" => true)));
		$socket = @stream_socket_client("ssl://$domain:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
		if (!$socket) {
			return false;
		}
		$context = stream_context_get_params($socket);
		if (!$context) {
			return false;
		}
		foreach ($context["options"]["ssl"]["peer_certificate_chain"] as $value) {
			//使用openssl扩展解析证书，这里使用x509证书验证函数
			$cerInfo = openssl_x509_parse($value);
			if(isset($cerInfo['name'])) {      
				return $cerInfo;
			}
		}
		return $context;
	}

	/**
     *  redis排他锁
     */
    public static function lock($name = 'global_lock', $time = 10, $num = 1) {
		$redis = self::getRedis();
        $name = parsePrefix($name);
        $value = $redis->incrby($name, 1);
        if ($num < $value) {
            return false;
        }
        if (1 == $value) {
            $redis->expire($name, $time);
        }
        return true;
    }
}


