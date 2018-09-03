
> 由于php不像nodejs、java一样可以常驻内存，没有现成的websock io，需要通过原生的php socket去实现服务端websocket的通信。对于菜鸟而言，还是使用现成的框架更方便，等水平足够了，才需要去考虑这些底层的实现  
# 什么是workerman？
Workerman是一款纯PHP开发的开源高性能的`PHP socket` 服务器框架。本身是一个PHP多进程服务器框架，具有PHP进程管理以及socket通信的模块，所以不依赖php-fpm、nginx或者apache等这些容器便可以独立运行。 
> 官网：http://www.workerman.net/workerman 
> workerman手册：http://doc.workerman.net/640361 

# 特性
1. 纯PHP开发
2. 支持PHP多进程
3. 支持TCP、UDP
4. 支持长连接
5. 支持各种应用层协议
6. 支持高并发
7. 支持服务平滑重启
8. 支持文件更新检测及自动加载
9. 支持以指定用户运行子进程
10. 支持对象或者资源永久保持
11. 高性能
12. 支持HHVM
13. 支持分布式部署
14. 支持守护进程化
15. 支持多端口监听
16. 支持标准输入输出重定向

# 环境要求
## Linux用户(含Mac OS)
1. 安装PHP>=5.3.3，并安装了pcntl、posix扩展
2. 建议安装event或者libevent扩展，但不是必须的（注意event扩展需要PHP>=5.4）

## Linux环境检查脚本
```bash
curl -Ss http://www.workerman.net/check.php | php
```
如果脚本中全部提示ok，则代表满足WorkerMan运行环境 

注意：检测脚本中没有检测event扩展或者libevent扩展，如果并发连接数大于1024建议安装event扩展或者libevent扩展 

## 如何安装扩展
参考文档：http://doc.workerman.net/appendices/install-extension.html

# 入门
## 安装
普通安装可以参照官网，通过git下载

也可以通过composer安装
```bash
composer require workerman/workerman
```

## 开发实例
### websocket实例
服务端实现
```php
<?php
require_once './vendor/autoload.php';

use Workerman\Worker;

// 注意：这里与上个例子不同，使用的是websocket协议
$ws_worker = new Worker("websocket://0.0.0.0:9501");

// 启动4个进程对外提供服务
$ws_worker->count = 4;

// 当收到客户端发来的数据后返回hello $data给客户端
$ws_worker->onMessage = function ($connection, $data) {
    // 向客户端发送hello $data
    $connection->send('hello ' . $data);
};

// 运行worker
Worker::runAll();
```

客户端实现
```js
ws = new WebSocket("ws://127.0.0.1:9501");
ws.onopen = function() {
    console.log("连接成功");
    ws.send('tom');
    console.log("给服务端发送一个字符串：tom");
};
ws.onmessage = function(e) {
    console.log("收到服务端的消息：" + e.data);
};
```

## 启动与停止
### 启动 
以debug(调试)方式启动
```bash
php start.php start
```

以daemon(守护进程)方式启动
```bash
php start.php start -d
```

### 停止
```bash
php start.php stop
```

### 重启
```bash
php start.php restart
```

### 平滑重启
```bash
php start.php reload
```

### 查看状态
```bash
php start.php status
```

### 查看连接状态
```bash
php start.php connections
```

### debug和daemon方式区别
1. 以debug方式启动，代码中echo、var_dump、print等打印函数会直接输出在终端 
2. 以daemon方式启动，代码中echo、var_dump、print等打印会默认重定向到/dev/null文件，可以通过设置`Worker::$stdoutFile = '/your/path/file';`来设置这个文件路径 
3. 以debug方式启动，终端关闭后workerman会随之关闭并退出 
4. 以daemon方式启动，终端关闭后workerman继续后台正常运行 

### 什么是平滑重启？
平滑重启不同于普通的重启，平滑重启可以做到在不影响用户的情况下重启服务，以便重新载入PHP程序，完成业务代码更新。

