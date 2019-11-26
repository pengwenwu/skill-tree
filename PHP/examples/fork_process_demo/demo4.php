<?php
for ( $i = 1; $i <= 100; $i++ ) {
    $i_pid = pcntl_fork();
    if ( 0 == $i_pid ) {
        file_put_contents( "./demo4.log", $i.PHP_EOL, FILE_APPEND );
        // 使用while保证子进程不会退出...
        while( true ) {
            sleep( 1 );
        }
    }
}
// 使用while保证主进程不会退出...
while( true ) {
    sleep( 1 );
}