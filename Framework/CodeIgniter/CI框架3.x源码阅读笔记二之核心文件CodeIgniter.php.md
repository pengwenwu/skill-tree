
# 核心文件CodeIgniter.php
文件地址: system/core/CodeIgniter.php 

## 加载框架常量、函数库以及框架初始化
### 加载框架常量constants.php文件
```php
<?php
if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/constants.php'))
{
    require_once(APPPATH.'config/'.ENVIRONMENT.'/constants.php');
}

require_once(APPPATH.'config/constants.php');
```
通过`ENVIRONMENT`常量去加载不同目录的constants.php，内容主要是一些`常量的定义` 

### 加载全局函数库Common.php
```php
<?php
require_once(BASEPATH.'core/Common.php');
```

### 进行全局变量安全处理
如果低于php5.4版本，将进行全局变量安全处理。 

```php
<?php
if ( ! is_php('5.4'))
{
    ini_set('magic_quotes_runtime', 0);

    if ((bool) ini_get('register_globals'))
    {
        $_protected = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_FILES',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            'GLOBALS',
            'HTTP_RAW_POST_DATA',
            'system_path',
            'application_folder',
            'view_folder',
            '_protected',
            '_registered'
        );

        $_registered = ini_get('variables_order');
        foreach (array('E' => '_ENV', 'G' => '_GET', 'P' => '_POST', 'C' => '_COOKIE', 'S' => '_SERVER') as $key => $superglobal)
        {
            if (strpos($_registered, $key) === FALSE)
            {
                continue;
            }

            foreach (array_keys($$superglobal) as $var)
            {
                if (isset($GLOBALS[$var]) && ! in_array($var, $_protected, TRUE))
                {
                    $GLOBALS[$var] = NULL;
                }
            }
        }
    }
}
```

### 自定义错误、异常和程序完成的函数
定义一个自定义错误处理程序，以便记录PHP错误

```php
<?php
set_error_handler('_error_handler');
set_exception_handler('_exception_handler');
register_shutdown_function('_shutdown_handler');
```

### 检查核心class是否被扩展
`$assign_to_config`是定义在入口文件Index.php中的配置数组，被注释掉了。 

这里的`subclass_prefix`配置默认项是在APPPATH/Config/config.php目录。 

即index.php文件中的`subclass_prefix`配置项具有优先权，会覆盖config中的配置。

```php
<?php
if (!empty($assign_to_config['subclass_prefix']))
{
    // 这里的get_config是全局函数，主要用于新增或替换原有配置
    get_config(array('subclass_prefix' => $assign_to_config['subclass_prefix']));
}
```

### 加载composer
```php
<?php
if ($composer_autoload = config_item('composer_autoload'))
{
    if ($composer_autoload === TRUE)
    {
        file_exists(APPPATH.'vendor/autoload.php')
            ? require_once(APPPATH.'vendor/autoload.php')
            : log_message('error', '$config[\'composer_autoload\'] is set to TRUE but '.APPPATH.'vendor/autoload.php was not found.');
    }
    elseif (file_exists($composer_autoload))
    {
        require_once($composer_autoload);
    }
    else
    {
        log_message('error', 'Could not find the specified $config[\'composer_autoload\'] path: '.$composer_autoload);
    }
}
```

## 加载核心类组件
### BenchMark->BM
指BenchMark，是CI的基准点组件，主要用于mark各种时间点、记录内存使用等参数，便于性能测试和追踪 

只是用来计算程序运行消耗的时间和内存 

```php
<?php
$BM =& load_class('Benchmark', 'core');
$BM->mark('total_execution_time_start');
$BM->mark('loading_time:_base_classes_start');
```

### 钩子类->EXT
Hooks钩子类 - 提供一种机制来扩展基本系统而不进行黑客攻击。

用于在不改变CI核心的基础上改变或者增加系统的核心运行功能。Hook钩子允许你在系统运行的各个挂钩点（hook point）添加自定义的功能和跟踪，如pre_system，pre_controller，post_controller等预定义的挂钩点。以下所有的$EXT->_call_hook("xxx"); 均是call特定挂钩点的程序（如果有的话）。 
```php
<?php
/*
 * ------------------------------------------------------
 *  Instantiate the hooks class
 * ------------------------------------------------------
 */
$EXT =& load_class('Hooks', 'core');

/*
 * ------------------------------------------------------
 *  Is there a "pre_system" hook?
 * ------------------------------------------------------
 */
$EXT->call_hook('pre_system');
```

