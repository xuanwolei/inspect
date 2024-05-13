<?php
use Yaf\Config_Ini;
/**
 *  清除emoji
 */
function clearEmoji($str){
    $tmpStr = json_encode($str);
    $tmpStr = preg_replace_callback("/(\\\u[0-9a-f]{4})/i",function($match){
        return addslashes($match['0']);
    },$tmpStr); //将emoji的unicode留下，其他不动
    $text = json_decode($tmpStr);

    return $text;
}
function p(){
	$args=func_get_args();  //获取多个参数
	if(count($args)<1){
		p($_POST);
	}	

	echo '<div style="width:100%;text-align:left"><pre>';
	//多个参数循环输出
	foreach($args as $arg){
		if(is_array($arg)){  
			print_r($arg);
			echo '<br>';
		}else if(is_string($arg)){
			echo $arg.'<br>';
		}else{
			var_dump($arg);
			echo '<br>';
		}
	}
	echo '</pre></div>';	
}

/**
 * 系统加密方法
 *
 * @param string $data
 *        	要加密的字符串
 * @param string $key
 *        	加密密钥
 * @param int $expire
 *        	过期时间 单位 秒
 * @return string

 */
function encrypt($data, $key = '', $expire = 0) {
	$key = md5 ( empty ( $key ) ? C('DATA_AUTH_KEY') : $key );
	$data = base64_encode ( $data );
	$x = 0;
	$len = strlen ( $data );
	$l = strlen ( $key );
	$char = '';

	for($i = 0; $i < $len; $i ++) {
		if ($x == $l)
			$x = 0;
		$char .= substr ( $key, $x, 1 );
		$x ++;
	}

	$str = sprintf ( '%010d', $expire ? $expire + time () : 0 );

	for($i = 0; $i < $len; $i ++) {
		$str .= chr ( ord ( substr ( $data, $i, 1 ) ) + (ord ( substr ( $char, $i, 1 ) )) % 256 );
	}
	return str_replace ( array (
		'+',
		'/',
		'='
	), array (
		'-',
		'_',
		''
	), base64_encode ( $str ) );
}

/**
 * 系统解密方法
 *
 * @param string $data
 *        	要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param string $key
 *        	加密密钥
 * @return string
 */
