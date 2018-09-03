> git `revert` 撤销某次提交, 保留之前的commit, 同时创建一个新的commit(可用于**公共分支**)
> git `reset` 撤销某个提交， 删除之前的commit, head回退 (多用于**私有分支**) 
> **区别**: git revert 只是撤销某个commit, 保留该commit之前的提交结果

### DEMO  
> commit3: add log3  
> commit2: add log2  
> commit1: add log1

### HEAD用法
- HEAD^: 指向上一次commit
- HEAD~100: 指向前第一百个commit
- HEAD commit_id: 直接指向某个commit  

### --soft、--mixed(默认)、--hard的区别
- --soft: 只是重置HEAD到某一个commit, 其余不会有任何变化(缓存区和工作目录都不会被改变)
- --mixed(默认): 重置HEAD, 文件修改都将保存到缓存区(缓存区和你指定的提交同步，但工作目录不受影响)
- --hard: 修改当前所有内容, 所有本地修改都将丢失(缓存区和工作目录都同步到你指定的提交). 找回执行命令: git reflow
