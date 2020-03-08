下载镜像：  
```bash
docker pull xuxueli/xxl-job-admin:2.1.2
```

启动脚本  
```bash
#!bin/bash

docker run -d \
--name my-xxl-job-admin \
-p 8080:8080 \
-v ~/my_docker/xxl_job/data:/data/applogs \
-v ~/my_docker/conf/xxl-job-admin/application.properties:/application.properties \
-e PARAMS='--spring.config.location=/application.properties' \
--link my-mysql:db \
--restart=always \
xuxueli/xxl-job-admin:2.1.2
```