平滑重启一般应用于业务更新或者版本发布过程中，能够避免因为代码发布重启服务导致的暂时性服务不可用的影响。 

**注意：只有子进程运行过程中载入的文件支持reload，主进程载入的文件不支持reload。或者说Worker::runAll执行完后workerman运行过程中动态加载的文件支持reload，Worker::runAll执行前就载入的文件代码不支持reload** 

### 平滑重启原理
WorkerMan分为主进程和子进程，主进程负责监控子进程，子进程负责接收客户端的连接和连接上发来的请求数据，做相应的处理并返回数据给客户端。当业务代码更新时，其实我们只要更新子进程，便可以达到更新代码的目的。 

当WorkerMan主进程收到平滑重启信号时，主进程会向其中一个子进程发送安全退出(让对应进程处理完毕当前请求后才退出)信号，当这个进程退出后，主进程会重新创建一个新的子进程（这个子进程载入了新的PHP代码），然后主进程再次向另外一个旧的进程发送停止命令，这样一个进程一个进程的重启，直到所有旧的进程全部被置换为止。 

我们看到平滑重启实际上是让旧的业务进程逐个退出然后并逐个创建新的进程做到的。为了在平滑重启时不影响客用户，这就要求进程中不要保存用户相关的状态信息，即业务进程最好是无状态的，避免由于进程退出导致信息丢失。 

## 开发流程
### 注意事项
#### 平滑重启
注意：只有在on{...}回调中载入的文件平滑重启后才会自动更新，启动脚本中直接载入的文件或者写死的代码运行reload不会自动更新。

#### 区分主进程和子进程
有必要注意下代码是运行在主进程还是子进程，一般来说在`Worker::runAll();`调用前运行的代码都是在`主进程`运行的，`onXXX`回调运行的代码都属于`子进程`。注意写在`Worker::runAll();`后面的代码永远不会被执行。 

注意： 不要在主进程中初始化数据库、memcache、redis等连接资源，因为主进程初始化的连接可能会被子进程自动继承（尤其是使用单例的时候），所有进程都持有同一个连接，服务端通过这个连接返回的数据在多个进程上都可读，会导致数据错乱。同样的，如果任何一个进程关闭连接(例如daemon模式运行时主进程会退出导致连接关闭)，都导致所有子进程的连接都被一起关闭，并发生不可预知的错误，例如mysql gone away 错误。

推荐在onWorkerStart里面初始化连接资源。 

## Worker类
### 构造函数
初始化一个Worker容器实例，可以设置容器的一些属性和回调接口，完成特定功能。
```bash
Worker::__construct([string $listen , array $context])
```

#### 参数
##### `$listen` （可选参数，不填写表示不监听任何端口） 
$listen 的格式为 `<协议>://<监听地址>` 

##### `$context`
一个数组。用于传递socket的上下文选项。 

比如传递ssl证书 
```php
<?php
require_once __DIR__ . '/Workerman/Autoloader.php';
use Workerman\Worker;

// 证书最好是申请的证书
$context = array(
    'ssl' => array(
        'local_cert' => '/etc/nginx/conf.d/ssl/server.pem', // 也可以是crt文件
        'local_pk'   => '/etc/nginx/conf.d/ssl/server.key',
    )
);
// 这里设置的是websocket协议
$worker = new Worker('websocket://0.0.0.0:4431', $context);
// 设置transport开启ssl，websocket+ssl即wss
$worker->transport = 'ssl';
$worker->onMessage = function($con, $msg) {
    $con->send('ok');
};

Worker::runAll();
```

### 部分常用属性
#### id
```bash
int Worker::$id
```

当前worker进程的id编号，范围为0到$worker->count-1 

这个属性对于区分worker进程非常有用，例如1个worker实例有多个进程，开发者只想在其中一个进程中设置定时器，则可以通过识别进程编号id来做到这一点，比如只在该worker实例id编号为0的进程设置定时器 

