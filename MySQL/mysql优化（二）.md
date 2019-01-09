[TOC]
### 一、基本架构
1.服务层：处理连接     安全验证
2.核心层：查询分析,优化,缓存,内置函数     内建试图,存储过程,触发器
3.存储引擎：数据的存储     提取

### 二、选择版本
MariaDB 完全兼容mysql     XtraDB引擎  代替mysql的InnoDB引擎
     企业版：收费
     社区版：开源，用的人多
     Percona Server：新特性多
     MariaDB：国内用的不多

### 三、配置文件详解
   /etc/my.cnf
1. max_connections     mysql所允许的同时会话数     报错：Too many connections
2. max_connect_erros  最大错误允许数     报错：FLUSH HOSTS 重启服务     
3. key_buffer_size        关键词缓冲区大小     缓存MyISAM索引块 索引处理速度 读取索引处理速度
4. max_allowed_packet设置最大包    限制server接收数据块大小 避免超长SQL执行 信息包过大 关闭连接   丢失与MySQL服务器连接报错
5. thread_cache_size     服务器线程缓存
6. thread_concurrency  CPU核数×2  MySQL不能很好利用多核处理器性能
7. sort_buffer_size        每个连接需要使用buffer时分配的内存大小   不是越大越好
8. join_buffer_size         join表使用的缓存大小
9. query_cache_size      查询缓存大小     查询结果缓存     缓存期间表必须没有更改，否则失效     多写操作数据库设置过大，影响写入效率
10. read_buffer_size     MyISAM全表扫描缓冲大小  无法添加索引全表扫描，需要增大优化
11. read_rnd_buffer_size 从排序好的数据中读取行时的，行数据从缓冲区读取  提升order by性能
          注意：mysql会为每个客户端申请这个缓冲区，并发过大，设置过大，影响开销
12. myisam_sort_buffer_size：当MyISAM表发生变化时，重新排序，所需缓存
13. innodb_buffer_pool_size：InnoDB 使用缓存保存索引，原始数据，缓存大小，可以有效减少读取数据所需的磁盘IO
14. nnodb_log_file_size：数据日志文件大小     大的值可以提高性能，但增加了恢复故障数据库的时间
15. innodb_log_buffer_size：日志文件缓存    增大该值可以提高性能，但增大了忽然宕机损失数据的风险
16. innodb_flush_log_at_trx_commit：执行事务的时候，会往InnoDB存储引擎日志缓存中插入事务日志
     写数据前先预写日志的方式 ：
       0  每秒日志缓存写入文件 实时写入
       1  缓存实时写入文件，文件实时写入磁盘
       2  缓存实时写入文件，每秒日志文件写入磁盘
17. innodb_lock_wait_timeout：被回滚前，一个InnoDB事务，应该等待一个锁被批准多久，InnoDB无法检测死锁发生，这时候用这个值有效
     《高性能MySQL》

### 四、软件优化
1. 选合适的引擎
   a. MyISAM 索引顺序访问方法  支持全文索引  非事务安全  不支持外键  表级锁
        三个文件FRM文件存放表结构  MYD文件存放数据  MYI存放索引
   b. InnoDB 事务性存书引擎  行锁   InnoDB 回滚 崩溃恢复
        ACID事务控制 表和索引放在一个空间里  表空间多个文件
2. 正确使用索引
   a. 给合适的列建立索引  where子句  连接子句  而不是select选择列表建立索引
   b. 索引值不尽相同  唯一值  索引效果最好  大量重复效果很差
   c. 使用短索引  指定前缀长度  较小的索引,索引缓存一定，存的索引多，消耗磁盘IO更小，能提高查找速度
   d. 最左前缀 n列索引  最左列的值匹配
   e. like查询  索引失效  尽量少用like  千万级数据用like，Sphinx开元方案结合mysql
   f. 不能滥用索引
       1）索引占用空间
       2）更新数据，索引必须更新，时间长  尽量不必要在长期不用的字段上建立索引
       3）SQL执行一个查询语句，增加查询优化的时间
3. 避免使用SELECT * 
   a. 返回结果多，降低查询的速度
   b. 过多的返回结果,增大服务器返回给APP端的数据传输量.网络传输速度慢,弱网络环境下,容易造成请求实效
4. 字段尽量设置为NOT NULL

### 五、硬件优化
1. 增加物理内存
Linux内核  内存开缓存  存放数据
-写文件  文件延迟写入机制  先把文件放到缓存  达到一定程度写进硬盘
-读文件  读文件到缓存  下次需要相同文件  从缓存文件中取  没有从硬盘中取
2. 增加应用缓存
     a. 本地缓存
        数据放到服务器内存或文件中
     b. 分布式缓存
        Redis  Memcahce  读写性能非常高  QPS 1W以上  数据持久化   Redis  不持久化  两者都可以
3. SSD代替机械硬盘
        a. 日志和数据分开存储  日志顺序读写  机械硬盘  数据随机读写  SSD
        b. 调参数  innodb_flush_method=O_DIRECT  操作系统禁用提高缓存  fsync  方式数据刷入机械硬盘
           innodb_io_capacity=10000控制MySQL中一次刷新脏页的数量  SSD  io增强  增大一次刷新脏页数量
4. SSD+SATA混合存储FlashCache FaceBook开源在文件系统和设备驱动之间加了一层缓存,对热数据缓存

### 六、架构优化
1. 分表
     a. 水平拆分
        数据分成多个表
     b. 垂直拆分
        字段分成多个表
     c. MyISAM MERGE存储引擎  InnoDB用alter table
2. 读写分离
    数据库压力大了，读和写拆开，对应主从服务器。主服务器是写操作，从服务器是读操作
    大多数业务是读业务，京东淘宝，大量浏览商品，挑选商品。购买是写操作，少量的购买
    读服务器写操作的同时，同步从服务器，保持数据完整性，主从复制
    主从复制的原理：
    基于主服务器的二进制日志(binlog)跟踪所有的对数据库的完整的更改实现
    要实现主从复制，必须启动二进制日志在主服务器上
    主从复制是异步复制，三个线程参与，主服务器一个线程(IO线程)，从服务器两个(IO线程和SQL线程)
        a. 从数据库，执行start slave 开启主从复制
        b. 从数据库IO线程会通过主数据库授权的用户请求连接数据库，并请求主数据库的binlog日志的指定位置，change master命令指定日志文件位置
        c. 数据库收到IO请求，负责复制的IO线程根据请求
     读是一些机器，写是一些机器  二进制文件的主从复制，延迟解决方案
3. 分库
     Mycat部署  工作流程

### 七、SQL慢查询分析  调参数
### 八、活用存储结构
内容表 id  user_id  content    索引表(字段)   内容表(kv,放数据)
### 九、故障排除案例
APP搜索商家，后台数据load居高不下
解决方案:like 查询无索引导致 Sphinx Coreseek开源全文检索

