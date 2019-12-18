<?php
echo posix_getpid().PHP_EOL;
pcntl_async_signals( true );
// 给进程安装信号...
pcntl_signal( SIGTERM, function() {
    for( $i = 1; $i <= 10; $i++ ){
        echo $i.PHP_EOL;
        sleep( 1 );
    }
    exit;
} );
pcntl_signal( SIGINT, function() {
    for( $i = 1; $i <= 10; $i++ ){
        echo $i.PHP_EOL;
        sleep( 1 );
    }
    exit;
} );
// while保持进程不要退出..
while ( true ) {
    sleep( 1 );
}