<?php
// 数组用于收集子进程
$a_child_pid = [];
// fork出十个子进程
for ( $i = 1; $i <= 10; $i++ ) {
    $i_pid = pcntl_fork();
    // 每个子进程随机运行1-5秒钟
    if ( 0 == $i_pid ) {
        $i_rand_time = mt_rand( 1, 5 );
        sleep( $i_rand_time );
        exit;
    }
    // 父进程收集所有子进程PID
    else if ( $i_pid > 0 ) {
        $a_child_pid[] = $i_pid;
    }
}
while( true ) {
    if ( count( $a_child_pid ) <= 0 ) {
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
            echo $i_item_pid."正常结束，最终返回：".$i_code.PHP_EOL;
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
        sleep( 1 );
    }
}