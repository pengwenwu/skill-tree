# Vagrant常用命令
在安装配置中，使用了一些基础命令  
- `vagrant box add`：添加box  
- `vagrant init`：初始化  
- `vagrant up`：启动虚拟机  
- `vagrant ssh`：登录虚拟机  
- `vagrant reload`：重新启动虚拟机  

Vagrant还包括如下一些操作：  
- `vagrant box list`  
    显示当前已经添加的box列表  
    ```bash
    $ vagrant box list
    base (virtualbox)
    ```
- `vagrant box remove`  
    删除相应的box  
    ```bash
    vagrant box remove base virtualbox
    ```
- `vagrant destroy`  
    停止当前正在运行的虚拟机并销毁所有创建的资源  
- `vagrant halt`  
    关机  
- `vagrant package`  
    打包命令，可以把当前的运行的虚拟机环境进行打包  
- `vagrant resume`  
    恢复前面被挂起的状态  
- `vagrant ssh-config`  
    输出用于ssh连接的一些信息  
- `vagrant status`  
    获取当前虚拟机的状态  
- `vagrant suspend`  
    挂起当前的虚拟机  

> 参考链接：  
> https://github.com/astaxie/go-best-practice/blob/master/ebook/zh/01.3.md