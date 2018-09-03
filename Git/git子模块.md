# 子模块   
文档：[Git 工具 - 子模块](https://git-scm.com/book/zh/v1/Git-%E5%B7%A5%E5%85%B7-%E5%AD%90%E6%A8%A1%E5%9D%97)  

> 在一个项目中要引入另外一个项目，比如我在当前项目里，想要引入laradock docker环境，需要怎么操作？难道需要copy一份代码过来？怎么保证同步更新？  

## 添加子模块
在当前项目中执行`git submodule add`命令  
```bash
git submodule add https://github.com/Laradock/laradock.git laradock
```
此时，会在当前项目下新建一个`laradock`项目，执行`git status`会看到下面两项：
```bash
$ git status
On branch master
Your branch is up to date with 'origin/master'.

Changes to be committed:
  (use "git reset HEAD <file>..." to unstage)

        new file: .gitmodules
        new file: laradock
```

`.gitmodules`是一个配置文件  
```bash
$ cat .gitmodules
[submodule "laradock"]
        path = laradock
        url = https://github.com/Laradock/laradock.git
```

如果此时，同时修改原项目文件，以及子项目文件，执行`git diff`  
```bash
$ git diff
diff --git a/README.md b/README.md
index e02af03..a6a0276 100644
--- a/README.md
+++ b/README.md
@@ -1,2 +1,4 @@
 # bbs
 基于CodeIgniter框架开发的bbs系统
+
+> 新增laradock项目引入
diff --git a/laradock b/laradock
--- a/laradock
+++ b/laradock
@@ -1 +1 @@
-Subproject commit 66c61d9a72ea52ab04ddb1999b0998f7ba10a0e4
+Subproject commit 66c61d9a72ea52ab04ddb1999b0998f7ba10a0e4-dirty
```
## 修改submodule
需要进入到对应的子项目目录去修改,提交完毕之后，回到父级项目提交。

## 新clone submodule项目
先clone父级项目，然后进入子项目，发现时空文件夹，需要依次执行  
```bash
# 在子项目目录
git submodule init  

git submodule update
```

## 删除submodule
git 并不支持`直接删除`Submodule, 需要手动删除对应的文件  
```bash
# 进入父级项目
git rm --cached pod-library

# 删除子项目
rm -rf pod-library

# 删除子项目配置文件
rm .gitmodules

# 更改git的配置文件config:
vim .git/config

# 删除父级项目git配置中的子项目配置
[submodule "pod-library"]
  url = git@github.com:jjz/pod-library.git

# 完成新的提交
```

参考资料：[使用Git Submodule管理子模块](https://segmentfault.com/a/1190000003076028)