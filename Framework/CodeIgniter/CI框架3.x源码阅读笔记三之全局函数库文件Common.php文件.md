
# 全局函数库Common.php
Common.php文件定义了一系列的全局函数，具有最高的加载优先权 

`function_exists()`的使用是为了避免重复定义

```php
require_once(BASEPATH.'core/Common.php');
```

## is_php()
确定当前PHP版本是否等于或大于提供的值 

```php
if ( ! function_exists('is_php'))
{
    /**
     * Determines if the current version of PHP is equal to or greater than the supplied value
     *
     * @param    string
     * @return    bool    TRUE if the current version is $version or higher
     */
    function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}
```

## is_really_writable()
测试文件可写性 

```php
if ( ! function_exists('is_really_writable'))
{
    /**
     * Tests for file writability
     *
     * is_writable() returns TRUE on Windows servers when you really can't write to
     * the file, based on the read-only attribute. is_writable() is also unreliable
     * on Unix servers if safe_mode is on.
     *
     * @link    https://bugs.php.net/bug.php?id=54709
     * @param    string
     * @return    bool
     */
    function is_really_writable($file)
    {
        // 兼容linux/Unix和windows系统
        // 可以通过分隔符判断当前系统是linux，直接调用方法判断文件是否可写
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') OR ! ini_get('safe_mode')))
        {
            return is_writable($file);
        }

        // Windows系统
        if (is_dir($file))
        {
            // 如果是目录，则创建一个随机命名的文件
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            // 如果文件无法创建，则返回不可写
            // 这里fopen参数的mode多加了'b'，是强制使用二进制的意思
            if (($fp = @fopen($file, 'ab')) === FALSE)
            {
                return FALSE;
            }

            fclose($fp);
            // 删除刚才的文件
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        }
        elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
        {
            // 非文件或者文件无法打开
            return FALSE;
        }

        // 关闭句柄
        fclose($fp);
        return TRUE;
    }
}
```

## &load_class() 
这个函数充当单例。如果所请求的类不存在，则实例化并将其设置为静态变量。如果先前实例化了变量，则返回该变量。 

- `引用返回`：该函数返回的是一个class实例的引用，任何对该实例的改变，都会影响下一次函数的调用结果 
- 也是通过内部的`static`关键字进行缓存已经加载的类的实例，实现方式类似于单例模式 
- 优先查找APPPATH和BASEPATH，最后才从$directory中查找类。如果存在同名类，最终加载自定义的扩展类   

```php
<?php
function &load_class($class, $directory = 'libraries', $param = NULL)
{
    static $_classes = array();

    // 判断当前类是否存在，已存在，则返回
    if (isset($_classes[$class]))
    {
        return $_classes[$class];
    }

    $name = FALSE;

    // 首先查找本地 application/directory 文件夹
    // 然后是本机的 system/directory 文件夹
    // 优先使用app自定义类
    foreach (array(APPPATH, BASEPATH) as $path)
    {
        if (file_exists($path.$directory.'/'.$class.'.php'))
        {
            $name = 'CI_'.$class;

            if (class_exists($name, FALSE) === FALSE)
            {
                require_once($path.$directory.'/'.$class.'.php');
            }

            break;
        }
    }

    // 自定义的拓展类是否存在，加载
    if (file_exists(APPPATH.$directory.'/'.config_item('subclass_prefix').$class.'.php'))
    {
        $name = config_item('subclass_prefix').$class;

        if (class_exists($name, FALSE) === FALSE)
        {
            require_once(APPPATH.$directory.'/'.$name.'.php');
        }
    }

    // 未找到该类
    if ($name === FALSE)
    {
        // Note: We use exit() rather than show_error() in order to avoid a
        // self-referencing loop with the Exceptions class
        set_status_header(503);
        echo 'Unable to locate the specified class: '.$class.'.php';
        exit(5); // EXIT_UNK_CLASS
    }

    // 记录刚刚加载过的类
    is_loaded($class);

    // 实例化
    $_classes[$class] = isset($param)
        ? new $name($param)
        : new $name();
    return $_classes[$class];
}
```

## is_loaded
用来追踪被加载过的类 

```php
<?php
if ( ! function_exists('is_loaded'))
{
    /**
     * Keeps track of which libraries have been loaded. This function is
     * called by the load_class() function above
     *
     * @param    string
     * @return    array
     */
    function &is_loaded($class = '')
    {
        static $_is_loaded = array();

        if ($class !== '')
        {
            $_is_loaded[strtolower($class)] = $class;
        }

        return $_is_loaded;
    }
}
```

## get_config
记载主要的config.php文件 

这个函数允许我们抓取配置文件，即使是配置类还没有被实例化 

