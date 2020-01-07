<?php
// create-bind-listen-accept
$host = '0.0.0.0';
$port = 9999;
// 第一个参数有AF_INET、AF_INET6、AF_UNIX三种，其实分别就是IPv4、IPv6、文件sock的意思
// 第二个参数有SOCK_STREAM、SOCK_DGRAM、SOCK_RAW等五种，这三种比较常见
// SOCK_STREAM就是流式面向连接的可靠协议，TCP就是基于SOCK_STREAM
// SOCK_DGRAM就是数据包、无连接的不可靠协议，UDP基于SOCK_DGRAM
// SOCK_RAW就是最粗暴原始的那种，你要完全手工来控制，你可以做成面向连接
// 第三个参数共有两个值SOL_TCP、SOL_UDP
// 这里提醒一下就是，后两个参数的选择是有关联性的，比如第二个参你用了
// SOCK_STREAM，那么第三个参数记得用SOL_TCP
$listen_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// 将$listen_socket协议捆绑到以$host&$port指定的socket上
socket_bind($listen_socket, $host, $port);
// 开始监听socket
socket_listen($listen_socket);
// 阻塞监听
while (true) {
    // 阻塞等待客户端连接
    $connect_socket = socket_accept($listen_socket);
    // 从客户端读取消息
    $content = socket_read($connect_socket, 4096);
    echo '从客户端获取：' . $content . PHP_EOL;

    // 向客户端发送一个消息
    $msg = 'Hello World' . "\r\n";
    socket_write($connect_socket, $msg, strlen($msg));
    socket_close($connect_socket);
}
// 关闭服务端连接
socket_close($listen_socket);