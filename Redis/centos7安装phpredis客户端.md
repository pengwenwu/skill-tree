## 源码下载
```bash
cd /usr/local/src

wget https://pecl.php.net/get/redis-4.1.1.tgz

tar zxvf redis-4.1.1.tgz

cd redis-4.1.1

phpize

./configure --with-php-config=/usr/local/bin/php-config

make && make install
```

## 修改php.ini配置
```bash
vim /usr/local/php7/etc/php.ini

# 增加redis扩展
extension=redis.so
```

## 重启php-fpm
```bash
systemctl restart php-fpm

# 检查扩展是否安装成功
php -m | grep redis
```