### 删除yum安装过的php相关模块
```bash
yum remove php*
```

### 依赖安装
```bash
yum -y install gcc automake autoconf libtool make

yum -y install gcc gcc-c++ glibc

yum -y install libmcrypt-devel mhash-devel libxslt-devel \
libjpeg libjpeg-devel libpng libpng-devel freetype freetype-devel libxml2 libxml2-devel \
zlib zlib-devel glibc glibc-devel glib2 glib2-devel bzip2 bzip2-devel \
ncurses ncurses-devel curl curl-devel e2fsprogs e2fsprogs-devel \
krb5 krb5-devel libidn libidn-devel openssl openssl-devel
```

### 编译安装
```bash
wget http://cn2.php.net/distributions/php-7.2.8.tar.gz
tar zvxf php-7.2.8.tar.gz
cd php-7.2.8

./configure --prefix=/usr/local/php7 \
--with-config-file-path=/usr/local/php7/etc \
--with-config-file-scan-dir=/usr/local/php7/etc/php.d \
--enable-mysqlnd \
--with-mysqli \
--with-pdo-mysql \
--enable-fpm \
--with-fpm-user=nginx \
--with-fpm-group=nginx \
--with-gd \
--with-iconv \
--with-zlib \
--enable-xml \
--enable-shmop \
--enable-sysvsem \
--enable-inline-optimization \
--enable-mbregex \
--enable-mbstring \
--enable-ftp \
--with-openssl \
--enable-pcntl \
--enable-sockets \
--with-xmlrpc \
--enable-zip \
--enable-soap \
--without-pear \
--with-gettext \
--enable-session \
--with-curl \
--with-jpeg-dir \
--with-freetype-dir \
--enable-opcache

make && make install
```

### 配置php
#### 添加php安装目录到系统环境变量
```bash
vim /etc/profile.d/php.sh

export PATH=$PATH:/usr/local/php/bin/:/usr/local/php/sbin/

source /etc/profile.d/php.sh
```

#### 添加php配置文件
```bash
cp php.ini-production /usr/local/php/etc/php.ini
```

#### 修改服务器所在时区
```bash
vim /usr/local/php/etc/php.ini

date.timezone = PRC
```

#### 添加 php-fpm 配置文件
```bash
cd /usr/local/php/etc

cp php-fpm.conf.default php-fpm.conf
```

#### 添加 php-fpm 管理相关的配置文件到系统配置目录 /etc/init.d
```bash
# /usr/local/php-7.2.8
cp sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
```

#### 添加 www.conf 配置文件
```bash
# /usr/local/php
cp etc/php-fpm.d/www.conf.default etc/php-fpm.d/www.conf
```

#### 设置PHP日志目录和php-fpm运行进程的ID文件目录
php-fpm运行进程的ID文件也就是 `php-fpm.pid`  

设置php-fpm进程目录的用户和用户组为nginx  

> -r: 添加系统用户( 这里指将要被创建的系统用户nginx )  
> -g: 指定要创建的用户所属组( 这里指添加到新系统用户nginx到nginx系统用户组 )  
> -s: 新帐户的登录shell( `/sbin/nologin` 这里设置为将要被创建系统用户nginx不能用来登录系统 )  
> -d: 新帐户的主目录( 这里指定将要被创建的系统用户nginx的家目录为 `/usr/local/nginx` )  
> -M: 不要创建用户的主目录( 也就是说将要被创建的系统用户nginx不会在 `/home` 目录下创建 `nginx` 家目录 )

```bash
# 添加系统用户组nginx
groupadd -r nginx

# 创建新的系统用户nginx, 并添加到系统用户组nginx, 设置不允许此用户名登录shell (如果你没有创建过系统用户nginx请创建)
useradd -r -g nginx -s /sbin/nologin -d /usr/local/nginx -M nginx

# 创建 `php-fpm` 日志目录
mkdir -p /var/log/php-fpm/

# 创建 `php-fpm` 进程的ID(php-fpm.pid)文件运行目录
mkdir -p /var/run/php-fpm

# 修改 `php-fpm` 进程的ID(php-fpm.pid)文件运行目录的所属用户和组
chown -R nginx:nginx /var/run/php-fpm/
```

#### 设置php开机启动
```bash
# 修改系统配置目录下的 `php-fpm` 文件可执行权限
chmod +x /etc/init.d/php-fpm

# 将系统配置目录下的 `php-fpm` 添加到 `系统服务`
chkconfig --add php-fpm

# 设置 `php-fpm` `系统服务` 为开机启动
chkconfig php-fpm on
```

#### 检测 php-fpm 系统服务是否启动成功
用 chkconfig 命令检测一下服务是否运行成功
```bash
chkconfig --list | grep php-fpm

Note: This output shows SysV services only and does not include native
      systemd services. SysV configuration data might be overridden by native
      systemd configuration.

      If you want to list systemd services use 'systemctl list-unit-files'.
      To see services enabled on particular target use
      'systemctl list-dependencies [target]'.

php-fpm 0:off 1:off 2:on 3:on 4:on 5:on 6:off

# 可见服务已经在 第2 到 第5 运行等级打开

# 禁用 `php-fpm` 开机启动
chkconfig php-fpm off
```

#### 测试PHP的配置文件是否无误
```bash
php-fpm -t

[22-Jul-2018 11:13:23] NOTICE: configuration file /usr/local/php/etc/php-fpm.conf test is successful

# 出现上面的提示也就是测试配置文件通过没有问题, 可以正式使用php服务了
```

