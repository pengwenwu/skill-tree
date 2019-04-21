### binlog格式
```bash
show variables like 'binlog_format';
```
binlog有三种格式：Statement、Row以及Mixed  
- 基于SQL语句的复制(statement-based replication,SBR)   
- 基于行的复制(row-based replication,RBR)   
- 混合模式复制(mixed-based replication,MBR)  

默认是row的,修改my.cnf文件
```bash
binlog_format=mixed
```

重启
### 查看bin_log目录
```bash
show variables like '%log_bin%';
```

### 查看当前bing_log使用状态
```bash
show master status\G;
```

### binlog命令
```bash
# 查找指定时间 -d指定数据库
mysqlbinlog /var/lib/mysql/mysql-bin.000008 --start-datetime="2019-4-21 15:00:00" --stop-datetime="2019-4-21 16:00:00" -d db 

# 查找指定位置
mysqlbinlog /var/lib/mysql/mysql-bin.000008 --start-position="300" --stop-position="600" -d db

# 恢复数据
mysqlbinlog /var/lib/mysql/mysql-bin.000008 --stop-position="600" -d db | mysql -h127.0.0.1 -P3306 -uroot -p123456
```

### 通过binlog恢复数据
要想通过binlog恢复，两种方式：  
- 一种：有全部完整的binlog，然后从那个点开始执行，不然不匹配是不会执行成功的
- 二种：配置数据库备份，从某一个备份节点，数据库备份+binlog恢复数据

### 测试备份
```bash
# 全量备份
mysqldump -uroot -P3306 -p123456 --all-databases > /home/vagrant/20190421164400.sql

# 新的binlog
flush binlog;

mysqlbinlog /var/lib/mysql/mysql-bin.000010 --stop-position=1507 | mysql -h127.0.0.1 -P3306 -uroot -p123456
```