**注意**：进程重启后id编号值是不变的。进程编号id的分配是基于每个worker实例的。每个worker实例都从0开始给自己的进程编号，所以worker实例间进程编号会有重复，但是一个worker实例中的进程编号不会重复。
```php
use Workerman\Worker;
use Workerman\Lib\Timer;
require_once './Workerman/Autoloader.php';

$worker = new Worker('tcp://0.0.0.0:8585');
$worker->count = 4;
$worker->onWorkerStart = function($worker)
{
    // 只在id编号为0的进程上设置定时器，其它1、2、3号进程不设置定时器
    if($worker->id === 0)
    {
        Timer::add(1, function(){
            echo "4个worker进程，只在0号进程设置定时器\n";
        });
    }
};
// 运行worker
Worker::runAll();
```

#### count
```bash
int Worker::$count
```

设置当前Worker实例启动多少个进程，不设置时默认为1。 

**注意**：此属性必须在`Worker::runAll();`运行前设置才有效。windows系统不支持此特性。

设置规则： 
- 每个进程占用内存之和需要小于总内存（一般来说每个业务进程占用内存大概40M左右） 
- 如果是`IO密集型`，也就是业务中涉及到一些`阻塞式IO`，比如一般的访问Mysql、Redis等存储都是阻塞式访问的，进程数可以开大一些，如`配置成CPU核数的3倍`。注意`非阻塞式IO`属于`CPU密集型`，而不属于IO密集型。 
- 如果是CPU密集型，也就是业务中没有阻塞式IO开销，例如使用异步IO读取网络资源，进程不会被业务代码阻塞的情况下，可以把`进程数设置成和CPU核数一样` 
- WorkerMan自身的IO都是`非阻塞`的，例如`Connection->send`等都是非阻塞的，属于`CPU密集型`操作。如果不清楚自己业务偏向于哪种类型，可设置进程数为`CPU核数的2倍`左右即可。 

#### transport
```bash
string Worker::$transport
```

设置当前Worker实例所使用的`传输层协议`，目前只支持3种(`tcp`、`udp`、`ssl`)。不设置默认为`tcp`。 

注意：ssl需要Workerman版本>=3.3.7

#### connections
```bash
array Worker::$connections
```

此属性中存储了当前进程的所有的`客户端连接对象`，其中id为connection的id编号

格式为：
```bash
array(id=>connection, id=>connection, ...)
```

#### stdoutFile
```bash
static string Worker::$stdoutFile
```

此属性为`全局静态属性`，如果以`守护进程`方式(-d启动)运行，则所有向终端的输出(echo var_dump等)都会被`重定向`到stdoutFile指定的文件中。

如果不设置，并且是以守护进程方式运行，则所有终端输出全部重定向到/dev/null

注意：此属性必须在Worker::runAll();运行前设置才有效。

```php
<?php
use Workerman\Worker;
require_once __DIR__ . '/Workerman/Autoloader.php';

Worker::$daemonize = true;
// 所有的打印输出全部保存在/tmp/stdout.log文件中
Worker::$stdoutFile = '/tmp/stdout.log';
$worker = new Worker('text://0.0.0.0:8484');
$worker->onWorkerStart = function($worker)
{
    echo "Worker start\n";
};
// 运行worker
Worker::runAll();
```

#### reloadable
设置当前Worker实例是否可以reload，即收到reload信号后`是否退出重启`。不设置默认为true，收到reload信号后自动重启进程。
```bash
bool Worker::$reloadable
```

#### daemonize
```bash
static bool Worker::$daemonize
```

此属性为`全局静态属性`，表示是否以daemon(守护进程)方式运行。如果启动命令使用了 `-d` 参数，则该属性会自动设置为true。也可以代码中手动设置

### 回调属性
#### onWorkerStart
```bash
callback Worker::$onWorkerStart
```

设置`Worker子进程`启动时的回调函数，每个子进程启动时都会执行。 

