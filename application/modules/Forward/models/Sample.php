<?php
/**
 * @name SampleModel
 * @desc sample数据获取类, 可以访问数据库，文件，其它系统等
 * @author {&$AUTHOR&}
 */
class SampleModel {
	protected $_name = 'admin';
    protected $_primary = 'id';     
    protected $_db;
    private $_table = "admin";
    
    public function selectSample() {
        return 'Hello World!';
    }

    public function insertSample($arrInfo) {
        return true;
    }

    /*
     *用户登录判断
     *@param $username,$password
     *return $user_info | false 
     */
    public function login_sign($username,$password)
    {
  		$this->_db = $this->getAdapter();
  		$select = $this->_db->select();
  		$select->from($this->_table)
                 ->where('username = ?', $username)
                 ->where('password = ?',$password);		
  		return $this->_db->fetchRow($select);
    }

    /*
     *所有用户信息显示
     *
     */
    public function show_admins()
    {
        $this->_db = $this->getAdapter();
         $select = $this->_db->select();
         $select->from($this->_table)
         ->where('is_del','0');
         return $this->_db->fetchAll($select);
    }

    /*
     *添加新用户
     *
     */
    public function insert_admin($data)
    {
        $data['password'] = md5($data['password']);
        $this->_db = $this->getAdapter();
        var_dump($this->getAdapter());die;
        $this->_db->insert($this->_table,$data);
        return $this->_db->lastInsertId();
    }

    /*
     *删除用户
     *
     */
    public function del_admin($id){
        $this->_db = $this->getAdapter();
        $where = $this->_db->quoteInto('id = ?', $id);//条件
        return $this->_db->delete($this->_table, $where);
    }

    /*
     *获取用户信息
     *
     */
    public function get_info($id)
    {
      $this->_db = $this->getAdapter();
      $select = $this->_db->select();
      $select->from($this->_table)
                 ->where('id = ?', $id);
      return $this->_db->fetchRow($select);      
    }
    
    /*
     *修改用户信息
     *
     */
    public function update_admin($data,$id)
    {
      $this->_db = $this->getAdapter();
      $where = $this->_db->quoteInto('id = ?', $id);//条件
      return $this->_db->update($this->_table,$data,$where);      
    }
   
}
