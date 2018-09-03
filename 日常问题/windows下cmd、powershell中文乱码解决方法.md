> windows下cmd或者powershell运行php文件中文乱码  

### 解决方法
执行下面的命令，切换成utf-8编码
```
chcp 65001
``` 

这个指标不治本，查了说是可以修改注册表，但是好像又会带来其他的坑（中文软件乱码、nodejs乱码），未测试过，不予评价。参考链接：https://www.zhihu.com/question/54724102