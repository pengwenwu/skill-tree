```bash
#!bin/bash
# 一键安装lnmp环境脚本

# 设置path
dir=/usr/local/src
nginx_download_path="https://nginx.org/download/nginx-1.14.0.tar.gz"
nginx_name="nginx-1.14.0.tar.gz"
nginx_version_name="nginx-1.14.0"
mysql_download_path="https://dev.mysql.com/get/Downloads/MySQL-5.6/mysql-5.6.43.tar.gz"
mysql_name="mysql-5.6.43.tar.gz"
mysql_version_name="mysql-5.6.43"
php_download_path="http://cn2.php.net/distributions/php-7.2.8.tar.gz"
php_name="php-7.2.8.tar.gz"
php_version_name="php-7.2.8"

# 安装前准备
initInstall() {
    # 删除旧配置
    rpm -qa |  grep http || echo " no httpd " && yum -y remove http* >/dev/null && echo "http ok"
    rpm -qa |  grep nginx || echo " no nginx " && yum -y remove nginx* >/dev/null && echo "nginx ok"
    rpm -qa |  grep php || echo " no php " && yum -y remove php* >/dev/null && echo "php ok"
    rpm -qa |  grep mysql || echo " no mysql " && yum -y remove mysql* >/dev/null && echo "mysql ok"

    # 关闭selinux
    if [ -s /etc/selinux/config ]; then
    sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
    fi

     yum -y install wget gcc pcre pcre-devel gcc-c++ autoconf libxml2 libxml2-devel zlib zlib-devel glibc libjpeg libjpeg-devel libpng libpng-devel glibc-devel glib2 glib2-devel ncurses ncurses-devel curl curl-devel e2fsprogs e2fsprogs-devel openssl openssl-devel openldap openldap-devel  openldap-clients openldap-servers make cmake freetype-devel libmcrypt libmcrypt-devel libxslt-devel
}

# 下载需要的软件
downLoad() {
    cd $dir
    wget -c $php_download_path
    wget -c $nginx_download_path
    wget -c $mysql_download_path
}

# 安装nginx
installNginx() {
    cd $dir
    tar zxvf $nginx_name
    cd $nginx_version_name
    useradd nginx -s /sbin/nologin -M
    ./configure --prefix=/usr/local/nginx --sbin-path=/usr/local/nginx/nginx --conf-path=/usr/local/nginx/nginx.conf --pid-path=/usr/local/nginx/nginx.pid --with-http_ssl_module --with-http_realip_module --with-http_sub_module --with-http_gzip_static_module --with-http_stub_status_module --with-pcre --with-cc-opt="-Wno-deprecated-declarations"
    [ $(echo $?) -eq 0 ] && make && make install
    echo 'export PATH=$PATH:/usr/local/nginx/nginx' > /etc/profile.d/nginx.sh
    cp /usr/local/nginx/nginx /usr/bin
    [ $(echo $?) -eq 0 ] && echo "nginx install success"
}

startNginx() {
    nginx -t
}

# 安装mysql
installMysql() {
    cd $dir
    tar zxvf $mysql_name
    mv $mysql_version_name /usr/local/mysql
    useradd mysql -s /sbin/nologin -M
    chown -R mysql.mysql /usr/local/mysql
    cd /usr/local/mysql
    mkdir data && mkdir log
    echo "export PATH=$PATH:/usr/local/mysql/bin" >> /etc/profile
    source /etc/profile
    groupadd mysql && useradd -r -g mysql -s /bin/false mysql
    cmake . -DCMAKE_INSTALL_PREFIX=/usr/local/mysql \
-DDEFAULT_CHARSET=utf8 \
-DDEFAULT_COLLATION=utf8_general_ci \
-DENABLED_LOCAL_INFILE=ON \
-DWITH_INNOBASE_STORAGE_ENGINE=1 \
-DWITH_FEDERATED_STORAGE_ENGINE=1 \
-DWITH_BLACKHOLE_STORAGE_ENGINE=1 \
-DWITHOUT_EXAMPLE_STORAGE_ENGINE=1 \
-DWITH_PERFSCHEMA_STORAGE_ENGINE=1 \
-DCOMPILATION_COMMENT='JSS for mysqltest' \
-DWITH_READLINE=ON \
-DSYSCONFDIR=/data/mysqldata/3306 \
-DMYSQL_UNIX_ADDR=/data/mysqldata/3306/mysql.sock
    make && make install
    chown -R mysql:mysql .
    chown -R mysql:mysql data
    chmod +x -R /usr/local/mysql

    cat << EOF > /etc/my.cnf
[client]      
socket=/usr/local/mysql/mysql.sock      
[mysqld]      
basedir=/usr/local/mysql      
datadir=/usr/local/mysql/data      
pid-file=/usr/local/mysql/data/mysqld.pid      
socket=/usr/local/mysql/mysql.sock      
log_error=/usr/local/mysql/log/mysqld.log
EOF
    if [ -f /etc/my.cnf ];
       then
	    ./scripts/mysql_install_db --user=mysql --datadir=/usr/local/mysql/data --socket=/usr/local/mysql/mysql.sock --pid-file=/usr/local/mysql/data/mysqld.pid --log-error=/usr/local/mysql/log/mysqld.log --basedir=/usr/local/mysql
       else
           echo "MySQL安装失败！！！！"
           exit 1
    fi
    cp /usr/local/mysql/support-files/mysql.server /etc/init.d/mysqld
    chmod +x /etc/init.d/mysqld
}

startMysql() {
    /etc/init.d/mysqld start
    if [ $(netstat -lutnp|grep 3306|wc -l) -eq 1 ]     
        then       
            echo "mysql starting success..."  /bin/true   
        else       
            echo "mysql starting fail,plaese check the service!"   
    fi
}

installPhp() {
    cd $dir
    tar zxvf $php_name
    cd $php_version_name
    ./configure --prefix=/usr/local/php \
	--with-config-file-path=/usr/local/php/etc \
	--with-config-file-scan-dir=/usr/local/php/etc/php.d \
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
    
    cp php.ini-development /etc/php.ini
    cp /usr/local/php/etc/php-fpm.conf.default /usr/local/php/etc/php-fpm.conf
    cp sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
    cp /usr/local/php/etc/php-fpm.d/www.conf.default /usr/local/php/etc/php-fpm.d/www.conf

    chmod +x /etc/init.d/php-fpm
    PATH=$PATH:/usr/local/php/bin/
    echo "export PATH=$PATH:/usr/local/php/bin/" >>/etc/profile
    source /etc/profile
    chkconfig --add php-fpm
    chkconfig php-fpm  on
}

startPhpFpm() {
    /etc/init.d/php-fpm start
    if [ $(netstat -lutnp|grep 9000|wc -l) -eq 1 ]
        then
            action "php-fpm starting success..." /bin/true
        else
            echo "php-fpm starting fail,plaese check the service!"
    fi
}

main() {
    initInstall
    downLoad
    installNginx
    startNginx
    installMysql
    startMysql
    installPhp
    startPhpFpm    
}

main

```