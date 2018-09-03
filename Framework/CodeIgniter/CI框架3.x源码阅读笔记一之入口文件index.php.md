> 当前框架版本define('CI_VERSION', '3.0.2');  

# 入口文件index.php
```php
<?php
# 对比项目跟原框架增加下列代码
include_once dirname(__FILE__) . '/../etc/environment.php';
...
...
...
include_once dirname(__FILE__) . '/../vendor/autoload.php';
include_once dirname(__FILE__) . '/../etc/load_all.php';
```
主要完成下列工作：

## 加载环境配置文件
通过environment文件判断并设置当前的环境  
```php
<?php
include_once dirname(__FILE__) . '/../etc/environment.php';
```
- 通过修改apache或nginx配置，设置环境变量
```bash
# apache
# SetEnv key=value
SetEnv CI_ENV development_beta

# nginx
# key value
fastcgi_param CI_ENV beta;
```

- 在通过`cli`运行时，通过`$_SERVER['argv']`获取传递给脚本的参数`数组`，第一个肯定为当前脚本名。  

## 设置报警级别
通过不同的环境变量，设置不同级别的报警。  
CI默认会有三个级别：development（开发），testing（测试），production（生产）
```php
<?php
switch (ENVIRONMENT)
{
 case 'development':
  error_reporting(E_ALL & ~E_NOTICE);
  ini_set('display_errors', 1);
     break;
    case 'development_beta':
        error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
        ini_set('display_errors', 0);
        break;
 case 'testing':
 case 'production':
  ini_set('display_errors', 0);
  if (version_compare(PHP_VERSION, '5.3', '>='))
  {
   error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
  }
  else
  {
   error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
  }
 break;

 default:
  header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
  echo 'The application environment is not set correctly.';
  exit(1); // EXIT_ERROR
}
```

## 配置系统、应用、视图等程序目录
```php
<?php
// 定义系统目录名称
$system_path = '../system';

// 定义应用目录名称
$application_folder = '../application';

// 视图文件存放目录
// 如果为空，则默认到应用程序文件夹内的标准位置
$view_folder = '';
```

## cli目录设置
bool chdir ( string $directory )  
将 PHP 的当前目录改为 directory  

string dirname ( string $path )  
给出一个包含有指向一个文件的全路径的字符串，本函数返回去掉文件名后的目录名。  

string realpath ( string $path )  
realpath() 扩展所有的符号连接并且处理输入的 path 中的 '/./', '/../' 以及多余的 '/' 并返回规范化后的绝对路径名。返回的路径中没有符号连接，'/./' 或 '/../' 成分。  


STDIN、STDOUT、STDERR是PHP以 CLI（Command Line Interface）模式运行而定义的三个常量，这三个常量类似于Shell的stdin,stdout,stdout,分别是PHP CLI模式下的标准输入、标准输出和标准错误流。  

这三行代码是为了保证命令行模式下，CI框架可以正常运行
```php
<?php
if (defined('STDIN'))
{
    chdir(dirname(__FILE__));
}
```

## 系统、应用、视图等目录的正确性验证
### 系统(system)文件目录的正确性验证  
用于校验生成system系统文件目录, 得到规范化的绝对路径名  
```php
<?php
if (($_temp = realpath($system_path)) !== FALSE)
{
    $system_path = $_temp.'/';
}
else
{
    // 确保后面有斜线
    $system_path = rtrim($system_path, '/').'/';
}

// 如果$system_path所指向的文件目录不存在，则die
if ( ! is_dir($system_path))
{
    header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
    echo 'Your system folder path does not appear to be set correctly. Please open the following file and correct this: '.pathinfo(__FILE__, PATHINFO_BASENAME);
    exit(3); // EXIT_CONFIG
}
```

### 定义主要的路径常量
mixed pathinfo ( string $path [, int $options = PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME ] )  
pathinfo() 返回一个关联数组包含有 path 的信息。返回关联数组还是字符串取决于 options。  

```php
<?php
// The name of THIS file
define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

// Path to the system folder
define('BASEPATH', str_replace('\\', '/', $system_path));

// Path to the front controller (this file)
define('FCPATH', dirname(__FILE__).'/');

// Name of the "system folder"
define('SYSDIR', trim(strrchr(trim(BASEPATH, '/'), '/'), '/'));
```

### 应用(application)文件目录的正确性验证  
```php
<?php
if (is_dir($application_folder))
{
    if (($_temp = realpath($application_folder)) !== FALSE)
    {
        $application_folder = $_temp;
    }

    define('APPPATH', $application_folder.DIRECTORY_SEPARATOR);
}
else
{
    if ( ! is_dir(BASEPATH.$application_folder.DIRECTORY_SEPARATOR))
    {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your application folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
        exit(3); // EXIT_CONFIG
    }

    define('APPPATH', BASEPATH.$application_folder.DIRECTORY_SEPARATOR);
}
```

### 视图(view)文件目录的正确性验证  
```php
<?php
if ( ! is_dir($view_folder))
{
    if ( ! empty($view_folder) && is_dir(APPPATH.$view_folder.DIRECTORY_SEPARATOR))
    {
        $view_folder = APPPATH.$view_folder;
    }
    elseif ( ! is_dir(APPPATH.'views'.DIRECTORY_SEPARATOR))
    {
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'Your view folder path does not appear to be set correctly. Please open the following file and correct this: '.SELF;
        exit(3); // EXIT_CONFIG
    }
    else
    {
        $view_folder = APPPATH.'views';
    }
}

if (($_temp = realpath($view_folder)) !== FALSE)
{
    $view_folder = $_temp.DIRECTORY_SEPARATOR;
}
else
{
    $view_folder = rtrim($view_folder, '/\\').DIRECTORY_SEPARATOR;
}

define('VIEWPATH', $view_folder);
```

## 加载composer
各种包的加载
```php
<?php
include_once dirname(__FILE__) . '/../vendor/autoload.php';
```

## 其余初始化的文件加载
包括一些监控、报警、调用链追踪
```php
<?php
include_once dirname(__FILE__) . '/../etc/load_all.php';
```

## 加载核心文件
```php
<?php
require_once BASEPATH.'core/CodeIgniter.php';
```

> defined() - 检查某个名称的常量是否存在  
> get_loaded_extensions() - 返回所有编译并加载模块名的 array  
> get_defined_functions() - 返回所有已定义函数的数组  
> get_defined_vars() - 返回由所有已定义变量所组成的数组  