`类型约束`：php5可以使用类型约束，函数的参数可以指定必须为对象（在函数原型里面指定类的名字），接口，数组（php5.1起），回调callback（php5.4起）。 
自php7起，新增标量类型声明：字符串string，整数int，浮点数float，布尔值bool。一般不太会用到，除非是`依赖注入`的设计模式中 

需要注意的几点：
- 函数只加载主配置文件，不会加载其他配置文件（这意味着，如果添加了其他的配置文件，在框架预备完毕之前，不会读取你的配置文件）。在Config组件实例化之前，所有读取主配置文件的工作都由该函数完成 
- 该函数支持动态运行的过程中修改Config.php中的条目（配置信息只可能修改一次，因为该函数也有static变量做缓存，若缓存存在，则直接返回配置） 
- 会同时加载environment下的配置文件，即会覆盖先前查找的config.php中相同的属性 

```php
<?php
if ( ! function_exists('get_config'))
{
    /**
     * Loads the main config.php file
     *
     * This function lets us grab the config file even if the Config class
     * hasn't been instantiated yet
     *
     * @param    array
     * @return    array
     */
    function &get_config(Array $replace = array())
    {
        static $config;

        if (empty($config))
        {
            $file_path = APPPATH.'config/config.php';
            $found = FALSE;
            if (file_exists($file_path))
            {
                $found = TRUE;
                require($file_path);
            }

            // Is the config file in the environment folder?
            if (file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/config.php'))
            {
                require($file_path);
            }
            elseif ( ! $found)
            {
                set_status_header(503);
                echo 'The configuration file does not exist.';
                exit(3); // EXIT_CONFIG
            }

            // Does the $config array exist in the file?
            if ( ! isset($config) OR ! is_array($config))
            {
                set_status_header(503);
                echo 'Your config file does not appear to be formatted correctly.';
                exit(3); // EXIT_CONFIG
            }
        }

        // Are any values being dynamically added or replaced?
        foreach ($replace as $key => $val)
        {
            $config[$key] = $val;
        }

        return $config;
    }
}
```

## config_item
获取配置数组中具体的值 
```php
<?php
if ( ! function_exists('config_item'))
{
    /**
     * Returns the specified config item
     *
     * @param    string
     * @return    mixed
     */
    function config_item($item)
    {
        static $_config;

        if (empty($_config))
        {
            // references cannot be directly assigned to static variables, so we use an array
            $_config[0] =& get_config();
        }

        return isset($_config[0][$item]) ? $_config[0][$item] : NULL;
    }
}
```

## get_mimes
此函数返回从配置/ mimes.php MIME类型的数组 

```php
<?php
if ( ! function_exists('get_mimes'))
{
    /**
     * Returns the MIME types array from config/mimes.php
     *
     * @return    array
     */
    function &get_mimes()
    {
        static $_mimes;

        if (empty($_mimes))
        {
            if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/mimes.php'))
            {
                $_mimes = include(APPPATH.'config/'.ENVIRONMENT.'/mimes.php');
            }
            elseif (file_exists(APPPATH.'config/mimes.php'))
            {
                $_mimes = include(APPPATH.'config/mimes.php');
            }
            else
            {
                $_mimes = array();
            }
        }

        return $_mimes;
    }
}
```

## is_https
判断是佛通过加密访问 
```php
<?php
if ( ! function_exists('is_https'))
{
    /**
     * Is HTTPS?
     *
     * Determines if the application is accessed via an encrypted
     * (HTTPS) connection.
     *
     * @return    bool
     */
    function is_https()
    {
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        {
            return TRUE;
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return TRUE;
        }

        return FALSE;
    }
}
```

## is_cli
判断是否由命令行运行 
```php
<?php
if ( ! function_exists('is_cli'))
{

    /**
     * Is CLI?
     *
     * Test to see if a request was made from the command line.
     *
     * @return     bool
     */
    function is_cli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }
}
```

## show_error
错误处理程序 

这个函数允许我们调用异常类，使用application/views/errors/error_general.php下的标准错误模板显示错误 

此函数会将错误页面直接发送到浏览器并退出 
```php
<?php
if ( ! function_exists('show_error'))
{
    /**
     * Error Handler
     *
     * This function lets us invoke the exception class and
     * display errors using the standard error template located
     * in application/views/errors/error_general.php
     * This function will send the error page directly to the
     * browser and exit.
     *
     * @param    string
     * @param    int
     * @param    string
     * @return    void
     */
    function show_error($message, $status_code = 500, $heading = 'An Error Was Encountered')
    {
        $status_code = abs($status_code);
        if ($status_code < 100)
        {
            $exit_status = $status_code + 9; // 9 is EXIT__AUTO_MIN
            if ($exit_status > 125) // 125 is EXIT__AUTO_MAX
            {
                $exit_status = 1; // EXIT_ERROR
            }

            $status_code = 500;
        }
        else
        {
            $exit_status = 1; // EXIT_ERROR
        }

        // 记载异常类，都是通过该组件去管理错误
        $_error =& load_class('Exceptions', 'core');
        echo $_error->show_error($heading, $message, 'error_general', $status_code);
        exit($exit_status);
    }
}
```

