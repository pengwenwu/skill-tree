> 记录一些阅读《鸟哥的Linux私房菜-基础学习篇》中不太熟悉的命令  

### 软连接、硬链接
> **软连接**：连接文件的内容只会写上目标文件的“文件名”，实际是通过记录的目标文件地址去访问实际存储内容。大小就是文件名大小。如果删除目标文件，则当前文件无法访问。(等同于windows的快捷方式)  

> **硬连接**：只能针对单个文件，实际会创建一个一模一样的“文件”(权限、大小)，连接数增加。连接到同一个地址，只是使用了不同的文件名，均可以对源文件进行数据修改，删除任意一个，不影响其余文件访问

```
cp -s test test1  
ln -s test test1 # 软连接(快捷方式)

cp -l demo.php demo1.php 
ln -l demo.php demo1.php # 硬链接(只支持单个文件)
```

### 查看分区

```
fdisk -l
```

### 解压缩  

```
#-z gzip压缩，-j bzip2压缩
tar -zcvf [newfilename] filename 
tar -zxvf filename.tar.gz [-C 目录] 解压要到指定目录
```

### 查看命令  

```
type name
```

### 命名别名

```
alias rm='rm -i'
alias st='status'

unalias rm #取消别名
```

### 数据流重定向
> 标准输入：<或<< (将由键盘输入的数据由文件代替/**结束输入**)  
> 标准输出：>或>> (覆盖/追加)  
> 标准错误输出：2>或2>> (覆盖/追加)  

```
cat > catfile << eof
>This is a test.
>Ok now stop
>eof
```

### 命令执行判断：; || &&   

```
#依次执行
echo 1; echo 2; echo 3 

#若cmd1正确执行，则开始执行cmd2
#若cmd1执行错误，则cmd2不执行
cmd1 && cmd2   
               
#若cmd1正确执行，则不执行cmd2
#若cmd1执行错误，则开始执行cmd2
cmd1 || cmd2           
```

### 选取命令：cut, grep  
> cut:  
> -d：分割字符，与-f一起用  
> -f：取出第几段  
> -c：取出固定字符区间  

```
# 列出第三段，第五段
echo $PATH | cut -d ':' -f 3,5 

# 取得第5字符之后
echo $PATH | cut -c 5-
```

### 排序命令:sort, wc, uniq
> sort [-fbMnrtuk]    
> uniq [-ic]  
> -c：进行计数  

```
# 每个人登录的次数
last | cut -d ' ' -f 1 | sort | uniq -c 
```

> wc [-lwm]  
> -l：仅列出行  
> -w：仅列出多少字
> -m：多少字符