注意：onWorkerStart是在子进程启动时运行的，如果开启了多个子进程($worker->count > 1)，每个子进程运行一次，则总共会运行$worker->count次。

**回调函数的参数:**  

$worker: Worker对象

#### onWorkerReload
```bash
callback Worker::$onWorkerReload
```

设置Worker收到reload信号后执行的回调。

可以利用onWorkerReload回调做很多事情，例如在不需要重启进程的情况下重新加载业务配置文件。

**注意：** 

子进程收到reload信号默认的动作是退出重启，以便新进程重新加载业务代码完成代码更新。所以reload后子进程在执行完onWorkerReload回调后便立刻退出是正常现象。

如果在收到reload信号后只想让子进程执行onWorkerReload，不想退出，可以在初始化Worker实例时设置对应的Worker实例的reloadable属性为false。 

**回调函数的参数:**  

$worker: Worker对象

#### onConnect
```bash
callback Worker::$onConnect
```
当客户端与Workerman建立连接时(TCP三次握手完成后)触发的回调函数。每个连接只会触发`一次`onConnect回调。 

注意：onConnect事件仅仅代表客户端与Workerman完成了TCP三次握手，这时客户端还没有发来任何数据，此时除了通过$connection->getRemoteIp()获得对方ip，没有其他可以鉴别客户端的数据或者信息，所以在onConnect事件里无法确认对方是谁。要想知道对方是谁，需要客户端发送鉴权数据，例如某个token或者用户名密码之类，在`onMessage`回调里做鉴权。 

**回调函数的参数:**  

$connection: 连接对象

#### onMessage
```bash
callback Worker::$onMessage
```
当客户端通过连接发来数据时(Workerman收到数据时)触发的回调函数 

**回调函数的参数:**  

$connection: 连接对象 

$data: 客户端连接上发来的数据 

#### onClose
```bash
callback Worker::$onClose
```
当客户端连接与Workerman断开时触发的回调函数。不管连接是如何断开的，只要断开就会触发onClose。每个连接只会触发一次onClose。 

注意：如果对端是由于断网或者断电等极端情况断开的连接，这时由于无法及时发送tcp的fin包给workerman，workerman就无法得知连接已经断开，也就无法及时触发onClose。这种情况需要通过应用层`心跳`来解决。 

**回调函数的参数:**  

$connection: 连接对象 


#### onBufferDrain
```bash
callback Worker::$onBufferDrain
```
每个连接都有一个单独的应用层发送缓冲区，缓冲区大小由TcpConnection::$maxSendBufferSize决定，默认值为1MB，可以手动设置更改大小，更改后会对所有连接生效。 

该回调可能会在调用Connection::send后立刻被触发，比如发送大数据或者连续快速的向对端发送数据，由于网络等原因数据被大量积压在对应连接的发送缓冲区，当超过TcpConnection::$maxSendBufferSize上限时触发。

**回调函数的参数:**  

$connection: 连接对象

#### onBufferDrain
```bash
callback Worker::$onBufferDrain
```
该回调在应用层发送缓冲区数据全部发送完毕后触发。一般与onBufferFull配合使用，例如在onBufferFull时停止向对端继续send数据，在onBufferDrain恢复写入数据。

**回调函数的参数:**  

$connection: 连接对象

#### onError
```bash
callback Worker::$onError
```
当客户端的连接上发生错误时触发。 


目前错误类型有
1. 调用Connection::send由于客户端连接断开导致的失败（紧接着会触发onClose回调） (code:WORKERMAN_SEND_FAIL msg:client closed) 

2. 在触发onBufferFull后(发送缓冲区已满)，仍然调用Connection::send，并且发送缓冲区仍然是满的状态导致发送失败(不会触发onClose回调)(code:WORKERMAN_SEND_FAIL msg:send buffer full and drop package) 

3. 使用AsyncTcpConnection异步连接失败时(紧接着会触发onClose回调) (code:WORKERMAN_CONNECT_FAIL msg:stream_socket_client返回的错误消息) 