## show_404
展示错误页面 

```php
<?php
if ( ! function_exists('show_404'))
{
    /**
     * 404 Page Handler
     *
     * This function is similar to the show_error() function above
     * However, instead of the standard error template it displays
     * 404 errors.
     *
     * @param    string
     * @param    bool
     * @return    void
     */
    function show_404($page = '', $log_error = TRUE)
    {
        $_error =& load_class('Exceptions', 'core');
        $_error->show_404($page, $log_error);
        exit(4); // EXIT_UNKNOWN_FILE
    }
}
```

## log_message
调用Log组件记录log信息 

```php
<?php
if ( ! function_exists('log_message'))
{
    /**
     * Error Logging Interface
     *
     * We use this as a simple mechanism to access the logging
     * class and send messages to be logged.
     *
     * @param    string    the error level: 'error', 'debug' or 'info'
     * @param    string    the error message
     * @return    void
     */
    function log_message($level, $message)
    {
        static $_log;

        if ($_log === NULL)
        {
            // references cannot be directly assigned to static variables, so we use an array
            $_log[0] =& load_class('Log', 'core');
        }

        $_log[0]->write_log($level, $message);
    }
}
```

## set_status_header
设置http头信息 

```php
<?php
if ( ! function_exists('set_status_header'))
{
    /**
     * Set HTTP Status Header
     *
     * @param    int    the status code
     * @param    string
     * @return    void
     */
    function set_status_header($code = 200, $text = '')
    {
        if (is_cli())
        {
            return;
        }

        if (empty($code) OR ! is_numeric($code))
        {
            show_error('Status codes must be numeric', 500);
        }

        // 此函数构造一个响应头。$stati为响应码与其响应说明
        if (empty($text))
        {
            is_int($code) OR $code = (int) $code;
            $stati = array(
                100    => 'Continue',
                101    => 'Switching Protocols',

                200    => 'OK',
                201    => 'Created',
                202    => 'Accepted',
                203    => 'Non-Authoritative Information',
                204    => 'No Content',
                205    => 'Reset Content',
                206    => 'Partial Content',

                300    => 'Multiple Choices',
                301    => 'Moved Permanently',
                302    => 'Found',
                303    => 'See Other',
                304    => 'Not Modified',
                305    => 'Use Proxy',
                307    => 'Temporary Redirect',

                400    => 'Bad Request',
                401    => 'Unauthorized',
                402    => 'Payment Required',
                403    => 'Forbidden',
                404    => 'Not Found',
                405    => 'Method Not Allowed',
                406    => 'Not Acceptable',
                407    => 'Proxy Authentication Required',
                408    => 'Request Timeout',
                409    => 'Conflict',
                410    => 'Gone',
                411    => 'Length Required',
                412    => 'Precondition Failed',
                413    => 'Request Entity Too Large',
                414    => 'Request-URI Too Long',
                415    => 'Unsupported Media Type',
                416    => 'Requested Range Not Satisfiable',
                417    => 'Expectation Failed',
                422    => 'Unprocessable Entity',

                500    => 'Internal Server Error',
                501    => 'Not Implemented',
                502    => 'Bad Gateway',
                503    => 'Service Unavailable',
                504    => 'Gateway Timeout',
                505    => 'HTTP Version Not Supported'
            );

            if (isset($stati[$code]))
            {
                $text = $stati[$code];
            }
            else
            {
                show_error('No status text available. Please check your status code number or supply your own message text.', 500);
            }
        }

        // php_sapi_name()方法可以获得PHP与服务器之间的接口类型
        if (strpos(PHP_SAPI, 'cgi') === 0)
        {
            header('Status: '.$code.' '.$text, TRUE);
        }
        else
        {
            $server_protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($server_protocol.' '.$code.' '.$text, TRUE, $code);
        }
    }
}
```

## _error_handler、_exception_handler、_shutdown_handler
错误处理机制 

