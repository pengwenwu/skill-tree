1. 首先先确定是否相应的消费者
![](http://pic.pwwtest.com/20201229140416.png)
2. 3.14上有多个容器，如何确定是哪个容器运行的呢？
  - 由于在linux的docker中，容器里运行的进程会直接通过宿主机执行，所以可以通过查看是否有对应进程
  ```bash
  ps aux | grep cront/trades/Cashier_trade/payed
  ```
  ![](http://pic.pwwtest.com/20201229140729.png)
  - 获取对应进程的父级pid（也就是运行该脚本的pm2进程），比如这里选择第一个进程的pid为2163，获取到上级pid为29217
  ```bash
  # ps -ef|awk '$2 ~ /pid/{print $3}'
  ps -ef|awk '$2 ~ /2163/{print $3}'
  ```
  ![](http://pic.pwwtest.com/20201229141158.png)
  - 再获取pm2的上级（即运行容器的pid），获取到为24450
  ```bash
  ps -ef|awk '$2 ~ /29217/{print $3}'
  ```
  - 查看容器pid相对应的进程
  ```bash
  ps aux | grep 24450
  ```
  ![](http://pic.pwwtest.com/20201229160017.png)
  - 有一串容器编号，复制前一小段，查询对应容器
  ```bash
  docker ps | grep 0d7eccf5ab
  ```
  ![](http://pic.pwwtest.com/20201229160347.png)