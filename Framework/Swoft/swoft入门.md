> 官方文档：https://doc.swoft.org/  

## 基础信息
### 环境搭建
#### Docker 安装 Swoft
- 下载官方docker镜像
  ```bash
  docker pull swoft/swoft
  ```

- 启动
  ```bash
  docker run -d -p 80:80 --name swoft swoft/swoft
  ```

- 运行 Swoft  
  使用 docker 安装 swoft, 默认已经执行 php bin/swoft start 命令来启动 swoft. 访问 http://localhost 验证是否安装成功

- 本地虚拟机挂载  
  首先将swoft git clone到本地，然后再当前目录里执行命令：
  ```bash
  docker run -d -p 80:80 -v "$PWD":/my_swoft --name swoft swoft/swoft
  ```
  

#### 开发准备
- PHPStorm 安装 PHP Annotations 插件优化注解使用

### 快速起步
#### env环境配置
在docker项目里执行`composer install`，并复制`.env.example`重命名为`.env`

## 框架核心
### 生命周期

