> [阅读《Docker — 从入门到实践》](https://docker_practice.gitee.io/)  

# windows使用 
- 下载docker for windows，安装 
- 使用镜像加速，我自己使用的是[阿里云镜像加速](https://cr.console.aliyun.com/#/accelerator)setting->Daemon->Registry mirrors->粘贴自己的加速器地址 
- 可以使用gui界面，Kitematic

# 常用命令
## 获取镜像 
可以使用`docker pull --help`查看
```bash
docker pull [选项] [Docker Registry 地址[:端口号]/]仓库名[:标签]

# etc
docker pull ubuntu:16.04
```
## 运行
```bash
docker run -it --rm ubuntu:16.04 bash

# 查看当前系统版本
cat /etc/os-release
```
- `-it`: `-i`: 交互式操作, `-t`: 终端 
- `--rm`: 执行退出后删除该容器实例
- `bash`: shell使用的方式 

## 镜像列表
```bash
docker image ls

# etc
REPOSITORY TAG IMAGE ID CREATED SIZE
```
各列意义：`仓库名`, `标签`, `镜像id`, `创建时间`, `解压后文件大小` 
**镜像ID 则是镜像的唯一标识，一个镜像可以对应多个标签** 

## 镜像大小 
```bash
docker system df
```

## 删除镜像 
```bash
docker image rm [选项] <镜像1> [<镜像2> ...]
```

## 启动已终止的container镜像 
```bash
docker [container] start
```

## 后台运行容器 
`-d`参数能够让容器后台运行，保证输出结果不会打印出来。但容器是否长久运行（一直后台挂起），跟`-d`参数无关，需要一直有指令执行，才不会“秒退” 
```bash
# -c exec执行
docker run ubuntu:17.10 /bin/sh -c "while true; do echo hello world; sleep 1; done"

# 后台运行
docker run -d ubuntu:17.10 /bin/sh -c "while true; do echo hello world; sleep 1; done"
```

可以通过`docker [container] logs`去查看容器输出信息 

## 终止运行 
```bash
docker [contanier] stop
```

## 进入容器 
```bash
docker attach 

# 推荐使用
docker exec -it [container id] /bin/bash
```

两者的区别：前者exit退出后，会停止当前容器；而`exec`仍然会保持运行 

## 删除容器 
```bash
docker container rm 

# 清楚所有处于终止状态的容器
docker container prune
```

## commit 
**为什么不建议使用`docker commit`?** 
使用`docker commit`提交后，对于其他使用者而言，这个image镜像是一个黑箱，别人无处得知执行过什么命令、如何生成的镜像 

## Dokcerfile 定制镜像 
### FROM指定基础镜像
### RUN执行命令 
- shell格式：`RUN <命令>` 
- exec格式：`RUN ["可执行文件", "参数1", "参数2"]` 

## 构建镜像
```bash
docker build [选项] <上下文路径/URL/->

# etc, 注意镜像构建上下文(context)
docker build -t nginx:v3 .
```

## Dockerfile 指令详解 
### CMD 启动命令 
`CMD` 指令就是用于指定默认的容器主进程的启动命令的，也分为`shell`格式以及`exec`格式 
- shell格式：CMD <命令> 
- exec格式：CMD ["可执行文件", "参数1", "参数2"...] 
- 参数列表格式：CMD ["参数1", "参数2"...]。在指定了 ENTRYPOINT 指令后，用 CMD 指定具体的参数 

一般`推荐`使用`exec`格式，这类格式在解析时会被解析成JSON数组，因此要使用`双引号` 

如果是`shell`格式，实际会被包装成`sh -c`的格式 
```bash
CMD echo $HOME
```

实际执行会变成： 
```bash
CMD [ "sh", "-c", "echo $HOME" ]
```

**注意**：Docker不是虚拟机，容器中的应用都是`前台执行`，没有后台服务的概念 

错误示范：
```bash
CMD service nginx start
```
这里容器执行后会秒退出，因为上面的命令会被转化为`CMD ["sh", "-c", "service nginx start]`，主进程是`sh`，当service nginx start执行结束后，sh也就结束了，sh作为主进程结束，所以容器也会退出

正确做法：
```bash
CMD [ "nginx", "-g", "daemon off;" ]
```

这边执行`docker run`的时候，不需要再跟`/bin/bash`启动命令，因为会覆盖。否则就是秒结束进程

### ENTRYPOINT 入口点
如果指定了`ENTRYPOINT`，`CMD`就不会直接执行命令，而是讲内容作为参数传给`ENTRYPOINT`，实际执行指令会变为：
```bash
ENTRYPOINT "<CMD>"
```

#### 场景一：让镜像变成像命令一样使用
```bash
docker run myip -i
```

#### 场景二：应用运行前的准备工作
在启动主进程之前，需要一些准备工作，比如数据库的配置、初始化 

### ENV 设置环境变量
两种格式：
- ENV \<key\> \<value\>
- ENV \<key1\>=\<value1\> \<key2>=\<value2\> ... 
```bash
# 含有空格的值使用双引号
ENV VERSION=1.0 DEBUG=on \
    NAME="Happy Feet"
```

### ARG构建参数
格式：`ARG <参数名>[=<默认值>]` 

构建参数和ENV效果一样，都是设置环境变量，唯一的区别是，`ARG`构建的环境变量，在将来容器运行的时候，不会存储这些环境变量 

`Dokcerfile`中的`ARG`指令是定义参数名称，以及其默认值。可以通过构建命令`docker build`中用`--build-arg <参数名>=<值>`来覆盖 

### VOLUME 定义匿名卷 
格式为：
- VOLUME ["<路径1>", "<路径2>"...]
- VOLUME <路径> 

容器运行时应该尽量保持容器存储层不发生写操作，对于数据库类需要保存动态数据的应用，其数据库文件应该保存于卷(volume)中 

```bash
VOLUME /data
```

这里的 /data 目录就会在运行时自动挂载为匿名卷，任何向 /data 中写入的信息都不会记录进容器存储层，从而保证了容器存储层的无状态化 

```bash
docker run -d -v mydata:/data xxxx
```

这里mydata 这个命名卷挂载到了 /data 这个位置，替代了 Dockerfile 中定义的匿名卷的挂载配置 

### EXPOSE 声明端口 
格式为：`EXPOSE <端口1> [<端口2>...]` 

`EXPOSE` 指令是声明运行时容器提供服务端口，这只是一个声明，在运行时并不会因为这个声明应用就会开启这个端口的服务。在 Dockerfile 中写入这样的声明有两个好处: 
- 帮助镜像使用者理解这个镜像服务的守护端口，以方便配置映射 
- 在运行时使用随机端口映射时，也就是 `docker run -P` 时，会自动随机映射 `EXPOSE` 的端口 

要将 `EXPOSE` 和在运行时使用 `-p <宿主端口>:<容器端口>` 区分开来。-p，是映射宿主端口和容器端口，换句话说，就是将容器的对应端口服务公开给外界访问，而 EXPOSE 仅仅是声明容器打算使用什么端口而已，并不会自动在宿主进行端口映射。 

### WORKDIR 指定工作目录 
格式为: `WORKDIR <工作目录路径>` 

使用 `WORKDIR` 指令可以来指定工作目录（或者称为当前目录），以后各层的当前目录就被改为指定的目录，如该目录不存在，`WORKDIR` 会帮你建立目录 

### USER 指定当前用户 
格式：`USER <用户名>` 

`USER` 指令和 `WORKDIR` 相似，都是改变环境状态并影响以后的层。`WORKDIR` 是改变工作目录，`USER` 则是改变之后层的执行 `RUN`, `CMD` 以及 `ENTRYPOINT` 这类命令的身份 

### HEALTHCHECK 健康检查 
`HEALTHCHECK` 指令是告诉 Docker 应该如何进行判断容器的状态是否正常 

格式： 
- HEALTHCHECK [选项] CMD <命令>：设置检查容器健康状况的命令
- HEALTHCHECK NONE：如果基础镜像有健康检查指令，使用这行可以屏蔽掉其健康检查指令 

### ONBUILD 
格式：`ONBUILD <其它指令>` 

`NOBUILD`指令是别人定制镜像。即使用`FROM`的时候，才执行的命令 

## 推送镜像 
```bash
# 先打标签
docker tag ubuntu:17.10 username/ubuntu:17.10

# 在push
docker push username/ubuntu:17.10
```

## 配置私有仓库
[配置私有仓库](https://docker_practice.gitee.io/repository/registry.html) 

## 数据卷
`数据卷` 是一个可供一个或多个容器使用的特殊目录： 
- `数据卷`可以在容器之间共享和重用 
- 对 `数据卷` 的修改会立马生效 
- 对 `数据卷` 的更新，不会影响镜像
- `数据卷` 默认会一直存在，即使容器被删除 
> 注意：数据卷 的使用，类似于 Linux 下对目录或文件进行 mount，镜像中的被指定为挂载点的目录中的文件会隐藏掉，能显示看的是挂载的 数据卷。 

## 外部访问容器 
使用`- P`标记时，Docker会随机映射 `49000~49900` 的端口到内部容器开放的网络端口 

`- p`可以指定要映射的端口，也可以指定地址
`ip:hostPort:containerPort` 

```bash
docker run -d -p 127.0.0.1:5000:5000 training/webapp python app.py
```

### 查看映射端口配置 
`docker port` 
```bash
docker port nostalgic_morse 5000
```

- 容器有自己的内部网络和 ip 地址 
- `-p` 标记可以多次使用来绑定多个端口 

## 容器互联 
### 查看已有网络
```bash
docker network ls
```

### 新建网络 
```bash
docker network create -d bridge my-net
```
`-d` 可以指定Docker网络类型，有`bridge`, `overlay`，其中 `overlay` 网络类型用于 `Swarm mode`(**集群服务**) 

### 连接容器 
运行一个容器并连接到新建的 `my-net` 网络 
```bash
docker run -it --rm --name busybox1 --network my-net busybox sh

# 再运行一个容器
docker run -it --rm --name busybox2 --network my-net busybox sh

# 测试连接
# 在busybox1 容器里，执行
# /ping busybox2
PING busybox2 (172.19.0.3): 56 data bytes
64 bytes from 172.19.0.3: seq=0 ttl=64 time=0.060 ms
64 bytes from 172.19.0.3: seq=1 ttl=64 time=0.046 ms
64 bytes from 172.19.0.3: seq=2 ttl=64 time=0.075 ms
```

# Compose 项目 
`Compose` 项目是 Docker 官方的开源项目，负责实现对 Docker 容器集群的快速编排。它允许用户通过一个单独的 `docker-compose.yml` 模板文件（YAML 格式）来定义一组相关联的应用容器为一个项目（project）。 
`Compose` 中有两个重要的概念： 
- `服务 (service)`：一个应用的容器，实际上可以包括若干运行相同镜像的容器实例。 
- `项目 (project)`：由一组关联的应用容器组成的一个完整业务单元，在 docker-compose.yml 文件中定义。 

`Compose` 的默认管理对象是`项目`，通过子命令对项目中的一组容器进行便捷地生命周期管理。

## Compose 命令说明 
### 命令对象与格式
`docker-compose` 命令的基本的使用格式是: 
```bash
docker-compose [-f=<arg>...] [options] [COMMAND] [ARGS...]
```

### 命令选项
- -f, --file FILE 指定使用的 Compose 模板文件，默认为 docker-compose.yml，可以多次指定。 
- -p, --project-name NAME 指定项目名称，默认将使用所在目录名称作为项目名。 
- --x-networking 使用 Docker 的可拔插网络后端特性 
- --x-network-driver DRIVER 指定网络后端的驱动，默认为 bridge 
- --verbose 输出更多调试信息。 
- -v, --version 打印版本并退出 

### 命令使用说明 
**Tips**: 这里的`service name`是指`服务`的名称，不是container name 或者 container id
#### build
构建（重新构建）项目中的服务容器 
```bash
docker-compose build [options] [SERVICE...]
```

服务容器一旦构建后，将会带上一个标记名，例如对于 web 项目中的一个 db 容器，可能是 web_db 
选项包括：
- `--force-rm` 删除构建过程中的临时容器 
- `--no-cache` 构建镜像过程中不使用 cache（这将加长构建过程） 
- `--pull` 始终尝试通过 pull 来获取更新版本的镜像

#### config
验证 Compose 文件格式是否正确，若正确则显示配置，若格式错误显示错误原因 

### down
此命令将会停止 `up` 命令所启动的容器，并移除网络 

### exec
进入指定的容器 
```bash
# 如果执行/bin/bash失败，报错OCI runtime exec failed,是因为bash不存在，替换成sh  

docker-compose exec web /bin/sh
```

### images 
列出 Compose 文件中包含的镜像 

### kill
格式为 `docker-compose kill [options] [SERVICE...]` 

通过发送 SIGKILL 信号来强制停止服务容器 

支持通过 -s 参数来指定发送的信号，例如通过如下指令发送 SIGINT 信号

```bash
docker-compose kill -s SIGINT
```

### logs
查看服务容器的输出。 
默认情况下，docker-compose 将对不同的服务输出使用不同的颜色来区分。可以通过 --no-color 来关闭颜色。  

格式为：`docker-compose logs [options] [SERVICE...]` 
### pause
暂停服务 
格式为：`docker-compose pause [SERVICE...]` 

### port
打印某个容器端口所映射的公共端口 

格式为 `docker-compose port [options] SERVICE PRIVATE_PORT` 

选项：
- --protocol=proto 指定端口协议，tcp（默认值）或者 udp。
- --index=index 如果同一服务存在多个容器，指定命令对象容器的序号（默认为 1） 

### ps
### pull
### push
### restart
### stop
### rm
### run
格式为 `docker-compose run [options] [-p PORT...] [-e KEY=VAL...] SERVICE [COMMAND] [ARGS...]` 
```bash
docker-compose run ubuntu ping docker.com
```
默认情况下，如果存在关联，则所有关联的服务将会自动被启动，除非这些服务已经在运行中。

该命令类似启动容器后运行指定的命令，相关卷、链接等等都将会按照配置自动创建。

两个不同点：

给定命令将会覆盖原有的自动运行命令；

不会自动创建端口，以避免冲突。

如果不希望自动启动关联的容器，可以使用 --no-deps 选项，例如: 
```bash
docker-compose run --no-deps web python manage.py shell
```
将不会启动 web 容器所关联的其它容器. 

选项：
- -d 后台运行容器。
- --name NAME 为容器指定一个名字。
- --entrypoint CMD 覆盖默认的容器启动指令。
- -e KEY=VAL 设置环境变量值，可多次使用选项来设置多个环境变量。
- -u, --user="" 指定运行容器的用户名或者 uid。
- --no-deps 不自动启动关联的服务容器。
- --rm 运行命令后自动删除容器，d 模式下将忽略。
- -p, --publish=[] 映射容器端口到本地主机。
- --service-ports 配置服务端口并映射到本地主机。
- -T 不分配伪 tty，意味着依赖 tty 的指令将无法运行。 

### scale
设置指定服务运行的容器个数 

格式为 `docker-compose scale [options] [SERVICE=NUM...]` 

通过 `service=num` 的参数来设置数量。例如： 
```bash
docker-compose scale web=3 db=2
```

一般的，当指定数目多于该服务当前实际运行容器，将新创建并启动容器；反之，将停止容器。 

### top
### unpause
启动已暂停的服务 

### up
格式为 `docker-compose up [options] [SERVICE...]` 

该命令十分强大，它将尝试自动完成包括构建镜像，（重新）创建服务，启动服务，并关联服务相关容器的一系列操作 

链接的服务都将会被自动启动，除非已经处于运行状态 

默认情况，`docker-compose up` 启动的容器都在前台，控制台将会同时打印所有容器的输出信息，可以很方便进行调试 

如果使用 `docker-compose up -d`，将会在后台启动并运行所有的容器。一般推荐生产环境下使用该选项。 

选项：
- -d 在后台运行服务容器。
- --no-color 不使用颜色来区分不同的服务的控制台输出。
- --no-deps 不启动服务所链接的容器。
- --force-recreate 强制重新创建容器，不能与 --no-recreate 同时使用。
- --no-recreate 如果容器已经存在了，则不重新创建，不能与 --force-recreate 同时使用。
- --no-build 不自动构建缺失的服务镜像。
- -t, --timeout TIMEOUT 停止容器时候的超时（默认为 10 秒）

## Compose 模板文件
模板文件是使用 Compose 的核心，涉及到的指令关键字也比较多。但大家不用担心，这里面大部分指令跟 docker run 相关参数的含义都是类似的。 

默认的模板文件名称为 docker-compose.yml，格式为 YAML 格式。 
```bash
version: '3'
services:

  webapp:
    build:
      context: ./dir
      dockerfile: Dockerfile-alternate
      args:
        buildno: 1
```
注意每个服务都必须通过 `image` 指令指定镜像或 `build` 指令（需要 Dockerfile）等来自动构建生成镜像 

如果使用 `build` 指令，在 `Dockerfile` 中设置的选项(例如：CMD, EXPOSE, VOLUME, ENV 等) 将会自动被获取，无需在 docker-compose.yml 中再次设置。 

### build 
指定 Dockerfile 所在文件夹的路径（可以是绝对路径，或者相对 docker-compose.yml 文件的路径）。 Compose 将会利用它自动构建这个镜像，然后使用这个镜像。 

可以使用 `context` 指令指定 Dockerfile 所在文件夹的路径 

使用 `dockerfile` 指令指定 Dockerfile 文件名 

使用 `arg` 指令指定构建镜像时的变量 

使用 `cache_from` 指定构建镜像的缓存 
```bash
build:
  context: .
  cache_from:
    - alpine:latest
    - corp/web_app:3.14
```

### cap_add, cap_drop
指定容器的内核能力（capacity）分配 

### command
覆盖容器启动后默认执行的命令 
```bash
command: echo "hello world"
```

### container_name
指定容器名称。默认将会使用 `项目名称_服务名称_序号` 这样的格式 
```bash
container_name: docker-web-container
```
>注意: 指定容器名称后，该服务将无法进行扩展（scale），因为 Docker 不允许多个容器具有相同的名称 

### devices
指定设备映射关系。 
```bash
devices:
  - "/dev/ttyUSB1:/dev/ttyUSB0"
```

### depends_on
解决容器的依赖、启动先后的问题。 
以下例子中会先启动 redis db 再启动 web 
```bash
version: '3'

services:
  web:
    build: .
    depends_on:
      - db
      - redis

  redis:
    image: redis

  db:
    image: postgres
```
> 注意：`web` 服务不会等待 `redis` `db` 「完全启动」之后才启动。 

### dns
自定义 `DNS` 服务器。可以是一个值，也可以是一个列表。 
```bash
dns: 8.8.8.8

dns:
  - 8.8.8.8
  - 114.114.114.114
```

### env_file
从文件中获取环境变量，可以为单独的文件路径或列表。 

如果通过 `docker-compose -f FILE` 方式来指定 Compose 模板文件，则 env_file 中变量的路径会基于模板文件路径 

如果有变量名称与 environment 指令冲突，则按照惯例，以后者为准 

```bash
env_file: .env

env_file:
  - ./common.env
  - ./apps/web.env
  - /opt/secrets.env
```

环境变量文件中每一行必须符合格式，支持 # 开头的注释行。 
```bash
# common.env: Set development environment
PROG_ENV=development
```

### environment
设置环境变量。可以使用数组或字典两种格式 

只给定名称的变量会自动获取运行 Compose 主机上对应变量的值，可以用来防止泄露不必要的数据 

```bash
environment:
  RACK_ENV: development
  SESSION_SECRET:

environment:
  - RACK_ENV=development
  - SESSION_SECRET
```

如果变量名称或者值中用到 true|false，yes|no 等表达 `布尔` 含义的词汇，最好放到引号里，避免 YAML 自动解析某些内容为对应的布尔语义。 

### expose
暴露端口，但不映射到宿主机，只被连接的服务访问 

仅可以指定内部端口为参数

```bash
expose:
- "3000"
- "8000"
```

### image
指定为镜像名称或镜像 ID 

### 读取变量
Compose 模板文件支持动态读取主机的系统环境变量和当前目录下的 `.env` 文件中的变量。 

例如，下面的 Compose 文件将从运行它的环境中读取变量 `${MONGO_VERSION}` 的值，并写入执行的指令中。 

```bash
version: "3"
services:

db:
  image: "mongo:${MONGO_VERSION}"
```
如果执行 `MONGO_VERSION=3.2` `docker-compose up` 则会启动一个 `mongo:3.2` 镜像的容器；如果执行 `MONGO_VERSION=2.8` docker-compose up 则会启动一个 `mongo:2.8` 镜像的容器。 

若当前目录存在 `.env` 文件，执行 docker-compose 命令时将从该文件中读取变量。

在当前目录新建 `.env` 文件并写入以下内容。 
```bash
# 支持 # 号注释
MONGO_VERSION=3.6
```
执行 docker-compose up 则会启动一个 `mongo:3.6` 镜像的容器。

# 附录
## 常见问题总结
[常见问题总结](https://docker_practice.gitee.io/appendix/faq/)

## 资源链接
[资源链接](https://docker_practice.gitee.io/appendix/resources/) 

## 进阶深入
进阶深入，参考原文档[《Docker — 从入门到实践》](https://docker_practice.gitee.io/)

