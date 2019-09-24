**第一种情况：只合并某一个commit**  
```bash
git checkout release
git cherry-pick 646abcdc
```
646abcdc 是develop上的一个fix bug的commit，以上就上讲这个commit合并到realease分支上


**第二种情况：合并连续的多个commit到指定的分支上**  

比如在develop上有一个 646abcdc 到 3a726c63 连续的10个commit，3a726c63 后面是其他的提交。现在要将这10个commit合并到release分支上  
1. 首先基于develop分支创建一个临时分支temp，并指明新分支的最后一个commit
    ```bash
    git checkout -b temp 3a726c63
    ```
2. 将temp分支上的 646abcdc 到最后一个commit，也就是 3a726c63 的commit合并到release上
    ```bash
    git rebase --onto release 646abcdc^
    ```