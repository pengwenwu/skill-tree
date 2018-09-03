# 介绍  
`laradock`是一个为php配置的完整的docker环境，可以通过修改配置文件，完成对不同版本、不同扩展、不同框架的php docker环境配置。从此对虚拟机说再见  

# 使用
## clone到项目的同级目录  
或者使用`Git 子模块`  
```bash
git clone https://github.com/Laradock/laradock.git laradock
```
这里使用CodeIgniter框架举个例子，目录结构是这样  
```bash
|--- CodeIgniter
|--- laradock
```

## 复制配置文件
`.env` 才是配置文件
```bash
# 进入laradockm目录
cd laradock
cp env-example .env
```

## 增加配置参数
如果需要使用mysql，redis等，`.env`需要新增配置HOST地址  
```bash
# .env 末尾新增
DB_HOST=mysql
REDIS_HOST=redis
QUEUE_HOST=rabbitmq
```

## 修改nginx默认挂在目录
```bash
vim laradock/nginx/sites/default.conf
```
修改`default.conf`配置文件
```bash
# 原配置
# root /var/www/public
root /var/www/CodeIgniter
```


## 启动容器
这里有个坑，由于最近更新了mysql8，而laradoc的mysql中的Dockerfile默认是from latest拉去最新镜像，会导致**mysql无法后台运行**，秒退，应该是旧配置挂载问题  
```bash
docker-compose up -d nginx mysql phpmyadmin redis workspace
```
这里需要重新构建mysql
```bash
docker-compose build --build-arg MYSQL_VERSION=5.7 mysql

# 构建完，重启容器
docker-compose up -d mysql
```

## 查看
可以通过`docker-compose ps`查看各个容器运行状态，以及暴露的端口  
```bash
        Name Command State Ports
--------------------------------------------------------------------------------------------------------
laradoc_mysql_1 docker-entrypoint.sh mysqld Up 0.0.0.0:3306->3306/tcp
laradoc_nginx_1 nginx Up 0.0.0.0:443->443/tcp, 0.0.0.0:80->80/tcp
laradoc_php-fpm_1 docker-php-entrypoint php-fpm Up 9000/tcp
laradoc_phpmyadmin_1 /run.sh phpmyadmin Up 0.0.0.0:8080->80/tcp, 9000/tcp
laradoc_redis_1 docker-entrypoint.sh redis ... Up 0.0.0.0:6379->6379/tcp
laradoc_workspace_1 /sbin/my_init Up 0.0.0.0:2222->22/tcp
```
这里看到，nginx默认80端口，打开浏览器，输入localhost即可访问，正常返回的是CI框架的默认首页  
>Welcome to CodeIgniter!  

phpmyadmin的端口是8080，浏览器输入localhost:8080，则可以访问phpmyadmin。服务器地址是之前配置的 DB_HOST = `mysql`，账号密码可以查看`.env`配置文件，默认是`root` `root`，这些都是可以在启动命令的参数里设置

参考链接：[《Laradock》](http://laradock.io/)
