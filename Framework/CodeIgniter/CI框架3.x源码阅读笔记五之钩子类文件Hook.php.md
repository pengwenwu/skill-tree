CI框架可以实现在不修改系统核心文件的基础上来改变或增加系统的核心运行功能（如重写缓存、输出等），那就是Hooks。钩子是什么呢？可以这样理解：  
1. 钩子是一种事件驱动模式，它的核心自然是事件（CI框架中pre_system，pre_controller等都是特定的事件）
2. 既然是事件驱动，那么必然要包含最重要的两个步骤: (1)、事件注册。对于Hook而言，就是指Hook钩子的挂载。(2).事件触发。在特定的时间点call特定的钩子，执行相应的钩子程序。
3. 既然是事件驱动，那么也应该支持统一挂钩点的多个注册事件。
4. 启动Hook钩子之后，程序的流程可能会发生变化，且钩子之间可能有相互调用的可能性，如果处理不当，会有死循环的可能性。同时，钩子的启用使得程序在一定程度上变得复杂，难以调试。  

CI有这些挂钩点：  
- `pre_system`： 在系统执行的早期调用，这个时候只有 `基准测试类` 和 `钩子类` 被加载了， 还没有执行到路由或其他的流程。
- `pre_controller`: 在你的控制器调用之前执行，所有的基础类都已加载，路由和安全检查也已经完成。
- `post_controller_constructor`: 在你的控制器实例化之后立即执行，控制器的任何方法都还尚未调用。
- `post_controller`: 在你的控制器完全运行结束时执行。
- `display_override`: 覆盖 _display() 方法，该方法用于在系统执行结束时向浏览器发送最终的页面结果。 这可以让你有自己的显示页面的方法。注意你可能需要使用 $this->CI =& get_instance() 方法来获取 CI 超级对象，以及使用 $this->CI->output->get_output() 方法来 获取最终的显示数据。
- `cache_override`: 使用你自己的方法来替代 输出类 中的 _display_cache() 方法，这让你有自己的缓存显示机制。
- `post_system` 在最终的页面发送到浏览器之后、在系统的最后期被调用。

 CI中钩子的核心功能是由Hook组件完成的：  
- enabled: 钩子功能是否开启的标志。
- hooks :保存系统中启用的钩子列表。
- in_progress:之后我们会看到，这个标志位用于防止钩子之间的互相调用而导致的死循环。
- _construct是Hook组件的构造函数，这其中调用了_initialize来完成初始化的工作。
-  call_hook: 调用_run_hook来call指定的钩子程序。之前CodeIgniter.php中我们已经看到，call_hook是实际提供给外部调用的接口。
- _run_hook: 实际执行钩子程序的函数。

使用挂钩点：
```php
$hook['pre_controller'][] = array(
    'class'    => 'MyClass', // 调用的类名，这一项可以留空。
    'function' => 'MyMethod', // 调用的方法或函数的名称。
    'filename' => 'Myclass.php', // 包含你的类或函数的文件名。
    'filepath' => 'hooks', // 包含你的脚本文件的目录名。 注意： 你的脚本必须放在 application/ 目录里面，所以 filepath 是相对 application/ 目录的路径
    'params'   => array('beer', 'wine', 'snacks') // 传递给你脚本的任何参数，可选。
);
```

### 组件初始化（构造函数）
```php
public function __construct()
{
    // 初始化，获取config配置
    $CFG =& load_class('Config', 'core');
    log_message('info', 'Hooks Class Initialized');

    // 检测配置是否开启钩子
    if ($CFG->item('enable_hooks') === FALSE)
    {
        return;
    }

    // 获取钩子配置信息
    if (file_exists(APPPATH.'config/hooks.php'))
    {
        include(APPPATH.'config/hooks.php');
    }

    if (file_exists(APPPATH.'config/'.ENVIRONMENT.'/hooks.php'))
    {
        include(APPPATH.'config/'.ENVIRONMENT.'/hooks.php');
    }

    // If there are no hooks, we're done.
    if ( ! isset($hook) OR ! is_array($hook))
    {
        return;
    }

    $this->hooks =& $hook;
    $this->enabled = TRUE;
}
```

### call调用指定的钩子
```php
public function call_hook($which = '')
{
    /// 检查钩子是否启用，以及call的钩子是否被预定义
    if ( ! $this->enabled OR ! isset($this->hooks[$which]))
    {
        return FALSE;
    }

    // 检查同一个挂钩点是否启用了多个钩子
    if (is_array($this->hooks[$which]) && ! isset($this->hooks[$which]['function']))
    {
        foreach ($this->hooks[$which] as $val)
        {
            $this->_run_hook($val);
        }
    }
    else
    {
        $this->_run_hook($this->hooks[$which]);
    }

    return TRUE;
}
```

### run执行特定的钩子程序
```php
protected function _run_hook($data)
{
    // data为上述我们定义的调用方式
    if (is_callable($data))
    {
        is_array($data)
            ? $data[0]->{$data[1]}()
            : $data();

        return TRUE;
    }
    elseif ( ! is_array($data))
    {
        return FALSE;
    }

    // 防止重复调用
    if ($this->_in_progress === TRUE)
    {
        return;
    }

    // 设置文件路径
    if ( ! isset($data['filepath'], $data['filename']))
    {
        return FALSE;
    }

    $filepath = APPPATH.$data['filepath'].'/'.$data['filename'];

    if ( ! file_exists($filepath))
    {
        return FALSE;
    }

    // 设置类名和方法名
    $class		= empty($data['class']) ? FALSE : $data['class'];
    $function	= empty($data['function']) ? FALSE : $data['function'];
    $params		= isset($data['params']) ? $data['params'] : '';

    if (empty($function))
    {
        return FALSE;
    }

    $this->_in_progress = TRUE;

    // 调用方法
    if ($class !== FALSE)
    {
        // The object is stored?
        if (isset($this->_objects[$class]))
        {
            if (method_exists($this->_objects[$class], $function))
            {
                $this->_objects[$class]->$function($params);
            }
            else
            {
                return $this->_in_progress = FALSE;
            }
        }
        else
        {
            class_exists($class, FALSE) OR require_once($filepath);

            if ( ! class_exists($class, FALSE) OR ! method_exists($class, $function))
            {
                return $this->_in_progress = FALSE;
            }

            // Store the object and execute the method
            $this->_objects[$class] = new $class();
            $this->_objects[$class]->$function($params);
        }
    }
    else
    {
        function_exists($function) OR require_once($filepath);

        if ( ! function_exists($function))
        {
            return $this->_in_progress = FALSE;
        }

        $function($params);
    }

    $this->_in_progress = FALSE;
    return TRUE;
}
```