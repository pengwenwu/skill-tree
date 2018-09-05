> Swoole是一个php扩展，直接通过phpize编译完动态加载即可。通过php -m | grep swoole查看是否成功安装Swoole  
> 
> [官网文档](https://wiki.swoole.com/wiki/index/prid-1)

## 进程管理 
- 默认使用`SWOOLE_PROCESS`，因此会额外创建`Master`和`Manager`两个进程。在设置`worker_num`之后，实际会出现`2 + worker_num`个进程  

### worker进程模型  
![worker进程模型](http://pic.pwwtest.com/swoole_worker进程模型.png)  
#### Mater主进程
主进程内有多个Reactor线程，基于epoll/kqueue进行网络事件轮询。收到数据后转发到worker进程去处理

#### Manager进程
对所有worker进程进行管理，worker进程生命周期结束或者发生异常时自动回收，并创建新的worker进程

#### Worker进程
对收到的数据进行处理，包括协议解析和响应请求。

## Server
### 运行流程图
![运行流程图](http://pic.pwwtest.com/server%E8%BF%90%E8%A1%8C%E6%B5%81%E7%A8%8B%E5%9B%BE_20180903182756.png)  

### 进程/线程结构图
![进程/线程结构图](http://pic.pwwtest.com/server%E8%BF%9B%E7%A8%8B%E7%BA%BF%E7%A8%8B%E7%BB%93%E6%9E%84%E5%9B%BE_20180903182918.png)  


