> BenchMark，是CI的基准点组件，主要用于mark各种时间点、记录内存使用等参数，便于性能测试和追踪   
> 
> 只是用来计算程序运行消耗的时间和内存

## 属性
### 公共属性：$marker
只是用于所有基准标记的数组  

## 方法
### 公共方法：mark($name)
标记当前的时间点
```php
public function mark($name)
{
    $this->marker[$name] = microtime(TRUE);
}
```
使用流程如下：  
1. 标记一个起始点
2. 标记一个结束点
3. 使用elapsed_time方法计算时间差  

```php
// 标记一个起始点
$this->benchmark->mark('code_init');
// ... 代码主体
// 标记一个结束点
$this->benchmark->mark('code_end');
// 计算时间差
echo $this->benchmark->elapsed_time('code_start', 'code_end');
```


### 公共方法：elapsed_time($point1 = '', $point2 = '', $decimals = 4)
计算两个标记点之间的时差
```php
public function elapsed_time($point1 = '', $point2 = '', $decimals = 4)
{
    if ($point1 === '')
    {
        return '{elapsed_time}';
    }

    if ( ! isset($this->marker[$point1]))
    {
        return '';
    }

    if ( ! isset($this->marker[$point2]))
    {
        $this->marker[$point2] = microtime(TRUE);
    }

    return number_format($this->marker[$point2] - $this->marker[$point1], $decimals);
}
```

### memory_usage()
显示内存占用
```php
public function memory_usage()
{
    return '{memory_usage}';
}
```

> 参考链接：https://blog.csdn.net/zhihua_w/article/details/52846274