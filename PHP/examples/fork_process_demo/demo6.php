<?php
// pcntl_wait — 等待或返回fork的子进程状态，默认阻塞，可以通过第二个参数option传递WNOHANG或WUNTRACED 让程序不会阻塞
//$i_pid = pcntl_fork();
//if ( 0 == $i_pid ) {
//    // 在子进程中
//    for( $i = 1; $i <= 10; $i++ ) {
//        sleep( 1 );
//        echo "子进程PID ".posix_getpid()."倒计时 : ".$i.PHP_EOL;
//    }
//}
//else if ( $i_pid > 0 ) {
//    $i_ret = pcntl_wait( $status, WNOHANG );
//    echo $i_ret.' : '.$status.PHP_EOL;
//    // while保持父进程不退出
//    while ( true ) {
//        sleep( 1 );
//    }
//}

/**
 *
pcntl_wexitstatus：此函数可检测进程退出时的错误码，在*NIX里进程退出时默认错误码是0，诸君亦可返其他任意数值，诸如exit( 250 )，此君可根据$status获取子进程退出时的错误码

pcntl_wifexited：此君根据$status判断子进程是否正常退出。APUE曾有记载进程完成自然生命周期亦或exit()均可视之为正常退出，被abort亦或终止于[ 信号 ]（signal）

pcntl_wifsignaled：此君较之前者，则用之于检查子进程是否因信号而中断

pcntl_wifstopped：此君用于检测子进程是否已停止（注意停止不是终止，诸君要理解为临时挂起），然需使用了WUNTRACED作为$option的pcntl_waitpid()函数调用产生的status时才有效

pcntl_wstopsig：此君则依赖前者，即仅在pcntl_wifstopped()返回 TRUE 时有效

pcntl_wtermsig：此君依赖于pcntl_wifsignaled()为ture时检测子进程因何种信号[ signal ]而终止
 */
//$i_pid = pcntl_fork();
//if ( 0 == $i_pid ) {
//    for( $i = 1; $i <= 3; $i++ ) {
//        echo "子进程running".PHP_EOL;
//        sleep( 1 );
//    }
//    exit( 11 );
//}
//echo '父进程block在此'.PHP_EOL;
//$i_ret = pcntl_wait( $status, WUNTRACED );
//echo $i_ret." : ".$status.PHP_EOL;
//$ret = pcntl_wexitstatus( $status );
//var_dump( $ret );
//$ret = pcntl_wifexited( $status );
//var_dump( $ret );
//$ret = pcntl_wifsignaled( $status );
//var_dump( $ret );
//$ret = pcntl_wifstopped( $status );
//var_dump( $ret );

$i_pid = pcntl_fork();
if ( 0 == $i_pid ) {
    for( $i = 1; $i <= 300; $i++ ) {
        echo "child alive".PHP_EOL;
        sleep( 1 );
    }
    exit;
}

while( true ) {
    $i_ret = pcntl_waitpid( 0, $status, WNOHANG | WUNTRACED );
    $ret = pcntl_wifstopped( $status );
    echo "是否停止:".json_encode( $ret ).PHP_EOL;
    sleep( 1 );
}