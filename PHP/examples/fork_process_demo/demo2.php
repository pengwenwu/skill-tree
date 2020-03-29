<?php
// 7次
//for ($i = 1; $i <= 3; $i++) {
//    $i_pid = pcntl_fork();
//    if (0 == $i_pid) {
//        echo "@子进程" . PHP_EOL;
//    }
//}

// 3次
for ( $i = 1; $i <= 3; $i++ ) {
    $i_pid = pcntl_fork();
    if ( 0 == $i_pid ) {
        echo "@子进程".PHP_EOL;
        exit;
    }
}
echo '@父进程'.PHP_EOL;