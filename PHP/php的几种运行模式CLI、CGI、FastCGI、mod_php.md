# 常用运行模式
## CLI
CLI：命令行，可以在控制台或者shell中输入命令获取输出，没有header头信息   

## CGI
公共网关接口（Common Gateway Interface/CGI）：是一种重要的互联网技术，可以让一个客户端，从网页浏览器向执行在网络服务器上的程序请求数据。CGI描述了服务器和请求处理程序之间传输数据的一种标准。即web server将用户请求以消息的方式转交给PHP独立进程,PHP与web服务之间无从属关系。  

## FastCGI
快速通用网关接口（Fast Common Gateway Interface／FastCGI）：是一种让交互程序与Web服务器通信的协议。FastCGI是早期通用网关接口（CGI）的增强版本。  

**CGI 和 FastCGI 是一种通信协议规范，不是一个实体**  

CGI 程序和FastCGI程序，是指实现这两个协议的程序，可以是任何语言实现这个协议的。（PHP-CGI 和 PHP-FPM就是实现FastCGI的程序）  

### FastCGI和CGI的区别
- CGI每接收一个请求就要fork一个进程处理，只能接收一个请求作出一个响应。请求结束后该进程就会结束。  
- FastCGI会事先启动起来，作为一个cgi的管理服务器存在，预先启动一系列的子进程来等待处理，然后等待web服务器发过来的请求，一旦接受到请求就交由子进程处理，这样由于不需要在接受到请求后启动cgi，会快很多。  
- FastCGI使用`进程/线程池`来处理一连串的请求。这些进程/线程由FastCGI服务器管理，而不是Web服务器。 当进来一个请求时，Web服务器把环境变量和这个页面请求通过一个Socket长连接传递给FastCGI进程。FastCGI像是一个常驻型的CGI，它可以一直执行，在请求到达时不会花费时间去fork一个进程来处理(这是CGI对位人诟病的fork-and-execute模式)。正是因为它只是一个通信协议，它还支持分布式的运算，即FastCGI程序可以在网站服务器以外的主机上执行并且接受来自其他网站服务器的请求。  

### FastCGI整个流程
1. Web server启动时载入FastCGI进程管理器  
2. FastCGI自身初始化，启动多个CGI解释器进程(可见多个php-cgi)并等待来自Web server的请求  
3. 当请求Web server时，Web server通过socket请求FastCGI进程管理器，FastCGI进程管理器选择并连接到一个CGI解释器，Web server将CGI环境变量和标准输入发送到FastCGI子进程php-cgi  
4. FastCGI子进程处理请求完成后将标准输出和错误从同一连接返回给Web server，当FastCGI子进程结束后请求便结束。FastCGI子进程接着等待处理来自FastCGI进程管理器的下一个连接，在CGI模式中，php-cgi在此便退出了。  

**PHP-FPM**：PHP的FastCGI进程管理器  

### PHP-CGI 和 PHP-FPM的区别
php-cgi与php-fpm一样，也是一个fastcgi进程管理器  

php-cgi的问题在于：  
- php-cgi变更php.ini配置后需重启php-cgi才能让新的php-ini生效，不可以平滑重启  
- 直接杀死php-cgi进程,php就不能运行了。  

PHP-FPM和Spawn-FCGI就没有这个问题，守护进程会平滑从新生成新的子进程。针对php-cgi的不足，php-fpm应运而生。  

`PHP-FPM` 的管理对象是php-cgi。使用PHP-FPM来控制PHP-CGI的FastCGI进程  

## mod_php（传统模式）
即`apache的php模块`，将PHP做为web-server的`子进程`控制,两者之间有从属关系。  

最明显的例子就是在CGI模式下,如果修改了PHP.INI的配置文件,不用重启web服务便可生效，而模块模式下则需要重启web服务。  

以mod_php模式运行PHP，意味着php是作为apache的一个模块来启动的，因此只有在apache启动的时候会读取php.ini配置文件并加载扩展模块，在apache运行期间是不会再去读取和加载扩展模块的。如果修改php的配置，需要重启apache服务  

### Apache的工作模式 prefork的工作原理
一个单独的控制进程(父进程)负责产生子进程，这些子进程用于监听请求并作出应答。  

Apache总是试图保持一些备用的 (spare)或是空闲的子进程用于迎接即将到来的请求。这样客户端就无需在得到服务前等候子进程的产生。  

在Unix系统中，父进程通常以root身份运行以便邦定80端口，而 Apache产生的子进程通常以一个低特权的用户运行。User和Group指令用于配置子进程的低特权用户。运行子进程的用户必须要对他所服务的内容有读取的权限，但是对服务内容之外的其他资源必须拥有尽可能少的权限。  

### Apache的工作模式 worker的工作原理
每个进程能够拥有的线程数量是固定的。服务器会根据负载情况增加或减少进程数量。  

一个单独的控制进程(父进程)负责子进程的建立。每个子进程能够建立ThreadsPerChild数量的服务线程和一个监听线程，该监听线程监听接入请求并将其传递给服务线程处理和应答。  

**nginx默认是使用的fastcgi模式，可以配合fpm使用**