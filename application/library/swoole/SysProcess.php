<?php
/**
 * 自定义进程
 * @author  ybc
 * @date(2018-7-13)
 */
Class SysProcess extends Process{	
	protected static $instance;

	protected function __construct(swoole_server $server){
		$process = new swoole_process(function($process) use ($server) {			
		    new InotifyReload($server);
		});
		$this->pushProcess('inotify', $process);
	}
}