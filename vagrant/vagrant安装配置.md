# 准备
自行下载Vagrant、VirtualBox安装  

vagrant官网地址：https://www.vagrantup.com/  

box下载：https://app.vagrantup.com/boxes/search  

# Vagrant配置  
## 下载box
box提供一个操作系统环境，可以自行下载想用的基础环境，比如Ubuntu、centos、debian等  

## 添加box
```bash
vagrant box add base 远端的box地址或者本地的box文件名  
```
`base`可以是box的自定义名称，用来标识添加的box，默认是base  

可以通过`vagrant list`查看刚刚添加的box  

## 初始化
```bash
vagrant init Box名称
```
如果没有指定名称为base，则需要指定初始化的Box名称。此时将会在目录下生成一个`Vagrantfile`文件  

## 启动虚拟机
```bash
vagrant up
```

## ssh连接到虚拟机
```bash
vagrant ssh
```

# Vagrantfile配置文件详解
目录下有一个文件`Vagrantfile`，里面包含有大量的配置信息，主要包括三个方面的配置，虚拟机的配置、SSH配置、Vagrant的一些基础配置。  

## box设置
```bash
config.vm.box = "base"
```
用于配置Vagrant要去启用哪个box作为系统，默认是`base`  

VirtualBox提供了VBoxManage这个命令行工具，可以让我们设定VM，用`modifyvm`这个命令让我们可以设定VM的名称和内存大小等等，这里说的名称指的是在VirtualBox中显示的名称  
```bash
config.vm.provider "virtualbox" do |v|
    v.customize ["modifyvm", :id, "--name", "astaxie", "--memory", "512"]
end
```
这行设置的意思是调用VBoxManage的modifyvm的命令，设置VM的名称为astaxie，内存为512MB。你可以类似的通过定制其它VM属性来定制你自己的VM。

或者使用默认的配置信息修改虚拟机配置  
```bash
config.vm.provider "virtualbox" do |vb|
# Display the VirtualBox GUI when booting the machine
# vb.gui = true
  #
# Customize the amount of memory on the VM:
vb.memory = "1024"
vb.cpus = "2"
vb.name = "pww_centos7"
end
```

## 网络设置
Vagrant有两种方式来进行网络连接，一种是`host-only(主机模式)`，意思是主机和虚拟机之间的网络互访，其他人访问不到你的虚拟机。另一种是`Bridge(桥接模式)`，该模式下的VM就像是局域网中的一台独立的主机，也就是说需要VM到你的路由器要IP，这样的话局域网里面其他机器就可以访问它  
```bash
# host-only
config.vm.network "private_network", ip: "192.168.33.10"

# Bridge
config.vm.network "public_network"
```

## hosename设置
```bash
config.vm.hostname = "go-app"
```
设置hostname非常重要，因为当我们有很多台虚拟服务器的时候，都是依靠hostname來做识别的  

## 同步目录（挂载）
`/vagrant`目录默认就是当前的开发目录，这是在虚拟机开启的时候默认挂载同步的。我们还可以通过配置来设置额外的同步目录：  
```bash
config.vm.synced_folder "../data", "/vagrant_data"
```

## 端口转发（映射）
```bash
config.vm.network :forwarded_port, guest: 80, host: 8080
```
把对host机器上8080端口的访问请求forward到虚拟机的80端口的服务  

## 多台vm通信
上面的配置都是针对单个服务器配置，如果是多个服务器，比如一台应用服务器，一台redis服务器，可以使用下面的配置：
```bash
Vagrant.configure("2") do |config|
  config.vm.define :web do |web|
    web.vm.provider "virtualbox" do |v|
          v.customize ["modifyvm", :id, "--name", "web", "--memory", "512"]
    end
    web.vm.box = "CentOs7"
    web.vm.hostname = "web"
    web.vm.network :private_network, ip: "192.168.33.10"
  end

  config.vm.define :redis do |redis|
    redis.vm.provider "virtualbox" do |v|
          v.customize ["modifyvm", :id, "--name", "redis", "--memory", "512"]
    end
    redis.vm.box = "CentOs7"
    redis.vm.hostname = "redis"
    redis.vm.network :private_network, ip: "192.168.33.11"
  end
end
```

也可以通过指定服务器名，ssh登录到指定服务器  
```bash
vagrant ssh redis
```

# 常见问题
- 无法挂载本地目录到虚拟机，提示mount: unknown filesystem type 'vboxsf'  
  ```bash
  # 通过安装vagrant-vbguest来修复
  vagrant plugin install vagrant-vbguest
  vagrant reload
  ```

**修改完配置记得用`vagrant reload`重启命令使配置生效**

> 参考文档：  
> https://github.com/astaxie/go-best-practice/blob/master/ebook/zh/01.2.md  
> https://blog.csdn.net/hel12he/article/details/51089774