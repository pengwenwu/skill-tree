<?php
/**
 * Created by PhpStorm.
 * User: pengwenwu
 * Date: 2019/11/27
 * Time: 14:47
 */
// 僵尸进程：子进程完成其生命周期后，父进程任之不管不顾，子进程残留数据诸如PID、持有的资源等，久而久之则危害操作系统。在*NIX系统中，僵尸进程常有[Z+]标志符。
// 孤儿进程：子进程尚未完成生命周期，父进程已提前完成生命周期，此子进程则为孤儿进程，可[ 望文生义 ]。孤儿进程一旦形成，则自动由系统头号进程init来完成收养。孤儿进程实属常见，见之不必惊慌。

// 孤儿进程
//$i_pid = pcntl_fork();
//if (0 == $i_pid) {
//    // 子进程10秒钟后退出
//    for ($i = 1; $i <= 10; $i++) {
//        sleep(1);
//        echo "我的父进程是：" . posix_getppid() . PHP_EOL;
//    }
//} elseif ($i_pid > 0) {
//    // 父进程休眠2秒后退出
//    sleep(2);
//}

// 僵尸进程
/*
 子进程在10s后退出，退出后父进程依然还在运行中
 但是父进程尚未做任何工作
 所以按照定义，子进程将会成为僵尸进程.
 */
//$i_pid = pcntl_fork();
//if ( 0 == $i_pid ) {
//    // 子进程10s后退出.
//    sleep( 10 );
//}
//else if ( $i_pid > 0 ) {
//    // 父进程休眠1000s后退出.
//    sleep( 1000 );
//}

// 通过pcntl_wait() 和 pcntl_waitpid() 来解决僵尸进程问题
$i_pid = pcntl_fork();
if ($i_pid == 0) {
    // 子进程
    for ($i = 1; $i <= 10; $i++) {
        sleep(1);
        echo '子进程PID' . posix_getpid() . "倒计时 ：" . $i . PHP_EOL;
    }
} elseif ($i_pid > 0) {
    $i_ret = pcntl_wait($status);
    echo $i_ret . ' : ' . $status . PHP_EOL;
    while (true) {
        sleep(1);
    }
}