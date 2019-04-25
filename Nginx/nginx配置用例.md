用例：
```bash
server
{
  listen       80;
  
  server_name  www.jr.com;   (配置域名)
  root /opt/ci123/www/html/jr;  （指定根目录）
  index index.html index.htm index.php; （确认index的文件类型）
  access_log logs/jr.log ; （指定日志）

  location / {
        try_files /$uri  /$uri/ /index.php?$query_string;
                client_max_body_size 20M;
        }
  location ~ [^/]\.php(/|$)  {
    fastcgi_split_path_info ^(.+?\.php)(/.*)$;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    #fastcgi_param PATH_INFO $fastcgi_path_info;
    #fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;

    if (!-f $document_root$fastcgi_script_name) {
            return 404;
    }
    #fastcgi_pass  unix:/tmp/php-dmcs.sock;
    fastcgi_pass 192.168.1.4:9001;
    fastcgi_index index.php;
    include fastcgi_params;
    client_max_body_size 100M;
  }
}
```

```bash
server {
    listen 80;
    server_name test.news.com;
    root /opt/ci123/www/news;
    index index.php index.html;

    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        if (!-f $document_root$fastcgi_script_name) {
                return 404;
        }
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        client_max_body_size 100M;
    }

    location / {
        rewrite . /index.php last;
        rewrite ^/article\/([0-9]+)\.html$ /article.php?id=$1 last;
    }

    location /social {
        rewrite ^(/social)$ /social/index.php last;
    }
}
```