这里使用mysql5.7版本镜像
```bash
docker pull mysql:5.7
```

启动脚本  

参数含义：  
- --name：容器名
- -e MYSQL_ROOT_PASSWORD=123456：管理员密码
- -p 3306:3306：容器端口映射
- -v ~/my_docker/mysql:/var/lib/mysql：本地数据卷挂载
- -v ~/my_docker/conf/mysql/my.cnf:/etc/my.cnf：本地配置文件挂载
- -d：后台运行
- --restart=always：容器自动重启
```bash
#!bin/bash

docker run --name my-mysql \
-e MYSQL_ROOT_PASSWORD=123456 \
-p 3306:3306 \
-v ~/my_docker/mysql:/var/lib/mysql \
-v ~/my_docker/conf/mysql/my.cnf:/etc/my.cnf \
-d mysql:5.7 \
--restart=always
```