function decrypt($data, $key = '') {
	$key = md5 ( empty ( $key ) ? C('DATA_AUTH_KEY') : $key );
	$data = str_replace ( array (
		'-',
		'_'
	), array (
		'+',
		'/'
	), $data );
	$mod4 = strlen ( $data ) % 4;
	if ($mod4) {
		$data .= substr ( '====', $mod4 );
	}
	$data = base64_decode ( $data );
	$expire = substr ( $data, 0, 10 );
	$data = substr ( $data, 10 );

	if ($expire > 0 && $expire < time ()) {
		return '';
	}
	$x = 0;
	$len = strlen ( $data );
	$l = strlen ( $key );
	$char = $str = '';

	for($i = 0; $i < $len; $i ++) {
		if ($x == $l)
			$x = 0;
		$char .= substr ( $key, $x, 1 );
		$x ++;
	}

	for($i = 0; $i < $len; $i ++) {
		if (ord ( substr ( $data, $i, 1 ) ) < ord ( substr ( $char, $i, 1 ) )) {
			$str .= chr ( (ord ( substr ( $data, $i, 1 ) ) + 256) - ord ( substr ( $char, $i, 1 ) ) );
		} else {
			$str .= chr ( ord ( substr ( $data, $i, 1 ) ) - ord ( substr ( $char, $i, 1 ) ) );
		}
	}
	return base64_decode ( $str );
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function I($name,$default='',$filter=null,$datas=null) {
    static $_PUT	=	null;
    if(strpos($name,'/')){ // 指定修饰符
        list($name,$type) 	=	explode('/',$name,2);
    }
    if(strpos($name,'.')) { // 指定参数来源
        list($method,$name) =   explode('.',$name,2);
    }else{ // 默认为自动判断
        $method =   'param';
    }
    switch(strtolower($method)) {
        case 'get'     :
            $input =& $_GET;
            break;
        case 'post'    :
            $input =& $_POST;
            break;
        case 'put'     :
            if(is_null($_PUT)){
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input 	=	$_PUT;
            break;
        case 'param'   :
            switch($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input  =  $_POST;
                    break;
                case 'PUT':
                    if(is_null($_PUT)){
                        parse_str(file_get_contents('php://input'), $_PUT);
                    }
                    $input 	=	$_PUT;
                    break;
                default:
                    $input  =  $_GET;
            }
            break;
        case 'request' :
            $input =& $_REQUEST;
            break;
        case 'session' :
            $input =& $_SESSION;
            break;
        case 'cookie'  :
            $input =& $_COOKIE;
            break;
        case 'server'  :
            $input =& $_SERVER;
            break;
        case 'globals' :
            $input =& $GLOBALS;
            break;
        case 'data'    :
            $input =& $datas;
            break;
        default:
            return null;
    }

    if(''==$name) { // 获取全部变量
        $data       =   $input;
        $filters    =   isset($filter)?$filter:C('DEFAULT_FILTER');
        if($filters) {
            if(is_string($filters)){
                $filters    =   explode(',',$filters);
            }
            foreach($filters as $filter){
                $data   =   array_map_recursive($filter,$data); // 参数过滤
            }
        }
    }elseif(isset($input[$name])) { // 取值操作
        $data       =   $input[$name];
        $filters    =   isset($filter)?$filter:C('DEFAULT_FILTER');
        if($filters) {
            if(is_string($filters)){
                if(0 === strpos($filters,'/')){
                    if(1 !== preg_match($filters,(string)$data)){
                        // 支持正则验证
                        return   isset($default) ? $default : null;
                    }
                }else{
                    $filters    =   explode(',',$filters);
                }
            }elseif(is_int($filters)){
                $filters    =   array($filters);
            }
            if(is_array($filters)){
                foreach($filters as $filter){
                    if(function_exists($filter)) {
                        $data   =   is_array($data) ? array_map_recursive($filter,$data) : $filter($data); // 参数过滤
                    }else{
                        $data   =   filter_var($data,is_int($filter) ? $filter : filter_id($filter));
                        if(false === $data) {
                            return   isset($default) ? $default : null;
                        }
                    }
                }
            }

        }
        if(!empty($type)){
            switch(strtolower($type)){
                case 'a':	// 数组
                    $data 	=	(array)$data;
                    break;
                case 'd':	// 数字
                    $data 	=	(int)$data;
                    break;
                case 'f':	// 浮点
                    $data 	=	(float)$data;
                    break;
                case 'b':	// 布尔
                    $data 	=	(boolean)$data;
                    break;
                case 's':   // 字符串
                default:
                    $data   =   (string)$data;
            }
        }
    }else{ // 变量默认值
        $data       =    isset($default)?$default:null;
    }
    return $data;
}

function array_map_recursive($filter, $data) {
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}



/**
 *	读取或修改配置文件
 *	@param string $key 键
 *	@param mixed $value [值]
 *	@return mixed
 */
// function C($key, $value = null){

// 	static $allConfig;
// 	if (!$allConfig) {
// 		$yaf_config = Yaf\Application::app()->getConfig();
// 		$allConfig  = $yaf_config->toArray();
// 	}
	
// 	if ($value) {
//  		$allConfig[$key] = $value;
// 	}

// 	$keys = explode('.', $key);
// 	$result = $allConfig;
// 	if (count($keys) > 1) {
// 		foreach ($keys as $v) {
// 			$result = $result[$v];
// 		}
// 	} else {
// 		$result = $result[$key];
// 	}
// 	return $result;
// }

/**
 *  读取或修改配置文件
 *  @param string $key 键
 *  @param mixed $value [值]
 *  @return mixed
 */
function C($key, $value = null, $is_reload = false){
    static $dynamicConfig;
    if (!$dynamicConfig || true == $is_reload) {
        $allConfig = parse_ini_file(CONFIG_PATH,true);
        foreach ($allConfig as $key => $value) {
            $k = str_replace(' ', '', $key);
            if ($k == $key) {
                continue;
            }
            $allConfig[$k] = $value;
            unset($allConfig[$key]);
        }
        $environ = getenv('ENVIRON');
        if (empty($environ)){
            $environ = ini_get('yaf.environ');
        }
        
        $dynamicConfig = array_merge($allConfig['common'],$allConfig["{$environ}:common"]);
    }
    if ($is_reload) {
        return true;
    }
    if ($value) {
        $dynamicConfig[$key] = $value;
    }
    
    return $dynamicConfig[$key];
}


/**
 *  实例化数据库
 *  @param string $tableName 表名称（无需前缀）
 *  @param string $db_dsn 配置文件中数据dsn配置
 *  @param string $db_prefix 表前缀
 */
function db($tableName , $db_dsn = '' , $db_prefix = ''){
    return DbPdo::getInstance($tableName , C($db_dsn) , $db_prefix);
}


function newObj($class){
	//危险路径不加载
	if (strpos($class, '/') !== false) {
		return false;
	}
	if (!class_exists($class)) {
		include APP_PATH."operation/{$class}.php";
	}

	$obj = new $class();
	return $obj;
}

function parseTwoStr(string $str){
    $length = floor(mb_strlen($str) / 2);
    $data = array();
    for ($i = 0; $i < $length; $i++) { 
        $data[] = substr($str, $i * 2, 2);
    }

    return $data;
}

function secho(string $message, $type = 'sysDebug'){
    if (!C('debug_log')) {
        return false;
    }
    uecho($message, $type);
}

function uecho($message, $type = 'debug'){
    if (is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    $date = date('Y-m-d H:i:s');
    echo "[{$type}][{$date}]:{$message}".PHP_EOL;
}

function getRandWorker(){
    return mt_rand(0, C('worker_num') - 1);
}

//增加前缀
function parsePrefix(string $name){
    $prefix = 'inspect_';
    return $prefix.$name;
}

function getAdminProjectAddr(int $id){
    return C('admin_host')."#/project/statistic/{$id}";
}