```php
<?php
if ( ! function_exists('_error_handler'))
{
    /**
     * Error Handler
     *
     * This is the custom error handler that is declared at the (relative)
     * top of CodeIgniter.php. The main reason we use this is to permit
     * PHP errors to be logged in our own log files since the user may
     * not have access to server logs. Since this function effectively
     * intercepts PHP errors, however, we also need to display errors
     * based on the current error_reporting level.
     * We do that with the use of a PHP error template.
     *
     * @param    int    $severity
     * @param    string    $message
     * @param    string    $filepath
     * @param    int    $line
     * @return    void
     */
    function _error_handler($severity, $message, $filepath, $line)
    {
        $is_error = (((E_ERROR | E_COMPILE_ERROR | E_CORE_ERROR | E_USER_ERROR) & $severity) === $severity);

        // When an error occurred, set the status header to '500 Internal Server Error'
        // to indicate to the client something went wrong.
        // This can't be done within the $_error->show_php_error method because
        // it is only called when the display_errors flag is set (which isn't usually
        // the case in a production environment) or when errors are ignored because
        // they are above the error_reporting threshold.
        if ($is_error)
        {
            set_status_header(500);
        }

        // Should we ignore the error? We'll get the current error_reporting
        // level and add its bits with the severity bits to find out.
        if (($severity & error_reporting()) !== $severity)
        {
            return;
        }

        $_error =& load_class('Exceptions', 'core');
        $_error->log_exception($severity, $message, $filepath, $line);

        // Should we display the error?
        if (str_ireplace(array('off', 'none', 'no', 'false', 'null'), '', ini_get('display_errors')))
        {
            $_error->show_php_error($severity, $message, $filepath, $line);
        }

        // If the error is fatal, the execution of the script should be stopped because
        // errors can't be recovered from. Halting the script conforms with PHP's
        // default error handling. See http://www.php.net/manual/en/errorfunc.constants.php
        if ($is_error)
        {
            exit(1); // EXIT_ERROR
        }
    }
}
```

## remove_invisible_characters
删除不可见字符 

```php
<?php
if ( ! function_exists('remove_invisible_characters'))
{
    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     * 这可以防止夹空字符之间的ASCII字符，如java \ 0scrip
     * @param    string
     * @param    bool
     * @return    string
     */
    function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded)
        {
            $non_displayables[] = '/%0[0-8bcef]/';    // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';    // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127

        do
        {
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);

        return $str;
    }
}
```

## html_escape
返回HTML转义变量 

```php
<?php
if ( ! function_exists('html_escape'))
{
    /**
     * Returns HTML escaped variable.
     *
     * @param    mixed    $var        The input string or array of strings to be escaped.
     * @param    bool    $double_encode    $double_encode set to FALSE prevents escaping twice.
     * @return    mixed            The escaped string or array of strings as a result.
     */
    function html_escape($var, $double_encode = TRUE)
    {
        if (empty($var))
        {
            return $var;
        }

        if (is_array($var))
        {
            return array_map('html_escape', $var, array_fill(0, count($var), $double_encode));
        }

        return htmlspecialchars($var, ENT_QUOTES, config_item('charset'), $double_encode);
    }
}
```

## _stringify_attributes
在HTML标签中使用Stringify属性 

用于转换字符串、数组或对象的辅助函数的字符串的属性

```php
<?php
if ( ! function_exists('_stringify_attributes'))
{
    /**
     * Stringify attributes for use in HTML tags.
     *
     * Helper function used to convert a string, array, or object
     * of attributes to a string.
     *
     * @param    mixed    string, array, object
     * @param    bool
     * @return    string
     */
    function _stringify_attributes($attributes, $js = FALSE)
    {
        $atts = NULL;

        if (empty($attributes))
        {
            return $atts;
        }

        if (is_string($attributes))
        {
            return ' '.$attributes;
        }

        $attributes = (array) $attributes;

        foreach ($attributes as $key => $val)
        {
            $atts .= ($js) ? $key.'='.$val.',' : ' '.$key.'="'.$val.'"';
        }

        return rtrim($atts, ',');
    }
}
```

## function_usable 
函数可用 

```php
<?php
if ( ! function_exists('function_usable'))
{
    /**
     * Function usable
     *
     * @link    http://www.hardened-php.net/suhosin/
     * @param    string    $function_name    Function to check for
     * @return    bool    TRUE if the function exists and is safe to call,
     *            FALSE otherwise.
     */
    function function_usable($function_name)
    {
        static $_suhosin_func_blacklist;

        if (function_exists($function_name))
        {
            if ( ! isset($_suhosin_func_blacklist))
            {
                $_suhosin_func_blacklist = extension_loaded('suhosin')
                    ? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
                    : array();
            }

            return ! in_array($function_name, $_suhosin_func_blacklist, TRUE);
        }

        return FALSE;
    }
}
```