**回调函数的参数:**  

$connection: 连接对象 
$code: 错误码 
$msg: 错误消息 

### 接口
#### runAll
```bash
void Worker::runAll(void)
```
运行所有Worker实例。

**注意：**

Worker::runAll()执行后将`永久阻塞`，也就是说位于Worker::runAll()后面的代码将不会被执行。所有Worker实例化应该都在Worker::runAll()前进行。 

#### stopAll
```bash
void Worker::stopAll(void)
```
停止当前进程（子进程）的`所有Worker实例`并退出。 

此方法用于安全退出当前子进程，作用相当于调用exit/die退出当前子进程。

与直接调用exit/die区别是，直接调用exit或者die无法触发onWorkerStop回调，并且会导致一条WORKER EXIT UNEXPECTED错误日志。

#### listen
```bash
void Worker::listen(void)
```
用于实例化Worker后执行监听。 

## Connection类
### 属性
#### id
#### protocol
```bash
string Connection::$protocol
```
设置当前连接的协议类

#### worker
```bash
Worker Connection::$worker
```
此属性为只读属性，即当前connection对象所属的worker实例 

```php
use Workerman\Worker;
require_once __DIR__ . '/Workerman/Autoloader.php';

$worker = new Worker('websocket://0.0.0.0:8484');

// 当一个客户端发来数据时，转发给当前进程所维护的其它所有客户端
$worker->onMessage = function($connection, $data)
{
    foreach($connection->worker->connections as $con)
    {
        $con->send($data);
    }
};
// 运行worker
Worker::runAll();
```

#### maxSendBufferSize
```bash
int Connection::$maxSendBufferSize
```
此属性用来设置当前连接的应用层发送缓冲区大小。不设置默认为Connection::$defaultMaxSendBufferSize(1MB)。 

#### defaultMaxSendBufferSize
```bash
static int Connection::$defaultMaxSendBufferSize
```
此属性为全局静态属性，用来设置所有连接的默认应用层发送缓冲区大小。不设置默认为1MB。 Connection::$defaultMaxSendBufferSize可以动态设置，设置后只对之后产生的新连接有效 

#### maxPackageSize
```bash
static int Connection::$maxPackageSize
```
此属性为全局静态属性，用来设置每个连接能够接收的最大包包长。不设置默认为10MB。 

### 回调属性
与worker的回调属性作用相同 

### 接口
#### send
#### getRemoteIp
#### etRemotePort

### Timer定时器类
#### add
```bash
int \Workerman\Lib\Timer::add(float $time_interval, callable $callback [,$args = array(), bool $persistent = true])
```
定时执行某个函数或者类方法 

注意：定时器是在当前进程中运行的，workerman中不会创建新的进程或者线程去运行定时器。 

**参数** 
time_interval: 多长时间执行一次，单位秒，支持小数，可以精确到0.001，即精确到毫秒级别 

callback: 回调函数注意：如果回调函数是类的方法，则方法必须是public属性 

args: 回调函数的参数，必须为数组，数组元素为参数值 

persistent: 是否是持久的，如果只想定时执行一次，则传递false（只执行一次的任务在执行完毕后会自动销毁，不必调用Timer::del()）。默认是`true`，即一直定时执行 

**返回值** 
返回一个整数，代表计时器的timerid，可以通过调用Timer::del($timerid)销毁这个计时器。 

**示例** 
```bash
use \Workerman\Worker;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/Workerman/Autoloader.php';

$ws_worker = new Worker('websocket://0.0.0.0:8080');
$ws_worker->count = 8;
// 连接建立时给对应连接设置定时器
$ws_worker->onConnect = function($connection)
{
    // 每10秒执行一次
    $time_interval = 10;
    $connect_time = time();
    // 给connection对象临时添加一个timer_id属性保存定时器id
    $connection->timer_id = Timer::add($time_interval, function()use($connection, $connect_time)
    {
         $connection->send($connect_time);
    });
};
// 连接关闭时，删除对应连接的定时器
$ws_worker->onClose = function($connection)
{
    // 删除定时器
    Timer::del($connection->timer_id);
};

// 运行worker
Worker::runAll();
```
> 更多示例：http://doc.workerman.net/timer/add.html 

