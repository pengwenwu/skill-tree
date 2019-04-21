### 主库配置
```bash
vim /etc/my.conf

# 在[mysqld]中添加
#主数据库端ID号
server_id = 1           
 #开启二进制日志                  
log-bin = mysql-bin    
#需要复制的数据库名，如果复制多个数据库，重复设置这个选项即可                  
binlog-do-db = db        
#将从服务器从主服务器收到的更新记入到从服务器自己的二进制日志文件中                 
log-slave-updates                        
#控制binlog的写入频率。每执行多少次事务写入一次(这个参数性能消耗很大，但可减小MySQL崩溃造成的损失) 
sync_binlog = 1                    
#这个参数一般用在主主同步中，用来错开自增值, 防止键值冲突
auto_increment_offset = 1           
#这个参数一般用在主主同步中，用来错开自增值, 防止键值冲突
auto_increment_increment = 1            
#二进制日志自动删除的天数，默认值为0,表示“没有自动删除”，启动时和二进制日志循环时可能删除  
expire_logs_days = 7                    
#将函数复制到slave  
log_bin_trust_function_creators = 1  
```

#### 重启mysql

#### 创建同步数据账户
修改账户安全等级mysql8
```bash
SHOW VARIABLES LIKE 'validate_password%';
set global validate_password.length=4;
set global validate_password.policy=0;
```

```bash
# 新增账户
CREATE USER 'account'@'%' IDENTIFIED WITH mysql_native_password BY '123456';
# 调整权限
GRANT REPLICATION SLAVE ON *.* TO 'account'@'%';
```

#### 查看主服务器状态
```bash
show master status\G;

*************************** 1. row ***************************
             File: mysql-bin.000002
         Position: 660
     Binlog_Do_DB: user
 Binlog_Ignore_DB:
Executed_Gtid_Set:
1 row in set (0.01 sec)
```

### 从库配置
```bash
vim /usr/local/mysqldata/3307/my.cnf

# 在[mysqld]中添加
server_id = 2
log-bin = mysql-bin
log-slave-updates
sync_binlog = 0
#log buffer将每秒一次地写入log file中，并且log file的flush(刷到磁盘)操作同时进行。该模式下在事务提交的时候，不会主动触发写入磁盘的操作
innodb_flush_log_at_trx_commit = 0        
#指定slave要复制哪个库
replicate-do-db = db         
#MySQL主从复制的时候，当Master和Slave之间的网络中断，但是Master和Slave无法察觉的情况下（比如防火墙或者路由问题）。Slave会等待slave_net_timeout设置的秒数后，才能认为网络出现故障，然后才会重连并且追赶这段时间主库的数据
slave-net-timeout = 60                    
log_bin_trust_function_creators = 1
```

#### 重启

#### 执行同步命令
```bash
# 执行同步命令，设置主服务器ip，同步账号密码，同步位置
mysql>change master to master_host='127.0.0.1',master_user='account',master_password='123456',master_log_file='mysql-bin.000002',master_log_pos=660;
# 开启同步功能
mysql>start slave;
# 查看服务器状态
show slave status\G;
*************************** 1. row ***************************
               Slave_IO_State: Waiting for master to send event
                  Master_Host: 127.0.0.1
                  Master_User: account
                  Master_Port: 3306
                Connect_Retry: 60
              Master_Log_File: mysql-bin.000002
          Read_Master_Log_Pos: 660
               Relay_Log_File: localhost-relay-bin.000002
                Relay_Log_Pos: 322
        Relay_Master_Log_File: mysql-bin.000002
             Slave_IO_Running: Yes
            Slave_SQL_Running: Yes
              Replicate_Do_DB: user
          Replicate_Ignore_DB:
           Replicate_Do_Table:
           ...
```

> 参考文档：https://www.jianshu.com/p/b0cf461451fb