<?php
/**
 * job 
 * @author ybc
 * @date(2018-7-13)
 */
Abstract Class Job{
	public $data;
	public $function = 'handle';
	protected $taskId;
	protected $fromId;

	public function __construct($data){
		$this->data = $data;
	}

	public abstract function handle();

	/**
	 *	添加任务
	 */
	public static function add(string $class, $data){
		$name = "{$class}Job";
		$job = new $name($data);
		self::dispatch($job);
	}

	public static function dispatch(&$job){
		Service::addTask($job);
		unset($job);
		return true;
	}

	/**
	 *	执行任务
	 */
	public function runJobs($taskId, $fromId){
		$this->taskId = $taskId;
		$this->fromId = $fromId;
		$function = $this->function;
		return $this->$function();
	}
}