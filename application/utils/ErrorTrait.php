<?php
/**
 * 接口错误信息处理
 * @author ybc
 * @date 2018-7-16
 */
Trait ErrorTrait{
	protected $errorMsg;
	protected $errorCode;

	public function setError($errorMsg, $errorCode = -1){
		$this->errorMsg = $errorMsg;
		$this->errorCode = $errorCode;
		return false;
	}

	public function getError(){
		return $this->errorMsg;
	}

	/**
	 * 获取错误code
	 * @return 
	 */
	public function getErrorCode(){
		return $this->errorCode;
	}
}