### 配置类->CFG
Config配置管理组件。主要用于加载配置文件、获取和设置配置项等 
```php
<?php
$CFG =& load_class('Config', 'core');

// Do we have any manually set config items in the index.php file?
// 上文提到过，在index.php入口文件中，配置项具有优先权，会替换替他文件内的配置
if (isset($assign_to_config) && is_array($assign_to_config))
{
    foreach ($assign_to_config as $key => $value)
    {
        $CFG->set_item($key, $value);
    }
}
```

### 字符集设置相关扩展开启
bool extension_loaded ( string $name ) 
检查一个扩展是否已经加载。

```php
<?php
// 获取配置项里的charset字符集配置
// config类的构造函数，会去获取APP应用目录下的配置
$charset = strtoupper(config_item('charset'));
ini_set('default_charset', $charset);

// 检查mbstring扩展项是否开启
if (extension_loaded('mbstring'))
{
    define('MB_ENABLED', TRUE);
    // mbstring.internal_encoding is deprecated starting with PHP 5.6
    // and it's usage triggers E_DEPRECATED messages.
    @ini_set('mbstring.internal_encoding', $charset);
    // This is required for mb_convert_encoding() to strip invalid characters.
    // That's utilized by CI_Utf8, but it's also done for consistency with iconv.
    mb_substitute_character('none');
}
else
{
    define('MB_ENABLED', FALSE);
}

// 开启字符集转换扩展
// There's an ICONV_IMPL constant, but the PHP manual says that using
// iconv's predefined constants is "strongly discouraged".
if (extension_loaded('iconv'))
{
    define('ICONV_ENABLED', TRUE);
    // iconv.internal_encoding is deprecated starting with PHP 5.6
    // and it's usage triggers E_DEPRECATED messages.
    @ini_set('iconv.internal_encoding', $charset);
}
else
{
    define('ICONV_ENABLED', FALSE);
}

// 设置内部编码
if (is_php('5.6'))
{
    ini_set('php.internal_encoding', $charset);
}
```

### 加载兼容性特性包
`重写`系统组件的一些方法 

- mbstring 
    mb_strlen - 字符串长度 
    mb_strpos - 字符串查找 
    mb_substr - 字符串截取 

- hash 
    hash - 生成哈希值 
    hash_equals - 可防止时序攻击的字符串比较 
    hash_pbkdf2 - 生成所提供密码的 PBKDF2 密钥导出 

- password 
    password_hash - 创建密码的散列，兼容`crypt()`，PHP7.0.0后已废弃`salt` 
    password_get_info - 返回指定散列（hash）的相关信息 
    password_needs_rehash - 检测散列值是否匹配指定的选项 
    password_verify - 验证密码是否和散列值匹配

- standard 
    array_column - 返回数组中指定的一列 
    hex2bin - 转换十六进制字符串为二进制字符串 
    array_replace - 使用传递的数组替换第一个数组的元素 
    array_replace_recursive - 使用传递的数组递归替换第一个数组的元素 
    quoted_printable_encode - 将 8-bit 字符串转换成 quoted-printable 字符串 
```php
<?php
// 中文字符串处理
require_once(BASEPATH.'core/compat/mbstring.php');

// hash处理
require_once(BASEPATH.'core/compat/hash.php');

// 加密兼容处理
require_once(BASEPATH.'core/compat/password.php');

// 标准兼容处理
require_once(BASEPATH.'core/compat/standard.php');
```

### Utf8类->UNI
```php
<?php
$UNI =& load_class('Utf8', 'core');
```

### URI类
```php
<?php
$URI =& load_class('URI', 'core');
```

### 路由类->RTR
```php
<?php
$RTR =& load_class('Router', 'core', isset($routing) ? $routing : NULL);
```

### OUTPUT类->OUT
最终输出管理组件 
```php
<?php
$OUT =& load_class('Output', 'core');

// 判断是否有有效的缓存文件，有的话执行结束
if ($EXT->call_hook('cache_override') === FALSE && $OUT->_display_cache($CFG, $URI) === TRUE)
{
    exit;
}
```

### 安全类->SEC
为xss和csrf支持加载安全类 
```php
<?php
$SEC =& load_class('Security', 'core');
```

### 输入及过滤类->IN
用于获取输入以及表单验证 
```php
<?php
$IN    =& load_class('Input', 'core');
```