#### del
```bash
boolean \Workerman\Lib\Timer::del(int $timer_id)
```
删除某个定时器 

**示例** 
定时器回调中删除当前定时器
```bash
use \Workerman\Worker;
use \Workerman\Lib\Timer;
require_once __DIR__ . '/Workerman/Autoloader.php';

$task = new Worker();
$task->onWorkerStart = function($task)
{
    // 注意，回调里面使用当前定时器id必须使用引用(&)的方式引入
    $timer_id = Timer::add(1, function()use(&$timer_id)
    {
        static $i = 0;
        echo $i++."\n";
        // 运行10次后删除定时器
        if($i === 10)
        {
            Timer::del($timer_id);
        }
    });
};

// 运行worker
Worker::runAll();
```

#### 定时器注意事项
1. 只能在`onXXXX`回调中添加定时器。全局的定时器推荐在onWorkerStart回调中设置，针对某个连接的定时器推荐在onConnect中设置。 
2. 添加的定时任务在当前进程执行(不会启动新的进程或者线程)，如果任务很重（特别是涉及到网络IO的任务），可能会导致该进程阻塞，暂时无法处理其它业务。所以最好将耗时的任务放到单独的进程运行，例如建立一个/多个Worker进程运行 
3. 当前进程忙于其它业务时或者当一个任务没有在预期的时间运行完，这时又到了下一个运行周期，则会等待当前任务完成才会运行，这会导致定时器没有按照预期时间间隔运行。也就是说当前进程的业务都是串行执行的，如果是多进程则进程间的任务运行是并行的。 
4. 多进程设置了定时任务造可能会造成并发问题 
5. 可能会有1毫秒左右的误差 
6. 定时器不能跨进程删除，例如a进程设置的定时器无法在b进程直接调用Timer::del接口删除 
7. 不同进程间的定时器id可能会重复，但是同一个进程内产生的定时器id不会重复 

## 常见问题
### 心跳
注意：长连接应用必须加心跳，否则连接可能由于长时间未通讯被路由节点强行断开。 

心跳作用主要有两个： 
1. 客户端定时给服务端发送点数据，防止连接由于长时间没有通讯而被某些节点的防火墙关闭导致连接断开的情况。 
2. 服务端可以通过心跳来判断客户端是否在线，如果客户端在规定时间内没有发来任何数据，就认为客户端下线。这样可以检测到客户端由于极端情况(断电、断网等)下线的事件。 

建议值： 
建议心跳间隔小于60秒 

**示例** 
自动断开连接
```php
<?php
/**
 * Created by PhpStorm.
 * User: pengwenwu
 * Date: 2018/6/23
 * Time: 23:31
 */
require_once './vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;

$ws_worker = new Worker("websocket://0.0.0.0:9501");

// 启动4个进程对外提供服务
$ws_worker->count = 4;

// 心跳间隔25秒
define('HEARTBEAT_TIME', 25);

$ws_worker->onMessage = function ($connection, $data) {
    // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
    $connection->lastMessageTime = time();
    // 其它业务逻辑...
    $connection->send('hello' . $data);
};

// 进程启动后设置一个每秒运行一次的定时器
$ws_worker->onWorkerStart = function ($worker) {
    Timer::add(1, function () use ($worker) {
        $time_now = time();
        foreach ($worker->connections as $connection) {
            // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
            if (empty($connection->lastMessageTime)) {
                $connection->lastMessageTime = $time_now;
                continue;
            }
            // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
            if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                $connection->close();
            }
        }
    });
};

Worker::runAll();
```

> 更多详细请参考官方手册
