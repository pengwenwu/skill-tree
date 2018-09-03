通常意义上的静态变量是静态分配，他们的生命周期和程序的生命周期一样，只有在程序退出的时候才结束生命周期。  

php的静态变量可以分为：  
- 静态全局变量：php中的全局变量也可以理解为静态全局变量，因为除非明确unset释放，在程序运行过程中始终存在  
- 静态局部变量：即函数内定义的静态变量，函数在执行时对变量的操作会保持到下一次函数执行，直到程序终止  
- 静态成员变量：在类中定义的静态变量，和实例变量相对应，静态成员变量可以在所有实例中共享  

静态局部变量
```php
<?php
function test()
{
    static $b = 0;
    $b = $b + 1;
    echo $b;
    return $b;
}
test(); // 1
test(); // 2
test(); // 3
```

静态变量只能被初始化一次：  
```php
<?php
static $a = 0;

$a = 10;
var_dump($a); // 10

static $a = 0;
var_dump($a); // 10
```

静态成员变量
```php
<?php
class Foo
{
    public static $a = 1;
}

$foo1 = new Foo();
echo $foo1::$a; // 1

$foo2 = new Foo();
echo $foo2::$a; // 1

echo FOO::$a; // 1
```

修改静态成员变量
```php
<?php
class Foo
{
    public static $a = 1;
}

$foo1 = new Foo();
echo $foo1::$a; // 1

$foo1::$a = 2;
echo $foo1::$a; // 2

$foo2 = new Foo();
echo $foo2::$a; // 2

echo FOO::$a; // 2
```

> 参考链接：[《深入理解PHP内核 - 静态变量》](http://www.php-internals.com/book/?p=chapt03/03-04-static-var)