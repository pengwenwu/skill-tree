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

## 需要处理的问题
- [x] 项目目录划分
- [x] 如何接收复杂参数？
  - 如果用传统form-data方式，go无法处理
  - 查了一下，java默认接收方式也是json，通过requestbdy获取
  - 所以直接统一使用json提交，但是无法给默认参数，只能在实例化的时候给默认参数
- [x] 访问日志处理
  - [x] logrus
    - [x] 日志切割
- [x] 统一返回格式
- [x] 状态码处理
- [ ] mysql业务
  - [x] 支持不定查询字段
    - [ ] 查询接口是map\[string\]interface{}是无法使用的
  - [x] 获取上一次创建id
  - [ ] 批量插入
  - [ ] 无法合并参数默认值，比如添加item初始状态（不支持）
- [x] 中间件鉴权
- [x] 多数据库连接
  - [x] 主从分库 
  - [x] 跨库可以通过tablename指定库名
  - [ ] 未测试出连接池的开启的效果
- [x] 区分测试生产环境配置
  - [x] viper
  - [x] 热更新
- [x] 数据库迁移migration
  - [x] golang-migrate
  - [ ] 无法多数据库区公用一个版本
- [ ] rabbitmq封装
- [ ] 重启
- [ ] docker部署
- [ ] 业务日志处理
- [ ] 错误异常处理
- [ ] mq消息处理