#### 启动php系统服务
使用 `systemctl start` + `服务名` 启动系统服务  
```bash
systemctl start php-fpm.service
```

#### 查看php系统服务状态
使用 `systemctl status` + `服务名` 查看系统服务状态
```bash
systemctl status php-fpm.service

● php-fpm.service - LSB: starts php-fpm
   Loaded: loaded (/etc/rc.d/init.d/php-fpm; bad; vendor preset: disabled)
   Active: active (running) since Sun 2018-07-22 11:15:47 UTC; 29s ago
     Docs: man:systemd-sysv-generator(8)
  Process: 13309 ExecStart=/etc/rc.d/init.d/php-fpm start (code=exited, status=0/SUCCESS)
    Tasks: 3
   Memory: 3.2M
   CGroup: /system.slice/php-fpm.service
           ├─13311 php-fpm: master process (/usr/local/php/etc/php-fpm.conf)
           ├─13312 php-fpm: pool www
           └─13313 php-fpm: pool www

Jul 22 11:15:47 localhost.localdomain systemd[1]: Starting LSB: starts php-fpm...
Jul 22 11:15:47 localhost.localdomain systemd[1]: Started LSB: starts php-fpm.
Jul 22 11:15:47 localhost.localdomain php-fpm[13309]: Starting php-fpm done
```

### 安装nginx
```bash
cd /usr/local/src

# 依赖安装
yum install -y pcre pcre-devel
yum install -y openssl openssl-devel

# 下载
wget https://nginx.org/download/nginx-1.14.0.tar.gz
tar -zxvf nginx-1.14.0.tar.gz
cd nginx-1.14.0/

# 编译
./configure --prefix=/usr/local/nginx --sbin-path=/usr/local/nginx/nginx --conf-path=/usr/local/nginx/nginx.conf --pid-path=/usr/local/nginx/nginx.pid --with-http_ssl_module --with-http_realip_module --with-http_sub_module --with-http_gzip_static_module --with-http_stub_status_module --with-pcre --with-cc-opt="-Wno-deprecated-declarations"

make && make install
```

#### 配置环境变量
```bash
echo 'export PATH=$PATH:/usr/local/nginx/nginx' > /etc/profile.d/nginx.sh

cp /usr/local/nginx/nginx /usr/bin
```

#### 常用nginx命令
- 启动  
    nginx
- nginx -s stop
- nginx -s quit
- nginx -s reload
- nginx -s quit:此方式停止步骤是待nginx进程处理任务完毕进行停止
- nginx -s stop:此方式相当于先查出nginx进程id再使用kill命令强制杀掉进程

#### 开机启动
```bash
vim /etc/init.d/nginx  
```
粘贴以下命令并修改以下目录：  
```bash
nginx="/usr/local/nginx/nginx"  
NGINX_CONF_FILE="/usr/local/nginx/nginx.conf"
```
```bash
#!/bin/sh
#
# nginx - this script starts and stops the nginx daemin
#
# chkconfig: - 85 15 
# description: Nginx is an HTTP(S) server, HTTP(S) reverse \
# proxy and IMAP/POP3 proxy server
# processname: nginx
# config: /etc/nginx/nginx.conf
# pidfile: /run/nginx/nginx.pid
# Source function library.
. /etc/rc.d/init.d/functions

# Source networking configuration.
. /etc/sysconfig/network

# Check that networking is up.
[ "$NETWORKING" = "no" ] && exit 0

nginx="/usr/sbin/nginx"

prog=$(basename $nginx)

NGINX_CONF_FILE="/etc/nginx/nginx.conf"

lockfile=/var/lock/nginx.lock

start() {
    [ -x $nginx ] || exit 5
    [ -f $NGINX_CONF_FILE ] || exit 6
    echo -n $"Starting $prog: "
    daemon $nginx -c $NGINX_CONF_FILE
    retval=$?
    echo
    [ $retval -eq 0 ] && touch $lockfile
    return $retval
}

stop() {
    echo -n $"Stopping $prog: "
    killproc $prog -QUIT
    retval=$?
    echo
    [ $retval -eq 0 ] && rm -f $lockfile
    return $retval
}

restart() {
    configtest || return $?
    stop
    start
}

reload() {
    configtest || return $?
    echo -n $"Reloading $prog: "
    killproc $nginx -HUP
    RETVAL=$?
    echo
}

force_reload() {
    restart
}

configtest() {
  $nginx -t -c $NGINX_CONF_FILE
}

rh_status() {
    status $prog
}

rh_status_q() {
    rh_status >/dev/null 2>&1
}

case "$1" in
    start)
        rh_status_q && exit 0
        $1
        ;;
    stop)
        rh_status_q || exit 0
        $1
        ;;
    restart|configtest)
        $1
        ;;
    reload)
        rh_status_q || exit 7
        $1
        ;;
    force-reload)
        force_reload
        ;;
    status)
        rh_status
        ;;
    condrestart|try-restart)
        rh_status_q || exit 0
            ;;
    *)
        echo $"Usage: $0 {start|stop|status|restart|condrestart|try-restart|reload|force-reload|configtest}"
        exit 2
esac

exit $RETVAL
```

- 添加到服务
  ```bash
  chmod a+x /etc/init.d/nginx
  chkconfig --add nginx
  ```

- 使用
  ```bash
  service nginx start  
  service nginx stop  
  service nginx restart  
  service nginx reload
  ```
