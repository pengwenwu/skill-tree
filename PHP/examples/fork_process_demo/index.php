<?php
// pcntl_fork()的返回是进程号PID
// 子进程里返回的PID = 0（因为子进程可以通过posix_getpid()获取自己的进程号，posix_getppid()获取父进程PID）
// 父进程里返回的PID为子进程的PID
$s_slogan = "Hello, I'm from ";
$i_pid  = pcntl_fork();

// 子进程
if ($i_pid == 0) {
    $s_slogan .= "child process";
    echo $s_slogan . " | 子进程PID：" . posix_getpid() . " | 父进程PID：" . posix_getppid() . PHP_EOL;
} elseif ($i_pid > 0) {
    $s_slogan .= "father process";
    echo $s_slogan . " | 子进程PID：" . $i_pid . " | 当前进程PID：" . posix_getpid() . PHP_EOL;
} else {
    throw new Exception("Exception:pcntl_fork err");
}