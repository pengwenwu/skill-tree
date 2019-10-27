这里直接使用redis最新镜像
```bash
docker pull redis
```

启动脚本  

配置项含义：  
- --name：容器名
- -p 6379:6379：容器端口映射
- -v ~/my_docker/redis/data:/data：本地数据卷挂载
- -v ~/my_docker/conf/redis/redis.conf:/etc/redis/redis.conf：本地配置文件挂载
- -d redis redis-server /etc/redis/redis.conf：守护运行，并使用配置文件启动容器内的redis-server
- --appendonly yes：使用持久化
- --restart=always：容器自动重启

```bash
#!bin/bash

docker run \
--name my-redis \
-p 6379:6379 \
-v ~/my_docker/redis/data:/data \
-v ~/my_docker/conf/redis/redis.conf:/etc/redis/redis.conf \
-d redis redis-server /etc/redis/redis.conf \
--appendonly yes \
--restart=always
```