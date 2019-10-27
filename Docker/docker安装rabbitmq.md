下载带管理后台的rabbitmq镜像
```bash
docker pull rabbitmq:management
```

启动脚本  

参数含义：  
- --name：容器名
- --hostname：容器内host名
- -p：端口映射
- -v ~/my_docker/rabbitmq/data:/var/lib/rabbitmq：数据卷挂载
- -e RABBITMQ_DEFAULT_USER=guest 默认用户名
- -e RABBITMQ_DEFAULT_PASS=guest 默认用户密码

```bash
#!bin/bash

docker run -d \
--name my-rabbitmq \
--hostname rabbitmqhost \
-p 5672:5672 \
-p 15672:15672 \
-v ~/my_docker/rabbitmq/data:/var/lib/rabbitmq \
-e RABBITMQ_DEFAULT_USER=guest \
-e RABBITMQ_DEFAULT_PASS=guest \
--restart=always \
rabbitmq:management
```