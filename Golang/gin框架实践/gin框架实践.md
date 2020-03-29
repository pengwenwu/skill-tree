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
- 结构分层(app/admin/main/credit/http/jury)
  - api
  - service
  - model
    - param
    - model
    - item_model
    - common
- 中间件鉴权
- 统一返回格式
- 错误异常处理
- 状态码处理
- 支持不定查询字段
- 主从分库
- 多数据库连接