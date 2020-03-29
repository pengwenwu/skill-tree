<?php
ini_set('default_socket_timeout', -1);

$start_time = microtime(true);
$a_child_pid = [];

$o_redis = new Redis();
$o_redis->connect( '127.0.0.1', 6379 );
$o_redis->set('stock_num', 0);

$o_redis->multi();
for($i = 0; $i < 10000; $i++) {
    $o_redis->get("stock_num");
}
$o_redis->exec();
echo '父进程执行结束时间：'.(microtime(true) - $start_time)."\n";
exit( "所有进程均已终止".PHP_EOL );

// 使用for循环搞出10个子进程来
for ( $i = 1; $i <= 10; $i++ ) {
    $i_pid = pcntl_fork();
    if ( 0 == $i_pid ) {
        $o_redis->connect( '127.0.0.1', 6379 );
        // 每个子进程循环自增1000
        for ($j = 0; $j < 1000; $j++) {
            $o_redis->incr("stock_num");
        }
        echo '子进程' . posix_getpid() . '执行结束时间:' . (microtime(true) - $start_time) . PHP_EOL;
        exit;
    } elseif ($i_pid > 0) {
        $a_child_pid[] = $i_pid;
    }
}
while( true ) {
    if ( count( $a_child_pid ) <= 0 ) {
        echo '父进程执行结束时间：'.(microtime(true) - $start_time)."\n";
        exit( "所有进程均已终止".PHP_EOL );
    }
    foreach( $a_child_pid as $i_item_key => $i_item_pid ) {
        $i_wait_ret = pcntl_waitpid( $i_item_pid, $i_status, WNOHANG | WUNTRACED | WCONTINUED );
        if ( -1 == $i_wait_ret || $i_wait_ret > 0 ) {
            unset( $a_child_pid[ $i_item_key ] );
        }
        // 如果子进程是正常结束
        if ( pcntl_wifexited( $i_status ) ) {
            // 获取子进程结束时候的 返回错误码
            $i_code = pcntl_wexitstatus( $i_status );
//            echo $i_item_pid."正常结束，最终返回：".$i_code.PHP_EOL;
        }
        // 如果子进程是被信号终止
        if ( pcntl_wifsignaled( $i_status ) ) {
            // 获取是哪个信号终止的该进程
            $i_signal = pcntl_wtermsig( $i_status );
            echo $i_item_pid."由信号结束，信号为：".$i_signal.PHP_EOL;
        }
        // 如果子进程是[临时挂起]
        if ( pcntl_wifstopped( $i_status ) ) {
            // 获取是哪个信号让他挂起
            $i_signal = pcntl_wstopsig( $i_status );
            echo $i_item_pid."被挂起，挂起信号为：".$i_signal.PHP_EOL;
        }
        // sleep使父进程不会因while导致CPU爆炸.
//        sleep( 1 );
    }
}
exit;
while (true) {
    if ( count( $a_child_pid ) <= 0 ) {
        echo '父进程执行结束时间：'.(microtime(true) - $start_time)."\n";
        exit( "所有进程均已终止".PHP_EOL );
    }
}
//while( true ) {
//    $i_ret = pcntl_waitpid( 0, $status, WNOHANG | WUNTRACED );
//    $ret = pcntl_wifstopped( $status );
//    echo "是否停止:".json_encode( $ret ).PHP_EOL;
//    sleep( 1 );
//}
