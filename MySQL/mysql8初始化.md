由于之前是通过rpm安装到默认目录`/var/lib/mysql`下，后来要配置主从复制，所以需要起多个端口实例。

### 初始化
```bash
mkdir -p /usr/local/mysqldata/3306/data

mysqld --initialize --basedir=/usr/local/mysqldata/3307 --datadir=/usr/local/mysqldata/3307/data --user=mysql
```
### 默认密码查看
```bash
grep "A temporary password" /var/log/mysqld.log
```

### 复制配置文件
```bash
cp /etc/my.cnf /usr/local/mysqldata/3306
```

### 修改配置文件
```bash
[client]
port    = 3307
socket  = /usr/local/mysqldata/3307/mysql.sock

[mysqld]
user    = mysql
port    = 3307
basedir=/usr/local/mysqldata/3307
datadir=/usr/local/mysqldata/3307/data
socket=/usr/local/mysqldata/3307/mysql.sock

log-error=/usr/local/mysqldata/3307/mysql_error.log
pid-file=/usr/local/mysqldata/3307/mysqld.pid
```

### 启动
```bash
mysqld --defaults-file=/usr/local/mysqldata/3307/my.cnf --user=mysql --basedir=/usr/local/mysqldata/3307 --datadir=/usr/local/mysqldata/3307/data 2>&1 > /dev/null &
```

### 修改默认密码
```bash
mysql -h127.0.0.1 -uroot -P 3307 -p默认密码

ALTER USER 'root'@'localhost' IDENTIFIED BY '新密码';
```