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