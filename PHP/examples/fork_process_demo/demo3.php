<?php
ini_set('default_socket_timeout', -1);
$o_redis = new Redis();
//$o_redis->connect( '127.0.0.1', 6379 );

// 使用for循环搞出3个子进程来
for ( $i = 1; $i <= 4; $i++ ) {
    $i_pid = pcntl_fork();
    if ( 0 == $i_pid ) {
        // 每个子进程创建一个连接
        $o_redis->connect( '127.0.0.1', 6379 );
        $b_ret = $o_redis->sIsMember("uid", $i);
        echo $i . ':' . json_encode($b_ret) . PHP_EOL;

        // 使用while保证三个子进程不会退出...
        while( true ) {
            sleep( 1 );
        }
    }
}
// 使用while保证主进程不会退出...
while( true ) {
    sleep( 1 );
}