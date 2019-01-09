[TOC]
### 优化概述
1. 存储层：存储引擎、字段类型选择、范式设计
2. 设计层：索引、缓存、分区(分表)
3. 架构层：多个mysql服务器设置，读写分离(主从模式)
4. sql语句层：多个sql语句都可以达到目的的情况下，要选择性能高、速度快的sql语句

### InnoDB和MyISAM的区别
#### InnoDB
技术特点：支持事务、行级锁定、外键

- 默认情况下，每个InnoDB表的`索引`和`数据`存储在同一文件下。
通过设置变量，使得每个InnoDB表有独特的存储文件
```
show variables like 'innodb_file_per_table';
set global innodb_file_per_table=1;    // 数据表有单独的数据和索引存储文件
```
- 数据存储按照主键的顺序排列，导致`写入操作较慢`
- 并发性较高

#### MyISAM
技术特点：索引顺序访问方法  支持全文索引  非事务安全  不支持外键  表级锁

- 三个文件：FRM文件存放表结构  MYD文件存放数据  MYI存放索引
- 数据存储按自然顺序排列，`写入操作较快`
- 并发性较低，表级锁
- 压缩机制。压缩的数据表是`只读`的，不能写信息

### 存储引擎的选择
- 网站大多数情况下”读和写“操作较多，适合选择MyISAM类型的
比如：dedecms、phpcms内容管理系统(新闻网站)、discuz论坛
- 网站对业务逻辑有一定要求（办公网站，商城）适合选择InnoDB
MySQL默认存储引擎是InnoDB

### 字段类型选择
- 尽量少的占据存储空间
TINYINT、SMALLINT
- 数据的整合最好固定长度
char(长度)
- 信息最好存储为整型
set集合类型、enm枚举类型

### 逆范式
如果涉及到多表查询，可以给表增加一个额外字段，提升查询速度，但是需要额外的维护工作

### 正确使用索引
1. 选择合适的列建立索引
   - where子句 、join子句、order by子句、group by子句 
   - 多列索引
        通过使用`复合索引`，查询的全部字段正好在索引里已经存在，就可以直接在索引中获取，而不需要在数据表中获取
        该查询速度非常快，效率高，称为”黄金索引“。但是索引本身需要消耗很大的空间资源，后期升级、维护困难
   - 连接查询
        在数据表中给外键/约束字段设置索引，可以提高`连表查询`的速度

2. 索引使用原则
   - 字段独立
   - 指定索引长度，尽可能使用短索引
   - 索引值尽量不相同，唯一值效果更好，大量重复效果很差
   - 最左前缀原则。最左列的值匹配，这样才能保证调用多列索引。
      即对于lname，fname，age三个字段使用多列索引之后，在执行的sql语句中，对于`(lname, fanme, age)`和`(lname, fname)`和`(lname)`这样的组合才会调用该多列索引。
   - 模糊查询左原则。使用like模糊差查询时，默认会使索引失效，除非使用`like 'abc%'`或者 `like 'abc_'`这样的形式，而不是`like '%abc%'`       
   - OR原则。在执行语句OR左右两侧的关联条件必须要具备索引，整体才会使用索引，否则不会用到索引。
        例如：select * from tablename where name = 'abc' or age > 10; 需要name和age都有索引，整体才会调用索引

3. 索引设计的依据
    - 被`频繁执行`的sql语句
    - `执行时间较长`的sql语句
    - `业务逻辑比较重要`的sql语句（比如：支付宝2小时内返现的业务逻辑）

### 如何确定索引前缀的长度呢？
`数据表的总记录数/不重复的索引数目 = 比值`
比值越接近于`1`或者`趋于稳定`，则说明选择性越好，不重复的值的行数越接近于总记录数
```
select count(*) / (count(distinct left(field, len)));
```

### 使用全文索引
普通sql语句模糊查询，不能使用全文索引
复合全文索引的使用
```
select * from article where match(title, body) against ('mysql');
```

### 开启查询缓存
```
show variables like "%query%"; //开启查询缓存，设置缓存大小
show status like "%Qcache%";//查看缓存空间状态
reset query cache;//清空缓存
```
- 当query_cache_size=0时，不能缓存
- 当数据表或者数据有`变动`时（增加、减少、修改），会引起`缓存失效`
- 当sql语句中有变动的信息，就不能使用缓存
   例如：事件信息now()、随机数rand()
- 相同结果的sql语句，如果语句中大小写、空格等有变化，会生成`多个缓存`
- 针对特殊语句，需要不进行缓存，需要在语句中加入 `sql_no_cache`
    例如：select sql_no_cache * from table where id = 2;

### 分表/分区
#### 数据表拆分后，需要考虑如何操作这些数据表
php-------------([手动/mysql]算法)------------数据表(分表)

手动算法：需要在php语言中设计逻辑操作，增加php语言的代码工作量
mysql算法：php不需要额外操作就可以像往常一样操作同一个数据表的不同分区

#### 创建一个"分表/分区"数据表
设计分区的字段，需要是`主键的一部分`
```
create table goods(
    id int auto_increament,
    name varchar(32) not null,
    ....
    ....
    primary key(id)
)engine=myisam charset=utf8 partition by key(id) partitions 10;     //创建一个有10个分区的goods表
```

#### 四种分区算法
- 求余：
    key：根据指定的`字段`进行分区设计
    hash：根据指定的`表达式`进行分区设计
- 条件：
    range：字段/表达式  符合某个`条件范围`的分区设计
    list：字段/表达式  符合某个`列表范围` 的分区设计
```
create table goods(
    id int auto_increament,
    name varchar(32) not null,
    pubdate datetime not null default '0000-00-00',
    ....
    primary key(id)
)engine=myisam charset=utf8 
partition by key(id) partitions 10;    //key分区算法

partition by hash(month(pubdate)) partitions 12;    //hash表达式分区，month()函数可以获取时间信息的“月份”信息

//partition by range (字段/表达式) (        //range表达式分区，根据年份分区
//    partition 分区名字  values less than (常量),
//    ...
//)
partition by range(year(pubdate)) (
    partitions 70hou  values less than(1980),
    partitions 80hou  values less than(1990),
    ...
);

//partition by list (字段/表达式) (        //list表达式分区，根据月份分区
//    partition 分区名字  values in (n1,n2,n3),
//    ...
//)
partition by list(month(pubdate)) (
    partitions spring  values in(3,4,5),
    partitions summer  values in(6,7,8),
    ...
);
```

#### 管理分区
- 求余（key、hash）算法管理
    增加分区：alter table 表名 add partition partitions 数量;
    减少分区：alter table 表名 `coalesce` partition 数量;
    `减少分区，数据要丢失`
- 条件（range、list）算法管理
    增加分区：alter table 表名 add partition(
        partition 分区名 values less than[in] (常量[列表]),
        ...
   )
- 减少分区：alter table 表名 drop partition 分区名;

### 架构(集群)设计
主从复制(读写分离)


### 慢查询日志
开启慢查询日志，设置时间阈值(set 后面没有global)
```
show variables like 'slow_query%';
set global slow_query_log = 1;

show variables like 'long_query%';
set long_query_time  = 2;
```