### 语言类->LANG
用于设置框架语言 
```php
<?php
$LANG =& load_class('Lang', 'core');
```

## 加载app应用控制器和本地system控制器
```php
<?php
// 加载本地system原始控制器
// 这里没有用load_class()；的原因是我们最终并不会直接使用该基类，都是针对继承后新的类
require_once BASEPATH.'core/Controller.php';

/**
* 定义get_instance();方法，通过调用CI_Controller::get_instance()可以实现单例化
* 调用此函数可方便以后直接取得当前应用控制器
* Reference to the CI_Controller method.
*
* Returns current CI instance object
*
* @return object
*/
function &get_instance()
{
    return CI_Controller::get_instance();
}

// 加载app应用的自定义控制器
if (file_exists(APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php'))
{
    require_once APPPATH.'core/'.$CFG->config['subclass_prefix'].'Controller.php';
}

// 为基准设置一个标记点，记录时间
$BM->mark('loading_time:_base_classes_end');
```

## 路由的设置与判断
会有下面几种情况认为是404： 
- 访问的文件不存在
- 请求的class不存在 
- 请求私有方法
- 请求原始基类
- 请求的方法不存在

```php
<?php
$e404 = FALSE;
$class = ucfirst($RTR->class);
$method = $RTR->method;

// 文件不存在
if (empty($class) OR ! file_exists(APPPATH.'controllers/'.$RTR->directory.$class.'.php'))
{
    $e404 = TRUE;
}
else
{
    // 加载该class文件
    require_once(APPPATH.'controllers/'.$RTR->directory.$class.'.php');

    // 该class不存在 || 请求私有方法 || 请求原始基类
    if ( ! class_exists($class, FALSE) OR $method[0] === '_' OR method_exists('CI_Controller', $method))
    {
        $e404 = TRUE;
    }
    elseif (method_exists($class, '_remap'))
    {
        $params = array($method, array_slice($URI->rsegments, 2));
        $method = '_remap';
    }
    // 请求的方法不存在
    elseif ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($class)), TRUE))
    {
        $e404 = TRUE;
    }
}

// 针对404的处理
if ($e404)
{
    if ( ! empty($RTR->routes['404_override']))
    {
        if (sscanf($RTR->routes['404_override'], '%[^/]/%s', $error_class, $error_method) !== 2)
        {
            $error_method = 'index';
        }

        $error_class = ucfirst($error_class);

        if ( ! class_exists($error_class, FALSE))
        {
            if (file_exists(APPPATH.'controllers/'.$RTR->directory.$error_class.'.php'))
            {
                require_once(APPPATH.'controllers/'.$RTR->directory.$error_class.'.php');
                $e404 = ! class_exists($error_class, FALSE);
            }
            // Were we in a directory? If so, check for a global override
            elseif ( ! empty($RTR->directory) && file_exists(APPPATH.'controllers/'.$error_class.'.php'))
            {
                require_once(APPPATH.'controllers/'.$error_class.'.php');
                if (($e404 = ! class_exists($error_class, FALSE)) === FALSE)
                {
                    $RTR->directory = '';
                }
            }
        }
        else
        {
            $e404 = FALSE;
        }
    }

    // Did we reset the $e404 flag? If so, set the rsegments, starting from index 1
    if ( ! $e404)
    {
        $class = $error_class;
        $method = $error_method;

        $URI->rsegments = array(
            1 => $class,
            2 => $method
        );
    }
    else
    {
        show_404($RTR->directory.$class.'/'.$method);
    }
}

if ($method !== '_remap')
{
    $params = array_slice($URI->rsegments, 2);
}
```

## 解析请求的类，并调用请求的方法
```php
<?php
// 新的钩子pre_controller
$EXT->call_hook('pre_controller');

// Mark a start point so we can benchmark the controller
$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_start');

// 实例化这个类
$CI = new $class();

// 新的钩子post_controller_constructor
$EXT->call_hook('post_controller_constructor');

// 调用方法
call_user_func_array(array(&$CI, $method), $params);

// Mark a benchmark end point
$BM->mark('controller_execution_time_( '.$class.' / '.$method.' )_end');

// 新的钩子post_controller
$EXT->call_hook('post_controller');
```

## 输出
```php
<?php
if ($EXT->call_hook('display_override') === FALSE)
{
    $OUT->_display();
}

/*
* ------------------------------------------------------
*  Is there a "post_system" hook?
* ------------------------------------------------------
*/
$EXT->call_hook('post_system');
```
