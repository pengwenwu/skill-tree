## 安装erlang
github地址：https://github.com/rabbitmq/erlang-rpm  

选择不同的版本  

### 添加仓库
比如在CentOS 7上使用Erlang 21.x：    
```bash
vim /etc/yum.repos.d/rabbitmq-erlang.repo

[rabbitmq-erlang]
name = rabbitmq-erlang
baseurl = https://dl.bintray.com/rabbitmq/rpm/erlang/21/el/7
gpgcheck = 1
gpgkey = https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc
repo_gpgcheck = 0
enabled = 1
```

### yum安装
```bash
yum install erlang -y
```

## 安装RabbitMq
官网下载地址：http://www.rabbitmq.com/download.html  

选择合适的版本，这里以centos为例：  
```bash
wget https://dl.bintray.com/rabbitmq/all/rabbitmq-server/3.7.7/rabbitmq-server-3.7.7-1.el7.noarch.rpm

yum install rabbitmq-server-3.7.7-1.el7.noarch.rpm
```

### rpm查看安装目录
- rpm查看安装包  
    ```bash
    rpm -qa | grep rabbitmq
    ```
- rpm查看安装路径  
    这里是`小写l`，加上刚刚查出的安装包名  
    ```bash
    rpm -ql rabbitmq-server-3.7.7-1.el7.noarch
    ```

## 启动rabbitmq-server
```bash
#将rabbitmq-server加入到开机自启动服务
systemctl enable rabbitmq-server.service
chkconfig rabbitmq-server on

#启动
service rabbitmq-server start
```

## 查看rabbitmq运行状态
```bash
systemctl status rabbitmq-server
```

## 查看rabbitmq状态
```bash
rabbitmqctl status
```

## 查看rabbitmq默认配置
```bash
rabbitmqctl environment
```

## 开启web监控
运行`rabbitmq-server`后，如果出现`completed with 0 plugins.`，则说明未开启监控。  

此时需要执行命令: `rabbitmq-plugins enable rabbitmq_management`  
再次运行rabbitmq-server  

```bash
service rabbitmq-server start #启动

service rabbitmq-server stop #停止

service rabbitmq-server restart #重启

service rabbitmq-server status #查看状态

service rabbitmq-server etc #查看有哪些命令可以使用
```