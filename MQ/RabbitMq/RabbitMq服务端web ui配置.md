## 应用插件
```bash
rabbitmq-plugins enable rabbitmq_management
```

## 访问
http://localhost:15672

## 虚拟机端口映射无法访问
### RabbitMQ中的访问控制（身份验证，授权）
> 官方文档：https://www.rabbitmq.com/access-control.html  

默认用户为`guest`，密码为`guest`。通过修改配置文件或者以下命令添加新用户：  
```bash
# 添加新用户
rabbitmqctl add_user root 123456
# 设置用户标签
rabbitmqctl set_user_tags root administrator
```

## 用户管理
> 更多用户管理命令，官方文档：https://www.rabbitmq.com/rabbitmqctl.8.html#User_Management