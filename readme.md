# 健康检查说明

![image](http://note.youdao.com/yws/public/resource/8309f1b754815f1fa60c45afd78b2196/xmlnote/45BCE3B68B2946AF953F4F4A82B3CA10/11066)

### 特性
- 多协议支持 http1.1/http2.0/websocket/tcp/udp 
- 多维度监控
- 数据可视化
- 实时报警通知

### 异常判断规则
符合以下任意一条规则，都属于异常

- 接口响应时间超出设置的阈值
- 网络连接异常
- 状态码和预期不一致
- DNS解析超时/失败
- 返回数据内容/类型和预期不一致
> 对于http1.1/http2.0/websocket协议，会验证HTTP状态码，tcp、udp协议只验证网络错误码。

### 响应时间说明
| 协议        | 域名   | ip |  
| --------   | -----  | -----  |
| http1.1/http2.0      |  相比ip会多一个DNS解析时间，不计入响应时间内  |   一个项目下的多个path(接口)，每次都会发起一个http请求，默认未开启keepalive，所以每次请求都会创建一个新的tcp连接     |
| websocket/tcp        |   同上   |   不包含tcp3次握手的连接建立过程，响应时间=消息发送+数据接收   |
| udp        |   同上   |   响应时间=数据发送+数据接收   |

### 报警级别
每个级别对应不同的预警级别，具体如下

| 级别        | 每次通知间隔   |  一小时内通知次数上限  |
| --------   | -----:  | :----:  |
| 1      | 2分钟   |   30     |
| 2        |   15分钟   |   4   |
| 3        |    30分钟    |  2  |
> 一般业务建议设置3级，等级越低，收到的通知越多，消息太多容易引起骚扰。

### 状态码返回说明

除了常见的状态码外，报错中还有4个特殊的状态，如下：

| 状态码        | 说明 | 
| --------   | -----  |
| -1      | 连接超时，服务器未监听端口或网络丢失  可以读取$errCode获取具体的网络错误码 |
| -2        |   请求超时，服务器未在规定的timeout时间内返回response      |
| -3        |   客户端请求发出后，服务器强制切断连接    | 
| -4        |   DNS解析失败    | 
> 对于http相关协议，会存在http状态码，其他协议除了上诉的4个状态外，还有会返回linux错误码（具体可查看linux错误码参照表）。

### linux错误码参照表

<table>
<thead>
<tr>
<th>C Name</th>
<th>Value</th>
<th>Description</th>
<th>含义</th>
</tr>
</thead>
<tbody><tr>
<td>Success</td>
<td>0</td>
<td>Success</td>
<td>成功</td>
</tr>
<tr>
<td>EPERM</td>
<td>1</td>
<td>Operation not permitted</td>
<td>操作不允许</td>
</tr>
<tr>
<td>ENOENT</td>
<td>2</td>
<td>No such file or directory</td>
<td>没有这样的文件或目录</td>
</tr>
<tr>
<td>ESRCH</td>
<td>3</td>
<td>No such process</td>
<td>没有这样的过程</td>
</tr>
<tr>
<td>EINTR</td>
<td>4</td>
<td>Interrupted system call</td>
<td>系统调用被中断</td>
</tr>
<tr>
<td>EIO</td>
<td>5</td>
<td>I/O error</td>
<td>I/O 错误</td>
</tr>
<tr>
<td>ENXIO</td>
<td>6</td>
<td>No such device or address</td>
<td>没有这样的设备或地址</td>
</tr>
<tr>
<td>E2BIG</td>
<td>7</td>
<td>Arg list too long</td>
<td>参数列表太长</td>
</tr>
<tr>
<td>ENOEXEC</td>
<td>8</td>
<td>Exec format error</td>
<td>执行格式错误</td>
</tr>
<tr>
<td>EBADF</td>
<td>9</td>
<td>Bad file number</td>
<td>坏的文件描述符</td>
</tr>
<tr>
<td>ECHILD</td>
<td>10</td>
<td>No child processes</td>
<td>没有子进程</td>
</tr>
<tr>
<td>EAGAIN</td>
<td>11</td>
<td>Try again</td>
<td>资源暂时不可用</td>
</tr>
<tr>
<td>ENOMEM</td>
<td>12</td>
<td>Out of memory</td>
<td>内存溢出</td>
</tr>
<tr>
<td>EACCES</td>
<td>13</td>
<td>Permission denied</td>
<td>拒绝许可</td>
</tr>
<tr>
<td>EFAULT</td>
<td>14</td>
<td>Bad address</td>
<td>错误的地址</td>
</tr>
<tr>
<td>ENOTBLK</td>
<td>15</td>
<td>Block device required</td>
<td>块设备请求</td>
</tr>
<tr>
<td>EBUSY</td>
<td>16</td>
<td>Device or resource busy</td>
<td>设备或资源忙</td>
</tr>
<tr>
<td>EEXIST</td>
<td>17</td>
<td>File exists</td>
<td>文件存在</td>
</tr>
<tr>
<td>EXDEV</td>
<td>18</td>
<td>Cross-device link</td>
<td>无效的交叉链接</td>
</tr>
<tr>
<td>ENODEV</td>
<td>19</td>
<td>No such device</td>
<td>设备不存在</td>
</tr>
<tr>
<td>ENOTDIR</td>
<td>20</td>
<td>Not a directory</td>
<td>不是一个目录</td>
</tr>
<tr>
<td>EISDIR</td>
<td>21</td>
<td>Is a directory</td>
<td>是一个目录</td>
</tr>
<tr>
<td>EINVAL</td>
<td>22</td>
<td>Invalid argument</td>
<td>无效的参数</td>
</tr>
<tr>
<td>ENFILE</td>
<td>23</td>
<td>File table overflow</td>
<td>打开太多的文件系统</td>
</tr>
<tr>
<td>EMFILE</td>
<td>24</td>
<td>Too many open files</td>
<td>打开的文件过多</td>
</tr>
<tr>
<td>ENOTTY</td>
<td>25</td>
<td>Not a tty device</td>
<td>不是 tty 设备</td>
</tr>
<tr>
<td>ETXTBSY</td>
<td>26</td>
<td>Text file busy</td>
<td>文本文件忙</td>
</tr>
<tr>
<td>EFBIG</td>
<td>27</td>
<td>File too large</td>
<td>文件太大</td>
</tr>
<tr>
<td>ENOSPC</td>
<td>28</td>
<td>No space left on device</td>
<td>设备上没有空间</td>
</tr>
<tr>
<td>ESPIPE</td>
<td>29</td>
<td>Illegal seek</td>
<td>非法移位</td>
</tr>
<tr>
<td>EROFS</td>
<td>30</td>
<td>Read-only file system</td>
<td>只读文件系统</td>
</tr>
<tr>
<td>EMLINK</td>
<td>31</td>
<td>Too many links</td>
<td>太多的链接</td>
</tr>
<tr>
<td>EPIPE</td>
<td>32</td>
<td>Broken pipe</td>
<td>管道破裂</td>
</tr>
<tr>
<td>EDOM</td>
<td>33</td>
<td>Math argument out of domain</td>
<td>数值结果超出范围</td>
</tr>
<tr>
<td>ERANGE</td>
<td>34</td>
<td>Math result not representable</td>
<td>数值结果不具代表性</td>
</tr>
<tr>
<td>EDEADLK</td>
<td>35</td>
<td>Resource deadlock would occur</td>
<td>资源死锁错误</td>
</tr>
<tr>
<td>ENAMETOOLONG</td>
<td>36</td>
<td>Filename too long</td>
<td>文件名太长</td>
</tr>
<tr>
<td>ENOLCK</td>
<td>37</td>
<td>No record locks available</td>
<td>没有可用锁</td>
</tr>
<tr>
<td>ENOSYS</td>
<td>38</td>
<td>Function not implemented</td>
<td>功能没有实现</td>
</tr>
<tr>
<td>ENOTEMPTY</td>
<td>39</td>
<td>Directory not empty</td>
<td>目录不空</td>
</tr>
<tr>
<td>ELOOP</td>
<td>40</td>
<td>Too many symbolic links encountered</td>
<td>符号链接层次太多</td>
</tr>
<tr>
<td>EWOULDBLOCK</td>
<td>41</td>
<td>Same as EAGAIN</td>
<td>和 EAGAIN 一样</td>
</tr>
<tr>
<td>ENOMSG</td>
<td>42</td>
<td>No message of desired type</td>
<td>没有期望类型的消息</td>
</tr>
<tr>
<td>EIDRM</td>
<td>43</td>
<td>Identifier removed</td>
<td>标识符删除</td>
</tr>
<tr>
<td>ECHRNG</td>
<td>44</td>
<td>Channel number out of range</td>
<td>频道数目超出范围</td>
</tr>
<tr>
<td>EL2NSYNC</td>
<td>45</td>
<td>Level 2 not synchronized</td>
<td>2 级不同步</td>
</tr>
<tr>
<td>EL3HLT</td>
<td>46</td>
<td>Level 3 halted</td>
<td>3 级中断</td>
</tr>
<tr>
<td>EL3RST</td>
<td>47</td>
<td>Level 3 reset</td>
<td>3 级复位</td>
</tr>
<tr>
<td>ELNRNG</td>
<td>48</td>
<td>Link number out of range</td>
<td>链接数超出范围</td>
</tr>
<tr>
<td>EUNATCH</td>
<td>49</td>
<td>Protocol driver not attached</td>
<td>协议驱动程序没有连接</td>
</tr>
<tr>
<td>ENOCSI</td>
<td>50</td>
<td>No CSI structure available</td>
<td>没有可用 CSI 结构</td>
</tr>
<tr>
<td>EL2HLT</td>
<td>51</td>
<td>Level 2 halted</td>
<td>2 级中断</td>
</tr>
<tr>
<td>EBADE</td>
<td>52</td>
<td>Invalid exchange</td>
<td>无效的交换</td>
</tr>
<tr>
<td>EBADR</td>
<td>53</td>
<td>Invalid request descriptor</td>
<td>请求描述符无效</td>
</tr>
<tr>
<td>EXFULL</td>
<td>54</td>
<td>Exchange full</td>
<td>交换全</td>
</tr>
<tr>
<td>ENOANO</td>
<td>55</td>
<td>No anode</td>
<td>没有阳极</td>
</tr>
<tr>
<td>EBADRQC</td>
<td>56</td>
<td>Invalid request code</td>
<td>无效的请求代码</td>
</tr>
<tr>
<td>EBADSLT</td>
<td>57</td>
<td>Invalid slot</td>
<td>无效的槽</td>
</tr>
<tr>
<td>EDEADLOCK</td>
<td>58</td>
<td>Same as EDEADLK</td>
<td>和 EDEADLK 一样</td>
</tr>
<tr>
<td>EBFONT</td>
<td>59</td>
<td>Bad font file format</td>
<td>错误的字体文件格式</td>
</tr>
<tr>
<td>ENOSTR</td>
<td>60</td>
<td>Device not a stream</td>
<td>设备不是字符流</td>
</tr>
<tr>
<td>ENODATA</td>
<td>61</td>
<td>No data available</td>
<td>无可用数据</td>
</tr>
<tr>
<td>ETIME</td>
<td>62</td>
<td>Timer expired</td>
<td>计时器过期</td>
</tr>
<tr>
<td>ENOSR</td>
<td>63</td>
<td>Out of streams resources</td>
<td>流资源溢出</td>
</tr>
<tr>
<td>ENONET</td>
<td>64</td>
<td>Machine is not on the network</td>
<td>机器不上网</td>
</tr>
<tr>
<td>ENOPKG</td>
<td>65</td>
<td>Package not installed</td>
<td>没有安装软件包</td>
</tr>
<tr>
<td>EREMOTE</td>
<td>66</td>
<td>Object is remote</td>
<td>对象是远程的</td>
</tr>
<tr>
<td>ENOLINK</td>
<td>67</td>
<td>Link has been severed</td>
<td>联系被切断</td>
</tr>
<tr>
<td>EADV</td>
<td>68</td>
<td>Advertise error</td>
<td>广告的错误</td>
</tr>
<tr>
<td>ESRMNT</td>
<td>69</td>
<td>Srmount error</td>
<td>srmount 错误</td>
</tr>
<tr>
<td>ECOMM</td>
<td>70</td>
<td>Communication error on send</td>
<td>发送时的通讯错误</td>
</tr>
<tr>
<td>EPROTO</td>
<td>71</td>
<td>Protocol error</td>
<td>协议错误</td>
</tr>
<tr>
<td>EMULTIHOP</td>
<td>72</td>
<td>Multihop attempted</td>
<td>多跳尝试</td>
</tr>
<tr>
<td>EDOTDOT</td>
<td>73</td>
<td>RFS specific error</td>
<td>RFS 特定的错误</td>
</tr>
<tr>
<td>EBADMSG</td>
<td>74</td>
<td>Not a data message</td>
<td>非数据消息</td>
</tr>
<tr>
<td>EOVERFLOW</td>
<td>75</td>
<td>Value too large for defined data type</td>
<td>值太大，对于定义数据类型</td>
</tr>
<tr>
<td>ENOTUNIQ</td>
<td>76</td>
<td>Name not unique on network</td>
<td>名不是唯一的网络</td>
</tr>
<tr>
<td>EBADFD</td>
<td>77</td>
<td>File descriptor in bad state</td>
<td>文件描述符在坏状态</td>
</tr>
<tr>
<td>EREMCHG</td>
<td>78</td>
<td>Remote address changed</td>
<td>远程地址改变了</td>
</tr>
<tr>
<td>ELIBACC</td>
<td>79</td>
<td>Cannot access a needed shared library</td>
<td>无法访问必要的共享库</td>
</tr>
<tr>
<td>ELIBBAD</td>
<td>80</td>
<td>Accessing a corrupted shared library</td>
<td>访问损坏的共享库</td>
</tr>
<tr>
<td>ELIBSCN</td>
<td>81</td>
<td>A .lib section in an .out is corrupted</td>
<td>库段. out 损坏</td>
</tr>
<tr>
<td>ELIBMAX</td>
<td>82</td>
<td>Linking in too many shared libraries</td>
<td>试图链接太多的共享库</td>
</tr>
<tr>
<td>ELIBEXEC</td>
<td>83</td>
<td>Cannot exec a shared library directly</td>
<td>不能直接执行一个共享库</td>
</tr>
<tr>
<td>EILSEQ</td>
<td>84</td>
<td>Illegal byte sequence</td>
<td>无效的或不完整的多字节或宽字符</td>
</tr>
<tr>
<td>ERESTART</td>
<td>85</td>
<td>Interrupted system call should be restarted</td>
<td>应该重新启动中断的系统调用</td>
</tr>
<tr>
<td>ESTRPIPE</td>
<td>86</td>
<td>Streams pipe error</td>
<td>流管错误</td>
</tr>
<tr>
<td>EUSERS</td>
<td>87</td>
<td>Too many users</td>
<td>用户太多</td>
</tr>
<tr>
<td>ENOTSOCK</td>
<td>88</td>
<td>Socket operation on non-socket</td>
<td>套接字操作在非套接字上</td>
</tr>
<tr>
<td>EDESTADDRREQ</td>
<td>89</td>
<td>Destination address required</td>
<td>需要目标地址</td>
</tr>
<tr>
<td>EMSGSIZE</td>
<td>90</td>
<td>Message too long</td>
<td>消息太长</td>
</tr>
<tr>
<td>EPROTOTYPE</td>
<td>91</td>
<td>Protocol wrong type for socket</td>
<td>socket 协议类型错误</td>
</tr>
<tr>
<td>ENOPROTOOPT</td>
<td>92</td>
<td>Protocol not available</td>
<td>协议不可用</td>
</tr>
<tr>
<td>EPROTONOSUPPORT</td>
<td>93</td>
<td>Protocol not supported</td>
<td>不支持的协议</td>
</tr>
<tr>
<td>ESOCKTNOSUPPORT</td>
<td>94</td>
<td>Socket type not supported</td>
<td>套接字类型不受支持</td>
</tr>
<tr>
<td>EOPNOTSUPP</td>
<td>95</td>
<td>Operation not supported on transport</td>
<td>不支持的操作</td>
</tr>
<tr>
<td>EPFNOSUPPORT</td>
<td>96</td>
<td>Protocol family not supported</td>
<td>不支持的协议族</td>
</tr>
<tr>
<td>EAFNOSUPPORT</td>
<td>97</td>
<td>Address family not supported by protocol</td>
<td>协议不支持的地址</td>
</tr>
<tr>
<td>EADDRINUSE</td>
<td>98</td>
<td>Address already in use</td>
<td>地址已在使用</td>
</tr>
<tr>
<td>EADDRNOTAVAIL</td>
<td>99</td>
<td>Cannot assign requested address</td>
<td>无法分配请求的地址</td>
</tr>
<tr>
<td>ENETDOWN</td>
<td>100</td>
<td>Network is down</td>
<td>网络瘫痪</td>
</tr>
<tr>
<td>ENETUNREACH</td>
<td>101</td>
<td>Network is unreachable</td>
<td>网络不可达</td>
</tr>
<tr>
<td>ENETRESET</td>
<td>102</td>
<td>Network dropped</td>
<td>网络连接丢失</td>
</tr>
<tr>
<td>ECONNABORTED</td>
<td>103</td>
<td>Software caused connection</td>
<td>软件导致连接中断</td>
</tr>
<tr>
<td>ECONNRESET</td>
<td>104</td>
<td>Connection reset by</td>
<td>连接被重置</td>
</tr>
<tr>
<td>ENOBUFS</td>
<td>105</td>
<td>No buffer space available</td>
<td>没有可用的缓冲空间</td>
</tr>
<tr>
<td>EISCONN</td>
<td>106</td>
<td>Transport endpoint is already connected</td>
<td>传输端点已经连接</td>
</tr>
<tr>
<td>ENOTCONN</td>
<td>107</td>
<td>Transport endpoint is not connected</td>
<td>传输终点没有连接</td>
</tr>
<tr>
<td>ESHUTDOWN</td>
<td>108</td>
<td>Cannot send after transport endpoint shutdown</td>
<td>传输后无法发送</td>
</tr>
<tr>
<td>ETOOMANYREFS</td>
<td>109</td>
<td>Too many references: cannot splice</td>
<td>太多的参考</td>
</tr>
<tr>
<td>ETIMEDOUT</td>
<td>110</td>
<td>Connection timed</td>
<td>连接超时</td>
</tr>
<tr>
<td>ECONNREFUSED</td>
<td>111</td>
<td>Connection refused</td>
<td>拒绝连接</td>
</tr>
<tr>
<td>EHOSTDOWN</td>
<td>112</td>
<td>Host is down</td>
<td>主机已关闭</td>
</tr>
<tr>
<td>EHOSTUNREACH</td>
<td>113</td>
<td>No route to host</td>
<td>没有主机的路由</td>
</tr>
<tr>
<td>EALREADY</td>
<td>114</td>
<td>Operation already</td>
<td>已运行</td>
</tr>
<tr>
<td>EINPROGRESS</td>
<td>115</td>
<td>Operation now in</td>
<td>正在运行</td>
</tr>
<tr>
<td>ESTALE</td>
<td>116</td>
<td>Stale NFS file handle</td>
<td>陈旧的 NFS 文件句柄</td>
</tr>
<tr>
<td>EUCLEAN</td>
<td>117</td>
<td>Structure needs cleaning</td>
<td>结构需要清洗</td>
</tr>
<tr>
<td>ENOTNAM</td>
<td>118</td>
<td>Not a XENIX-named</td>
<td>不是 XENIX 命名的</td>
</tr>
<tr>
<td>ENAVAIL</td>
<td>119</td>
<td>No XENIX semaphores</td>
<td>没有 XENIX 信号量</td>
</tr>
<tr>
<td>EISNAM</td>
<td>120</td>
<td>Is a named type file</td>
<td>是一个命名的文件类型</td>
</tr>
<tr>
<td>EREMOTEIO</td>
<td>121</td>
<td>Remote I/O error</td>
<td>远程输入 / 输出错误</td>
</tr>
<tr>
<td>EDQUOT</td>
<td>122</td>
<td>Quota exceeded</td>
<td>超出磁盘配额</td>
</tr>
<tr>
<td>ENOMEDIUM</td>
<td>123</td>
<td>No medium found</td>
<td>没有磁盘被发现</td>
</tr>
<tr>
<td>EMEDIUMTYPE</td>
<td>124</td>
<td>Wrong medium type</td>
<td>错误的媒体类型</td>
</tr>
<tr>
<td>ECANCELED</td>
<td>125</td>
<td>Operation Canceled</td>
<td>取消操作</td>
</tr>
<tr>
<td>ENOKEY</td>
<td>126</td>
<td>Required key not available</td>
<td>所需键不可用</td>
</tr>
<tr>
<td>EKEYEXPIRED</td>
<td>127</td>
<td>Key has expired</td>
<td>关键已过期</td>
</tr>
<tr>
<td>EKEYREVOKED</td>
<td>128</td>
<td>Key has been revoked</td>
<td>关键被撤销</td>
</tr>
<tr>
<td>EKEYREJECTED</td>
<td>129</td>
<td>Key was rejected by service</td>
<td>关键被拒绝服务</td>
</tr>
<tr>
<td>EOWNERDEAD</td>
<td>130</td>
<td>Owner died</td>
<td>所有者死亡</td>
</tr>
<tr>
<td>ENOTRECOVERABLE</td>
<td>131</td>
<td>State not recoverable</td>
<td>状态不可恢复</td>
</tr>
<tr>
<td>ERFKILL</td>
<td>132</td>
<td>Operation not possible due to RF-kill</td>
<td>由于 RF-kill 而无法操作</td>
</tr>
<tr>
<td>EHWPOISON</td>
<td>133</td>
<td>Memory page has hardware error</td>
<td>分页硬件错误</td>
</tr>
</tbody></table>

## 数据库表
```
CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'http' COMMENT '监控类型：包含http,tcp,udp',
  `name` varchar(225) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '配置名称',
  `http_host` varchar(225) COLLATE utf8_unicode_ci DEFAULT NULL,
  `hosts` varchar(225) COLLATE utf8_unicode_ci DEFAULT NULL,
  `paths` text COLLATE utf8_unicode_ci,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `level` int(2) unsigned DEFAULT '1',
  `timeout` int(10) unsigned NOT NULL DEFAULT '3000',
  `state` int(2) unsigned DEFAULT '1',
  `desc` varchar(225) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `notice_token` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '钉钉token',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

## 常见问题
### 收到了报警通知，但是自己访问确没有异常？
在排查时，先确认以下几个情况：
1. 监控的应用是否是带域名，域名是否加过cdn加速
2. 应用是否有负载均衡或web防火墙。

==对于情况1：==
- 监控机器dns服务解析存在异常。
- 监控机器到某些的cdn节点连接异常。

==对于情况2：==
- 负载均衡的某一台机器异常，所以出现概率性的异常
- web防火墙做了拦截
> 对于有域名的项目，建议配置域名监控外，再加上目标服务器ip监控，在出现dns或cdn节点异常时可以提供参考。

### 钉钉自定义机器人配置参考
![image](http://note.youdao.com/yws/public/resource/8309f1b754815f1fa60c45afd78b2196/xmlnote/F33A94178347453B9F023436BD698D31/11143)
> 配置关键词“项目”
