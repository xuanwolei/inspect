<?php

Class InotifyReload{
    // 监控的目录
    public $monitorDir;
    public $inotifyFd;
    public $server;
    public $fileType = array('php','ini');

    public function __construct($server){
        secho("已开启代码热重载");
        swoole_set_process_name("inspect-reload");
        $this->server = $server;
        $this->monitorDir = array(
            APPLICATION_PATH . "/conf",
            APP_PATH.'utils',
            APP_PATH.'jobs',
            APP_PATH.'logics',            
            APP_PATH.'services'
        );
        if (!extension_loaded('inotify')) {
            secho("热重载开启失败，请安装inotify扩展");
            return false;
        }
        
        $this->useInotify();        
    }

    public function useInotify(){
        global $monitorFiles;
        // 初始化inotify句柄
        $this->inotifyFd = inotify_init();
        // 设置为非阻塞
        stream_set_blocking($this->inotifyFd, 0);
        // 递归遍历目录里面的文件
        foreach ($this->monitorDir as $dir) {
            $dirIterator = new RecursiveDirectoryIterator($dir);
            $iterator = new RecursiveIteratorIterator($dirIterator);
            foreach ($iterator as $file) {
                // 监控文件类型
                if (!in_array(pathinfo($file, PATHINFO_EXTENSION),$this->fileType)) {
                    continue;
                }
                // 把文件加入inotify监控，这里只监控了IN_MODIFY文件更新事件
                $wd = inotify_add_watch($this->inotifyFd, $file, IN_MODIFY);
                $monitorFiles[$wd] = $file;
            }
        }
        
        // 监控inotify句柄可读事件
        swoole_event_add($this->inotifyFd, function ($fd) {
            global $monitorFiles;
            // 读取有哪些文件事件
            $events = inotify_read($fd);
            if ($events) {
                // 检查哪些文件被更新了
                foreach ($events as $ev) {
                    // 更新的文件
                    $file = $monitorFiles[$ev['wd']];
                    secho($file . " update", 'reload'); 
                    unset($monitorFiles[$ev['wd']]);
                    // 需要把文件重新加入监控
                    $wd = inotify_add_watch($fd, $file, IN_MODIFY);
                    $monitorFiles[$wd] = $file;
                }
                $this->server->reload();
            }
        }, null, SWOOLE_EVENT_READ);
    }
}

