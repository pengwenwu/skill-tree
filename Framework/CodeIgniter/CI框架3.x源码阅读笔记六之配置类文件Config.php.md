首先对CI框架的配置管理类文件Config.php进行一个简要的类结构及说明：  
- config：所有的配置项都存储在$config数组中，item()方法取值也是从这里取
- is_loaded：是一个数组，用当前上下文加载过的文件路径做数组元素。如果路径存在，则说明以及加载过该配置文件， 无需重复加载  
- _config_path：默认值 array(APPPATH)，配置文件存储路径，程序循环加载
- __construct()：加载默认config.php中的配置。如果config["base_url']不存在，则重新根据当前$_SERVER中信息计算并设置
- load()：加载自定义配置文件
- item()：获取配置项
- slash_item()：获取配置项，并在结尾增加"/"
- set_item()：设置配置项
- base_url()、site_url()、system_url()、_uri_string()：主要用于URL辅助函数调用。system_url()已经弃用
  
主要完成以下几个主要功能：  
1. 加载配置文件
2. 获取配置项值
3. 设置配置项值（临时）
4. url路由处理

### 加载配置文件（__construct(), load()）
#### __construct()
```php
public function __construct()
{
    $this->config =& get_config();

    // 在config/config.php下有非必需配置项base_url, 如果没有值，则自动给他进行赋值
    if (empty($this->config['base_url']))
    {
        // The regular expression is only a basic validation for a valid "Host" header.
        // It's not exhaustive, only checks for valid characters.
        if (isset($_SERVER['HTTP_HOST']) && preg_match('/^((\[[0-9a-f:]+\])|(\d{1,3}(\.\d{1,3}){3})|[a-z0-9\-\.]+)(:\d+)?$/i', $_SERVER['HTTP_HOST']))
        {
            $base_url = (is_https() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']
                .substr($_SERVER['SCRIPT_NAME'], 0, strpos($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_FILENAME'])));
        }
        else
        {
            $base_url = 'http://localhost/';
        }

        $this->set_item('base_url', $base_url);
    }

    log_message('info', 'Config Class Initialized');
}
```
在Config组件实例化之前，所有的组配置文件的获取都是由get_config()函数来代理的。在Config组件实例化时，要将所有的配置存放到自己的私有变量$config中，便于之后的访问和处理：$this->config =& get_config();。

#### load()
```php
public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
{
    $file = ($file === '') ? 'config' : str_replace('.php', '', $file);
    $loaded = FALSE;

    // 默认值APPPATH
    foreach ($this->_config_paths as $path)
    {
        // 在默认配置目录以及指定环境的配置目录里查找
        foreach (array($file, ENVIRONMENT.DIRECTORY_SEPARATOR.$file) as $location)
        {
            $file_path = $path.'config/'.$location.'.php';
            // 判断是否加载过
            if (in_array($file_path, $this->is_loaded, TRUE))
            {
                return TRUE;
            }

            if ( ! file_exists($file_path))
            {
                continue;
            }

            include($file_path);

            if ( ! isset($config) OR ! is_array($config))
            {
                if ($fail_gracefully === TRUE)
                {
                    return FALSE;
                }

                show_error('Your '.$file_path.' file does not appear to contain a valid configuration array.');
            }

            if ($use_sections === TRUE)
            {
                $this->config[$file] = isset($this->config[$file])
                    ? array_merge($this->config[$file], $config)
                    : $config;
            }
            else
            {
                $this->config = array_merge($this->config, $config);
            }

            // 存储，表明已加载
            $this->is_loaded[] = $file_path;
            $config = NULL;
            $loaded = TRUE;
            log_message('debug', 'Config file loaded: '.$file_path);
        }
    }

    if ($loaded === TRUE)
    {
        return TRUE;
    }
    elseif ($fail_gracefully === TRUE)
    {
        return FALSE;
    }

    show_error('The configuration file '.$file.'.php does not exist.');
}
```
这是Config组件中较核心的方法之一，所有的参数都是可选参数，我们这里简单解释一下各形参的含义：  
- $file：需要加载的配置文件，可以包含后缀名也不可以不包含，如果未指定该参数，则默认加载Config.php文件；
- \$user_sections：是否为加载的配置文件使用独立的section，这么说可能还是不明白，试想，如果你定义了自己的配置文件，而你的配置文件中的配置项可能与Config.php文件中的配置项冲突，通过指定$section为true可以防止配置项的覆盖；
- $fail_gracefully：要load的配置文件不存在时的处理。Gracefully意为优雅的，如果该参数设置为true,则在文件不存在时只会返回false，而不会显示错误。

在不启用user_secitons的情况下，如果你的配置文件中有与主配置文件Config.php相同的键，则会覆盖主配置文件中的项；

### 获取配置项值（item()、slash_item()）
#### item()
```php
public function item($item, $index = '')
{
    if ($index == '')
    {
        return isset($this->config[$item]) ? $this->config[$item] : NULL;
    }

    return isset($this->config[$index], $this->config[$index][$item]) ? $this->config[$index][$item] : NULL;
}
```
item方法用于在配置中获取特定的配置项。注意，如果你在load配置文件的时候启用了use_sections，则在使用item()获取配置项的时候需要指定第二个参数，也就是加载的配置文件的文件名（不包含后缀）。

#### slash_item()
```php
public function slash_item($item)
{
    if ( ! isset($this->config[$item]))
    {
        return NULL;
    }
    elseif (trim($this->config[$item]) === '')
    {
        return '';
    }

    return rtrim($this->config[$item], '/').'/';
}
```
slash_item()实际上与item()方法类似，但他不会去用户的配置中寻找，并且，他返回的是主配置文件中的配置项，并在配置项最后添加反斜杠。

#### set_item()
```php
public function set_item($item, $value)
{
    $this->config[$item] = $value;
}
```

### url路由处理（site_url()、base_url()、_uri_string()、system_url()）
几个方法的区别：
```php
echo "site_url  : ",$this->config->site_url("index/hello"),"</br>";
//site_url : http://www.citest.com/index/hello.html
echo "base_url  : ",$this->config->base_url("index/hello"),"<br/>";
//base_url : http://www.citest.com/index/hello
echo "system_url: ",$this->config->system_url();
//system_url: http://www.citest.com/system/
```
我们可以通过输出的结果，看出它们之间的区别。site_url是添加了suffix，base_url则是没有添加suffix的url地址，而system_url这个东西很奇怪，是获取系统的url路径。但实际上，由于system路径并没有直接执行的脚本，所以这个方法的实际用途是什么，暂时不知。
