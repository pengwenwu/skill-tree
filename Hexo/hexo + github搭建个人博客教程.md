## 前言
一年前，临近毕业。为了准备面试，才刻意去准备了`github`以及`blog`。
自从找到工作后，这两个基本没怎么维护过，想想未免太功利了点。

主要是前几天，又踩到坑了，想去找以前的记录，很麻烦。
之前虽然每天都会写工作总结，一些踩过的坑记在为知笔记上，没有`分类`、`标签`，后面再想去找很困难。
痛定思痛，该记的东西少不掉，索性优雅一点。

之前用的是博客园，但是那个账号密码老记不住，干脆自己搭建一个吧，好歹也是一个码农。
然后花了两个晚上加一个上午，通过`hexo`和`github`搭建了一个个人博客。
网上教程、文档那么多，为什么花这么久时间？当然是踩坑了啊。
所以下面会记录一些遇到的问题和坑。
如果你看完这边文章，那你只需要`两个小时`就能搭建成功。欢迎有兴趣的小伙伴尝试一下。

## 正文
### 环境准备
1. **node.js**
2. **git**

这两个应用windows用户直接搜索下载安装就可以。
如果习惯了使用linux命令的朋友，推荐windows神器`cmder`。
可以直接在windows环境下使用linux命令，样式可调，再也不要用黑乎乎的cmd了，而且自带git，完全可以不用下载windows git。

### 正式安装hexo
[hexo官方中文文档](https://hexo.io/zh-cn/docs/)

在node.js安装好的前提下，全局安装hexo
如何判断node.js是否安装成功？执行以下命令，如果能够看到版本号则说明安装成功了
````
node -v
````

安装`hexo`
````
npm install -g hexo-cli
````

自选合适的目录，新建文件夹&lt;folder&gt;
````
cd <folder>
hexo init
npm install
````

不再赘述，直接看官方文档。

### 配置github
新建仓库，仓库名必须为**[your_name.github.io]**

> 补充：本地配置github ssh连接，方便自动部署，以及clone你喜欢的主题(theme)

windows用户直接在`c:/用户/youername/.ssh/`下查看是否有`id_rsa.pub`文件。
没有的话命令行执行命令`ssh-keygen -t rsa -C "your eamil"`，会自动生成`id_rsa.pub`文件，打开后复制。

github->头像->Settings→SSH kyes→Add SSH key，粘贴复制的内容。

配置本地账户
````
git config --global user.name “your_username” #设置用户名
git config --global user.email “your_email” #设置邮箱地址,最好使用注册邮箱地址
````

测试是否配置成功
````
ssh -T git@github.com
````

### hexo配置以及使用
有两个配置文件：
- 一个是根目录下的`_config.yml`称为`站点配置`文件
- 一个是`themes/landscape/_config.yml`称为`主题配置`文件(默认主题：landscape)

站点配置如下：

````
url: https://yourname.github.io/
theme: landscape #选择你想用的主题，我用的是indigo
deploy:
    type: git # 不要使用github
    repo: git@github.com:pengwenwu/pengwenwu.github.io.git # 使用ssh连接
    branch: master # 默认master分支
    message: add new blog # 自动部署commit备注，可不填
````

#### hexo常用命令
[hexo命令参考](https://segmentfault.com/a/1190000002632530)

`hexo n "我的博客"` == `hexo new "我的博客"` #新建文章  
`hexo p` == `hexo publish`  
`hexo g` == `hexo generate` #生成  
`hexo s` == `hexo server` #启动服务本地预览  
`hexo d` == `hexo deploy` #部署  
`hexo clean` #清除缓存 网页正常情况下可以忽略此条命令  

`hexo server` #Hexo 会监视文件变动并自动更新，您无须重启服务器。  
`hexo server -s` #静态模式  
`hexo server -p 5000` #更改端口  
`hexo server -i 192.168.1.1` #自定义 IP

在执行之前，记得安装自动部署 (--save 加不加的区别在于是否写入到依赖文件package.json中)
````
npm install hexo-deployer-git --save
````

正常本地预览，直接执行`hexo s`,如果要发布话最好执行`clean`命令，会去删除生成的public文件，完整部署命令:`hexo clean && hexo g && hexo d`。或者直接`hexo d -g`

### 注意问题
安装完自动部署后，是不需要本地git init新建仓库的。执行`hexo g`会在根目录生成public文件夹，自动部署，
本质是将public文件夹内容全部提交到仓库中去，默认会访问编译好的index.html。

如果部署完，访问your_name.github.io 404,可能有下面几个原因
1. 首先检查仓库文件，是不是全都是public的文件内容，如果整个本地blog文件夹都提交了，首先清空
仓库，然后删除本地`.deploy_git`文件夹，再重新部署
2. 文件有报错，本地`hexo s`观察是否有报错。

不喜欢原主题的朋友，可以github去找喜欢的主题。执行命令
````
git clone XXXX.next.git themes\next
````
这个会将新的主题下载到themes下对应的next目录，next为主题的名字。

主题的配置，可以看文档，修改对应的`主题配置`文件。  
我使用的主题是`indigo`,详细文档[indigo](https://github.com/yscoder/hexo-theme-indigo)

`markdown`不会使用的朋友，参考链接[markdown中文文档](https://www.appinn.com/markdown/)  
如果没有ide的话，可以使用在线预览[Cmd Markdown](https://www.zybuluo.com/mdeditor)