<?php
$client = new swoole_client(SWOOLE_SOCK_TCP);
if (!$client->connect('127.0.0.1', 9501, -1))
{
    exit("connect failed. Error: {$client->errCode}\n");
}

$data = <<<str
{
  "event": "ping",
  "data": {
    "hosts": ["http:\/\/www.baidu.com"],
    "http_host": "www.baidu.com",
    "paths": [{
      "method": "get",
      "data": "",
      "path": "\/silk\/bulleScreen?source_type=1",
      "validate_type": "2",
      "validate_rule": "json"
    }, {
      "method": "get",
      "data": "",
      "path": "\/silk\/center\/isLogin",
      "validate_type": "2",
      "validate_rule": "json"
    }]
  }
}
str;
$client->send($data);
echo $client->recv();
$client->close();