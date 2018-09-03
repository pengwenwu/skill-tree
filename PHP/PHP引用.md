### 官方文档
1. [引用是什么](http://www.php.net/manual/zh/language.references.whatare.php)  
2. [引用不是什么](http://php.net/manual/zh/language.references.arent.php)  
3. [引用做什么](http://www.php.net/manual/zh/language.references.whatdo.php)  
4. [引用传递](http://php.net/manual/zh/language.references.pass.php)  
5. [引用返回](http://php.net/manual/zh/language.references.return.php)   

### php引用  
#### 1. 变量的引用
php的引用允许两个变量指向同一个内容  
```php
<?php
$a = 10;
$b =& $a;
echo $a, $b; // 10, 10

$b = 20;
echo $a, $b; // 20, 20
```

#### 2. 函数的引用传递
```php
<?php
function foo(&$var)
{
    $var++;
}

$a=5;
foo($a); // 6
foo($a); // 7

echo $a; // 7
```

#### 3. 函数的引用返回
```php
<?php
function &test()
{
    static $b = 0;//申明一个静态变量
    $b = $b + 1;
    echo $b;
    return $b;
}

$a = test();//这条语句会输出　$b的值　为１
$a = 5;
$a = test();//这条语句会输出　$b的值　为2

$a =& test();//这条语句会输出　$b的值　为3
$a = 5;
$a = test();//这条语句会输出　$b的值　为6
```
没有加&, 跟普通的函数调用没有区别。  

而引用返回的作用，相当于把$b的内存地址返回，赋值给$a，使得$a, $b的内存地址指向同一个地方，即相当于执行了($a =& $b;)  

更多的是使用在对象中：  
```php
<?php
class Foo
{
    public $value = 42;

    public function &getValue()
    {
        return $this->value;
    }
}

$obj = new Foo;
$myValue = &$obj->getValue();
echo $myValue; // 42;

$obj2 = new Foo;
$obj2->value = 2;
$myValue =& $obj2->getValue();
echo $myValue; // 2
```

### 写时复制
php一个比较重要的内部机制是写时复制
```php
<?php
$a = 10;
$b = $a; // 此时$b, $a 指向同一地方

$b = 20; // 在写入时，才会给$b 额外分配存储空间
```

### 性能优化
本来之前了解的，使用引用传递能够提高运行效率，本机测试也是能提高30%左右。不过看这篇文章，介绍引用坑大于利，所以不再推荐。文章地址：https://zhuanlan.zhihu.com/p/35107602  

> 参考文章：https://www.cnblogs.com/xiaochaohuashengmi/archive/2011/09/10/2173092.html