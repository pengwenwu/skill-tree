## 分层
```
|—— app
|   |—— api
|   |   |—— http
|   |   |   |—— v1
|   |   |   |—— v2
|   |—— cmd
|   |   |—— main.go
|   |—— conf
|   |—— dao
|   |—— model
|   |—— server
|   |   |—— http
|   |   |—— grpc
|   |—— service
|   |   |- v1
|   |   |- v2
|   |—— middleware
|   |   |—— jwt
|—— conf
|—— library

```

- api：类似controller层，请求入口  
- cmd：放main.go和配置文件, 作为启动入口
- config：放配置文件对应的golang struct, 使用的是toml  
- dao：data access object, 数据库访问方法, redis, memcache访问方法, 还有一些RPC调用也放在这里面
- server：做一些初始化工作，主要是提供协议转换, 聚合. 逻辑还是再service层做（将参数校验也下沉到service层去做）  
- service：业务逻辑处理  

## 应用
### 路由创建
```go
// Default 已经连接了 Logger 和 Recovery 中间件
r := gin.Default()
```

```go
// 创建一个默认的没有任何中间件的路由
r := gin.New()
// 全局中间件

// Logger 中间件将写日志到 gin.DefaultWriter ,即使你设置 GIN_MODE=release 。
// 默认 gin.DefaultWriter = os.Stdout
r.Use(gin.Logger())

// Recovery 中间件从任何 panic 恢复，如果出现 panic，它会写一个 500 错误。
r.Use(gin.Recovery())

// 每个路由的中间件, 你能添加任意数量的中间件
r.GET("/benchmark", MyBenchLogger(), benchEndpoint)
```

### 静态文件服务
```go
// 获取当前文件的相对路径
router.Static("/assets", "./assets")
//
router.StaticFS("/more_static", http.Dir("my_file_system"))
// 获取相对路径下的文件
router.StaticFile("/favicon.ico", "./resources/favicon.ico")
```

## 功能说明
- [x] 项目目录划分
- [x] 如何接收复杂参数？
  - 如果用传统form-data方式，go无法处理
  - 查了一下，java默认接收方式也是json，通过requestbdy获取
  - 所以直接统一使用json提交，但是无法给默认参数，只能在实例化的时候给默认参数
- [x] 统一返回格式
- [x] 状态码处理
- [x] mysql业务
  - [x] 支持不定查询字段(已废弃)
    - [x] 查询接口是map\[string\]interface{}是无法使用的
  - [x] 获取上一次创建id
  - [x] 批量插入
  - [x] 无法合并参数默认值，比如添加item初始状态（不支持，可以通过初始化方法处理）
  - [x] 未获取数据的状态码处理
  - [x] 协程并发读读取多个数据库
  - [x] 批量更新(不支持类似CI那种updateBatch方法)
- [x] 中间件鉴权
- [x] 多数据库连接
  - [x] 主从分库 
  - [x] 跨库可以通过tablename指定库名
  - [ ] 未测试出连接池的开启的效果
- [x] 区分测试生产环境配置
  - [x] viper
  - [x] 热更新
- [ ] 数据库迁移migration
  - [x] golang-migrate
  - [ ] 无法多数据库区公用一个版本
- [ ] rabbitmq封装
  - [x] 消费者处理
  - [x] 生产者处理
  - [x] 消息绑定处理
  - [ ] 生产者连接池(初始化无需产生新的连接)
- [x] mq消息处理
  - [x] SyncSkuInsert（批量处理）
  - [x] SyncSkuUpdate
  - [x] SyncIemInsert
  - [x] SyncIemUpdate
- [x] 日志处理（zap OR logrus? 选择zap的优势是性能更高）
  - [x] 访问日志
    - [x] 日志切割（按文件大小lumberjack，日期file-rotatelogs）
  - [x] 业务日志
    - [x] 区分错误级别
- [ ] docker部署
- [x] 平滑重启(优雅关闭服务endless)
  - [x] 经测试，平滑重启无法解决mq消费者执行到一半的问题。同理，php里也有这个问题，一般可以通过mq的ack机制去做处理
- [x] 原有bug/待优化
  - [x] 查询商品列表接口，只返回item_id，会导致查询某一个sku的条码，返回了全部sku
  - [x] mq消息发送重复（由于在dao层处理发送消息，会出现同时调用多个dao层），比如删除商品，即发送了同步item消息，也发送了同步sku消息，回调处理方法差不多
- [ ] 多版本
- [ ] 协程mq消费者如何优雅退出
- [ ] 入口文件调整
- [x] qps性能对比swoft
- [x] makefile构建


## gin和swoft框架性能对比
1. 单个商品信息查询，所有关联表

| 框架 | 单次获取商品详情 | ab压测获取详情 | 单次添加商品 | ab压测添加商品 |
| - | - | - | - | - |
| swoft| 20ms | qps800左右，性能急剧下滑到110ms左右| 160ms | qps150左右，响应时间上升到350ms|
|gin | 15ms | qps920左右，响应时间108ms | 20ms | qps560左右，响应时间123ms|

ab压测报告：  
**swoft（获取商品详情）**  
1000请求并发10，响应时间12ms左右，qps427  
![](http://pic.pwwtest.com/rdo21y.png)  

1000请求并发100已经有7%的失败率，响应时间也升到110ms左右，qps858  
![](http://pic.pwwtest.com/0Mn6MK.png)

1000请求并发70有1%的失败率，响应时间升到85ms左右,qps803

下面是50并发的记录  
![](http://pic.pwwtest.com/NPxnOH.png)

**swoft添加商品**  
1000请求并发10，响应时间170ms，qps57
![](http://pic.pwwtest.com/M5qMDW.png)

1000请求并发50，响应时间340ms，qps146
![](http://pic.pwwtest.com/Iw1Oey.png)

1000请求并发70，响应时间460ms，qps150
![](http://pic.pwwtest.com/m3swD1.png)

**gin查看商品**  
1000请求80并发，响应时间97ms，qps820
![](http://pic.pwwtest.com/OO2iqw.png)

10000请求100并发，响应时间108ms，qps920
![](http://pic.pwwtest.com/0fqqGu.png)

10000请求1000并发，响应时间1392ms，qps710
![](http://pic.pwwtest.com/fuXrbc.png)

**gin添加商品**
1000请求70并发，响应时间123ms，qps565
![](http://pic.pwwtest.com/UCqIue.png)

1000请求100并发，本地请求响应时间560，qps180。请求测试机，数据库没扛住
![](http://pic.pwwtest.com/VgUp7r.png)