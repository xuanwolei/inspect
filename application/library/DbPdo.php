<?php
/*********************************************************************************
 * 数据库操作类。 *
 * *******************************************************************************
 * $Author: 袁宝城 (429710096@qq.com) $                                    *
 * $Date: 2015-09-17 21:00:00 $                                                  * 
 * ******************************************************************************/

Class DbPdo{
	protected $where  	= ''; //where条件
	protected $field  	= '*'; 
	protected $group  	= '';
	protected $having	= '';
	protected $limit  	= '';
	protected $order  	= '';
	protected $parameters= [];
	protected $db_ame 	= '';
	protected $alias     = '';
	protected $join      = '';
	protected $db     	= ''; //数据库连接对象
	protected $stmt; //PDOStatement
	protected $db_dsn; //当前数据库连接配置
	//表前缀
	protected $db_prefix = '';
	protected $sql    	= '';	
	//FOR UPDATE锁表
	protected $lock	    = '';	
	protected $sql_all	= array();
	protected static $db_cache	= array();
	//重连code
	protected static $mysql_reconncet_code = array(
		2006,//未知的MySQL服务器主机
		2007,//MySQL服务器不可用。
		2013,//服务器握手过程中出错
	);

	/**
	*   单例模式连接数据库
	*	@param string $db_dsn 配置文件中数据dsn配置
 	*	@param string $db_prefix 表前缀
	* 	@return $this
	*/
	public static function getInstance($dbname = '' , $db_dsn = '' , $db_prefix = ''){		
		$db_key = "{$db_dsn}_xw_php";
		if (self::$db_cache[$db_key]) {
			return self::$db_cache[$db_key];
		}
		
		self::$db_cache[$db_key] = new self;	
		self::$db_cache[$db_key]->connect($db_dsn, $db_prefix)->table($dbname);

		return self::$db_cache[$db_key];
	}

	/**
	 *	连接数据库
	 *	@param db_dsn dsn配置
	 *	@param db_prefix 表前缀
	 */
	public function connect($db_dsn = '' , $db_prefix = ''){
		//解析数据库配置
		$config   		 = self::_parse_dsn($db_dsn , $db_prefix);
		//创建PDO对象
		$db_pdo_key = "{$db_dsn}_pdo";
		if (!self::$db_cache[$db_pdo_key]) {
			try{
				$options = array();
				//持久化连接
				if (C('DB_PERSISTENT')) {
					$options[PDO::ATTR_PERSISTENT] = true;
				}
				$pdo = new PDO($config['db_dsn'] , $config['username'] , $config['password'], $options);
				$pdo->query("SET NAMES utf8");
				if (C('application.dispatcher.throwException')) {
					//异常模式
					$pdo->setattribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
				}
			} catch(PDOException $e) {
				secho('PDOException:'.$e->getMessage(), 'PDOException');
				return false;
			}
			$this->db_dsn = $db_dsn;
			$this->db_prefix = $config['db_prefix']; 
			self::$db_cache[$db_pdo_key] = $this->db = $pdo;
		}

		return $this;
	}

	/**
	 *	重新连接数据库
	 */
	public function reconnect(){
		$db_pdo_key = "{$this->db_dsn}_pdo";
		unset(self::$db_cache[$db_pdo_key]);
		return $this->connect($this->db_dsn, $this->db_prefix);
	}

	/*
     @der 设置数据库表名
	*/
	public function table($dbname){
		$this->db_name = $this->db_prefix.$dbname;
		return $this;
	}

	/**
	 *	设置表别名
	 */
	public function alias($alias){
		$this->alias = " AS {$alias} ";
		return $this;
	}

	/**
     *   @der where条件   
     *	 @param array $where where条件
     *			array('id' => 5 , name => 'ybc');    						WHERE id = 5 AND  name = 'ybc'
     *			array('id' => 5, name => array('like','ybc%') ) 		    WHERE id = 5 AND  name like 'ybc%'
     *          array('id' => array( array('egt',5) , array('elt',10) ) )   WHERE id >= 5 AND id <= 10
	 */
    public function where($where, array $parameters = []){
    	if (is_array($where)) {
    		$where = $this->filter($where);
    	}
    	if (!empty($parameters)) {
    		$single = '';
    	} else {
    		$single = '\'';
    	}
    	$type = 'AND';
    	if ( isset($where['WHERETYPE']) ) {
    		$type = 'OR';
    		unset($where['WHERETYPE']);
    	}
    	$keys = array(
    		'LIKE'		  => 'LIKE',
    		'GT'		  => '>',
    		'LT'		  => '<',
    		'EGT'		  => '>=',
    		'ELT'		  => '<=',
    		'NEQ'		  => '!=',
    		'IN'		  => '()',
    		'NOT IN'	  => '()',
    		'BETWEEN'	  => 'AND',
    		'NOT BETWEEN' => 'AND',
		);
    	if (is_array($where)) {
    		$pwhere = 'WHERE ';
    		foreach($where as $key => $v) {
    			//多重数组的情况
    			if (is_array($v)) {
    				
    				if ( !is_array(current($v)) ) {
    					
    					$first = strtoupper(current($v));
	    				//如果不存在就退出当层循环
	    				if ( !$keys[$first] ) {
	    					continue;
	    				}
	    				$value2 = next($v);
	    				if (is_array($value2) && $keys[$first] != 'AND') {
	    					$value2 = implode(',', $value2);
	    				}
	    				if ($keys[$first] == '()') {
	    					$pwhere .= "{$key} {$first}(".$value2.") {$type} ";
	    				} elseif($keys[$first] == 'AND') {
	    					$pwhere .= "{$key} {$first} {$single}{$value2['0']}{$single} AND {$single}{$value2['1']}{$single} {$type} ";	    					
	    				} else {
	    					$pwhere .= "{$key} {$keys[$first]} {$single}".$value2."{$single} {$type} ";
	    				}
	    			//大于并且小于
	    			} else {

	    				foreach ($v as $key2 => $v2) {
	    					$first = strtoupper(current($v2));

		    				//如果不存在就退出当层循环
		    				if ( !$keys[$first] ) {
		    					continue;
		    				}
		    				$value2 = next($v2);
		    				if (is_array($value2) && $keys[$first] != 'AND') {
		    					$value2 = implode(',', $value2);
		    				}
		    				if ($keys[$first] == '()') {
	    						$pwhere .= "{$key} {$first}(".$value2.") {$type} ";
		    				} elseif($keys[$first] == 'AND') {
		    					$pwhere .= "{$key} {$first} {$single}{$value2['0']}{$single} AND {$single}{$value2['1']}{$single} {$type} ";	    					
		    				} else {
		    					$pwhere .= "{$key} {$keys[$first]} {$single}".$value2."{$single} {$type} ";
		    				}
	    				}
	    			}
    			} else {
    				$pwhere .= "{$key}={$single}{$v}{$single} {$type} ";
    			}
    		}

    		$this->where = rtrim($pwhere,$type.' ');

    	} elseif (!empty($where) ) {
    		$this->where='WHERE '.$where;
    	} else {
    		$this->where = '';
    	}
    	$this->parameters = $parameters;
    	return $this;
    }

    /**
	 *	@der 选择要返回的字段
	 *  @param mixed $field 要查询的字段
	 *  @param boolean $istrun 是否取反 
	 *  @return object $this
     */
	public function field($field = true, $istrun = false){

		if ($field === true || !$field) {
			return $this;
		}
		if ( !is_array($field) ) {
			$field = explode(',', $field);
		}
		if ($istrun) {
			$this->sql = "DESC ".$this->db_name." ";
			$result    = $this->query($this->sql);
			$fieldAll  = array();

			foreach ($result  as $key => $v) {
				if ( !in_array($v['Field'] , $field) ) {
					$fieldAll[] = $v['Field'];
				}
			}
			
			$field = $fieldAll;
			unset($fieldAll);		
		}

		$this->field = implode(',', $field);
		
		return $this;
	}

	/**
	 *	连表查询
	 */
	public function join($join){
		$this->join = preg_replace_callback("/(__[a-z,A-z,0-9]+__)/is", function($param){
		  return strtolower($this->db_prefix.trim($param['0'], '__'));
		}, $join);
		return $this;		
	}

	public function order($order){
		if ( is_array($order) ) {
			$str = '';
			foreach ($order as $key => $v) {
				$str .= "{$key} $v,";
			}
			$order = rtrim($str , ',');
		}
		$this->order="ORDER BY {$order}";
		return $this;
	}

	/**
	 *	分组
	 *	@param mixed $group 
	 *	@return $this
	 */
	public function group($group){
		if ( is_array($group) ) {
			$str = '';
			foreach ($group as $key => $v) {
				$str .= "{$key} $v,";
			}
			$group = rtrim($str , ',');
		}
		$this->group = "GROUP BY {$group}";
		return $this;
	}

	/**
	 *	分组后的筛选
	 *	@param string $having
	 *	@return $this
	 */
	public function having($having = ''){
		$this->having = "HAVING {$having}";
		return $this;
	}

	public function limit($limit){
		$this->limit='LIMIT '.$limit;
		return $this;
	}

	public function select(){

		$this->sql = 'SELECT '.$this->field.' FROM '.$this->db_name.$this->alias.$this->join.' '.$this->where.' '.$this->group.' '.$this->order.' '.$this->limit.' '.$this->lock;

		return $this->query($this->sql);
	}

	public function find($id = ''){
		//如果没有传递where条件，则用主键		
		if (!$this->where && $id) {
			$pri = $this->get_pri();
			$this->where = "WHERE {$pri} == {$id}";
		}
		$this->sql = 'SELECT '.$this->field.' FROM '.$this->db_name.$this->alias.$this->join.' '.$this->where.' '.$this->group.' '.$this->order.' '.$this->limit.' '.$this->lock;
		return $this->query($this->sql, true);
	}

	/**
	 *	直接执行语句
	 */
	public function query($sql , $limit = false, $parameters = []){
		if ($limit) {
			$limit = 'fetch';
		} else {
			$limit = 'fetchAll';
		}
		
		$result = $this->_exec($sql, $parameters);
		if (false === $result) {
			return false;
		}

		$data = $this->stmt->$limit(PDO::FETCH_ASSOC);
		return $data;
	}

	/**
 	 *	执行ddl语句
 	 */
	public function execute($sql, $parameters = []){	
		$result = $this->_exec($sql, $parameters);
		if (false === $result) {
			return false;
		}
		
		return $this->stmt->rowCount();
	}

	public function add(array $arr, array $parameters = []){
		$arr = $this->filter($arr);
    	if (!empty($parameters)) {
    		$single = '';
    	} else {
    		$single = '\'';
    	}
		$arr    = $this->get_field($arr);
		$name   = '';
		$values = '';
		foreach($arr as $key=>$v){			
			if (!is_null($v)) {
				$name   .= "`{$key}`,"; 
				$values .= "{$single}{$v}{$single}".',';
			}
		}
		$name   =rtrim($name,',');
		$values =rtrim($values,',');

		$this->sql = "INSERT INTO ".$this->db_name."({$name}) values({$values})";
		$count =  $this->execute($this->sql, $parameters);
		
		if ($count !== false) {
			return $this->db->lastInsertId();
		} else {
			return false;
		}
	}

	/**
	 *	@der 同时插入多条记录
	 *  @param array $arr 插入的记录(二维数组)
	 *	@param array $parameters 一个元素个数和将被执行的 SQL 语句中绑定的参数一样多的数组
	 *	@return 最后一条插入的ID
	 */
	public function addAll(array $arr, array $parameters = []){
		$arr = $this->filter($arr);
    	if (!empty($parameters)) {
    		$single = '';
    	} else {
    		$single = '\'';
    	}
		//必须是二维数组
		if ( !is_array(current($arr)) ) {
			return false;
		}

		$values = 'values(';
		$name   = '';
		$i = 0;
		foreach ($arr as $key1 => $value) {		
			$value = $this->get_field($value);	

			foreach($value as $key=>$v){
				$i == 0 && $name   .= "`{$key}`,"; 
				if (is_null($v)) {
					$values .= "'',";
				} else {
					$values .= "{$single}{$v}{$single}".',';
				}
			}
			$values   = rtrim($values,','); 
			$values .= '),(';
			$i == 0 && $name =rtrim($name,',');
			$values =rtrim($values,',');
			$i++;
		}
		$values = rtrim($values , ',(');

		$this->sql="INSERT INTO ".$this->db_name."({$name}) {$values}";
		return $this->execute($this->sql, $parameters);
	}

	/**
	 *	修改,返回受上一个 SQL 语句影响的行数
	 */
	public function save($arr){	
		$arr = $this->get_field($arr);
		
		//如果没有传递where条件，则用主键	
		$pri = $this->get_pri();	
		if (!$this->where && $arr[$pri]) {
			$this->where = "WHERE {$pri} == {$arr[$pri]}";
		}

		$set = '';
		foreach($arr as $key=>$v){
			$v = addslashes($v);
			$set .= "{$key}='$v',";
		}
		$set = rtrim($set,',');
		$this->sql = "UPDATE ".$this->db_name." SET {$set} ".$this->where." ".$this->order." ".$this->limit;
		return $this->execute($this->sql);
	}

	public function delete(){
		$this->sql="DELETE FROM ".$this->db_name." ".$this->where.";";
		return $this->execute($this->sql);
	}

	public function count($field = ''){
		$field || $field = $this->get_pri();

		$this->sql = "SELECT count({$field}) AS {$field} FROM ".$this->db_name." ".$this->where."";
		return $this->query($this->sql,true)[$field];
	}

    public function sum($field = ''){
        $field || $field = $this->get_pri();

        $this->sql = "SELECT sum({$field}) AS {$field} FROM ".$this->db_name." ".$this->where."";
        return $this->query($this->sql)[$field];
    }

	public function setInc($field,$num=1){
		$this->sql = "UPDATE ".$this->db_name." SET {$field}={$field}+{$num} ".$this->where."";
		return $this->execute($this->sql);
	}

	public function setDec($field,$num=1){
		$this->sql = "UPDATE ".$this->db_name." SET {$field}={$field}-{$num} ".$this->where."";
		return $this->execute($this->sql);
	}

	public function getField($field){
		$this->sql = "SELECT {$field} FROM ".$this->db_name." ".$this->where." ".$this->order." limit 1";
		return $this->query($this->sql, true)[$field];
	}

	/**
	 * @der 获取最后一条sql
	 */
	public function getLastSql(){
		return $this->sql_all[$this->db_name];
	}

	/**
	 *	开始事务处理
	 */
	public function beginTransaction(){
		//关闭自动提交功能
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT,0);
        return $this->db->beginTransaction();
	}

	/**
	 *	回滚事务
	 */
	public function rollback(){
		$result =  $this->db->rollback();
		//开启自动提交功能
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT,1);
        return $result;
	}

	/**
	 *	提交事务
	 */
	public function commit(){
		$result =  $this->db->commit();
		//开启自动提交功能
        $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT,1);
        return $result;
	}

	/**
	 *	FOR UPDATE 锁表(排它锁) SELECT
	 *  share mode 共享锁
	 *	where 条件为主键时为行锁，如果where不为主键时就是全表锁。（使用 for update 之外的终端才会锁）
	 *	注1: FOR UPDATE仅适用于InnoDB，且必须在交易区块(BEGIN/COMMIT)事务中才能生效。
	 *	注2: 要测试锁定的状况，可以利用MySQL的Command Mode ，开二个视窗来做测试。
	 */
	public function lock($lock = ''){
		if ($lock === true) {
			$this->lock = 'FOR UPDATE';
		} elseif(!empty($lock)) {
			$this->lock = $lock;
		}
		return $this;
	}
	
	/**
	 *	魔术方法，调用不存在的方法时执行
	 */
	public function __call($name , $args = array()){		
		if ( !method_exists($this->db, $name) ) {
			//抛出异常
			secho("{$name} method does not exist!");
			return false;
		}
		call_user_method_array($name, $this->db, $args);
	}

	/*
	 @der 数组的过滤方法 
	*/
	public function filter($arr){
		if (C('DB_FILTER') == false) {
			return $arr;
		}
	 	foreach($arr as $key=>$v){
	 		if(is_array($v)){
	 			$this->filter($v);
	 		}else{
				$arr[$key] = addslashes($v); 			
	 		}	 		
	 	}
	 	return $arr;
	 }

 	/**
	 *	@der 重置成员属性
	 */
	protected function resetAttribute(){
		$this->where  = '';
		$this->field  = '*';
		$this->limit  = '';
		$this->group  = '';
		$this->order  = '';
		$this->having = '';
		$this->lock   = '';
		$this->parameters = [];
	}

	/**
	 *	解析数据库配置
	 *	@param string $db_dsn 数据库dsn配置
	 *	@param string $db_prefix 数据表前缀
	 *	@return array
	 */
	private static function _parse_dsn($db_dsn , $db_prefix) {
		$db_dsn || $db_dsn = C('DB_DSN');
		$arr = parse_url($db_dsn);
		$db_prefix || $db_prefix = $arr['fragment'];
		$config  = array(
				'db_type'    => $arr['scheme'],
				'username'	 => $arr['user'],
				'password'	 => $arr['pass'],
				'db_host'	 => $arr['host'],
				'db_port'	 => $arr['port'],
				'db_name'	 => ltrim($arr['path'], '/'),
				'db_prefix'	 => $db_prefix ? $db_prefix : '',
				'db_dsn'	 => "{$db_type}:host={$db_host};dbname={$db_name}",
			);
		$config['db_dsn'] = "{$config['db_type']}:host={$config['db_host']};dbname={$config['db_name']}";
		
		return $config;
	}

	/**
	 * @der 返回数据表拥有的字段
	 * @param array $arr 数据 
	 * @return array
	*/
	private function get_field(array $arr){
		$this->sql = "DESC ".$this->db_name." ";
		$result = $this->db->query($this->sql);		
		foreach ($result AS $v) {
			$field_list[$v['Field']] = $v['Field'];			
		}
		foreach ($arr as $key => &$v) {

			if (!isset($field_list[$key]) && isset($v[$key])) {
				unset($v[$key]);
			}
		}
		
		return $arr;
	}

	/**
	 * @der 添加SQL语句信息
	 */
	private function addSql($sql, array $parameters = []){		
		if ( C('application.dispatcher.throwException') == true) { 
			$this->sql_all[ $this->db_name ] = $sql;
		}
	}

	/**
     * @der 获取主键	
     * @return 主键
	 */
	private function get_pri(){
		$this->sql = "DESC ".$this->db_name." ";
		$data = $this->db->query($this->sql);

		foreach ($data as $v) {
			if ($v['Key'] == 'PRI') {
				return $v['Field'];
			}
		}

		return $data['0']['Field'];
	}

	/**
	 *	是否重连
	 */
	public function isReconnect(PDOStatement $stmt){
		$code = $stmt->errorCode();
		if (in_array($code, self::$mysql_reconncet_code)) {
			return true;
		}

		return false;
	}

	/**
	 *	执行语句
	 */
	private function _exec($sql, $parameters = []){
		if (empty($parameters) && !empty($this->parameters)) {
			$parameters = $this->parameters;
		}
		$this->sql = $sql;
		$this->addSql($this->sql, $parameters);
		//最多只连接两次
		for ($i = 0; $i < 2; $i++) {
			$this->stmt = $this->db->prepare($sql);
			$result     = $this->stmt->execute($parameters);
			//重连
			if (false === $result && $this->isReconnect($this->stmt)) {
				$this->reconnect();
				continue;
			}
			//重置成员属性
			$this->resetAttribute();
			return $result;
		}

		return false;
	}

	private function __construct(){}
}
