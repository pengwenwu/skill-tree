## 下载源码包
```bash
cd /usr/local/src

wget http://download.redis.io/releases/redis-4.0.11.tar.gz

tar zxvf redis-4.0.11.tar.gz

cd redis-4.0.11

make && make install
```

## 启动
此时安装目录默认在`/usr/local/bin/`  

### 修改配置文件
在当前源码目录`/usr/local/src/redis-4.0.11`，修改配置文件`redis.conf`，将daemonize no修改为：  
```bash
daemonize yes
``` 

### 后台启动
```bash
redis-server ./redis.conf
```

## 关闭服务
```bash
redis-cli shutdown
```