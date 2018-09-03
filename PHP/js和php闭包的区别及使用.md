### 匿名函数
> 如果只是省去函数名，单纯的当作一个函数式方法返回，只能称为匿名函数（闭包需要将匿名函数当作结果返回），比如：  

```JavaScript
// js
var foo = function(x, y) {
    return x + y ;
};
console.log(foo(1, 2));  // 3
```

```php
<?php
// php
$foo = function($a, $b) {
    return $a + $b;
}; // 一定要加分号
echo $foo(1, 2); // 3
```

### 闭包  
> 闭包通常是用来创建内部变量，使得这些变量不得被外部随意修改，而只能通过指定的函数接口去修改  

#### js闭包  
这里举一个阮老师博客里的例子，[阮老师博客：学习Javascript闭包（Closure）](http://www.ruanyifeng.com/blog/2009/08/learning_javascript_closures.html?20120612141317#comments)  

##### js基础
参考链接：[深入理解JS中声明提升、作用域（链）和`this`关键字](https://github.com/creeperyang/blog/issues/16)  

- js比较特殊的一点是：函数内部可以直接读取到全局变量（对于阮老师的这句话不是很能理解，大概是想表达的意思：父作用域的变量可以在子作用域直接访问，而不需要去声明访问真正的全局变量？） 
    - 大部分语言，变量都是先声明在使用，而对于js，具有声明提升的特性（不管在哪里声明，都会在代码执行前处理）
    - 函数和变量的声明总是会隐式地被移动到当前作用域的顶部，函数的声明优先级高于变量的声明
    - var 会在当前作用域声明一个变量，而未声明的变量，会隐式地创建一个全局变量  

```JavaScript
// 声明提升
console.log(a);  // 1, 未报错
var a = 1;
```

```JavaScript
// 上文链接中的例子
function testOrder(arg) {
    console.log(arg); // arg是形参，不会被重新定义
    console.log(a); // 因为函数声明比变量声明优先级高，所以这里a是函数
    var arg = 'hello'; // var arg;变量声明被忽略， arg = 'hello'被执行
    var a = 10; // var a;被忽视; a = 10被执行，a变成number
    function a() {
        console.log('fun');
    } // 被提升到作用域顶部
    console.log(a); // 输出10
    console.log(arg); // 输出hello
}; 
testOrder('hi');
/* 输出：
hi 
function a() {
        console.log('fun');
    }
10 
hello 
*/
```

```JavaScript
// 全局作用域
var foo = 42;
function test() {
    // 局部作用域
    foo = 21;
}
test();
foo; // 21
```

```JavaScript
// 全局作用域
foo = 42;
function test() {
    // 局部作用域
    var foo = 21;
}
test();
foo; // 42
```

- js变量的查找是从里往外的，直到最顶层（全局作用域），并且一旦找到，即停止向上查找。所有内部函数可以访问函数外部的变量，反之无效  

```JavaScript
function foo(a) {
    var b = a * 2;
    function bar(c) {
        console.log(a, b, c);
    }
    bar(b * 3);
}
foo(2);
```

```JavaScript
function foo() {
    var a = 1;
}
console.log(a);  //a is not defined
```

```JavaScript
function foo1() {
    var num = 0;
    addNum = function() {  // 这里未通过var去声明，默认是全局变量
        num += 1;
    };
    function foo2() {
        console.log(num);
    }
    return foo2;
}
var tmp = foo1();
tmp();  // 0

addNum();
tmp(); // 1
```

> 这里第二次调用foo2函数，foo1函数的局部变量num并没有被初始化为0，说明打印的是内存中的num。正常函数在每次调用结束后都会销毁局部变量，在重新调用的时候会再次声明变量；而这边没有重新声明的原因是：把foo2函数赋值给了一个全局变量tmp，导致foo2函数一直存在内存中，而foo2函数依赖于foo1函数存在，所以foo1函数也存在内存中，并没有被销毁，所以foo1的局部变量也是存在内存中。  

- `this`的上下文基于函数调用的情况。和函数在哪定义无关，而和函数怎么调用有关。
    - 在全局上下文（任何函数以外），this指向全局对象(windows)
    - 在函数内部时，this由函数怎么调用来确定
        - 当函数作为对象方法调用时，this指向该对象  

下面是阮老师博客里的两个思考题：  

```JavaScript
var name = "The Window";
var object = {
　　name : "My Object",
　　getNameFunc : function(){
　　　　return function(){
　　　　　　return this.name;
　　　　};
　　}
};
alert(object.getNameFunc()()); // The Window
```

```JavaScript
var name = "The Window";
var object = {
    name : "My Object",
　　getNameFunc : function(){
　　    var that = this;
　　　　return function(){
　　　　    return that.name;
　　　　};
　　}
};
alert(object.getNameFunc()());// My Object
```

> `this`的作用域好像一直是个比较奇怪的东西，对于上面两个例子，我的理解是：第一个例子，是在方法里调用的`this`，而这个`this`并没有`声明`，会隐式地创建一个全局变量，所以调用的全局的name；第二个，调用的`that`的时候，会向顶级链式查找是否声明`that`，而这个that有this赋值，这里的this又是通过对象方法调用，则该this指向这个object对象，所有最终调用的是object作用域内的name。不知道这么理解是不是有问题，还望大神指正。  

**那其实js闭包的主要目的：访问函数内部的局部变量，即延长作用域链**  
参考链接：[js闭包MDN文档](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Closures)  

#### php闭包
##### php回调函数
[mixed call_user_func ( callable $callback [, mixed $parameter [, mixed $... ]] )](http://php.net/manual/zh/function.call-user-func.php)    
[mixed call_user_func_array ( callable $callback , array $param_arr )](http://php.net/manual/zh/function.call-user-func-array.php)  

这两个函数都是把第一个参数作为回调函数d调用，后面接收参数，区别就是第二个函数第二参数接收数组；在使用上唯一的区别就是，`call_user_func`函数无法接收`引用传递`; 个人觉得同样是使用，call_user_func 相比call_user_func_array完全可以被替代，不知道是不是有一些性能上的优势。具体使用样例，请参考官方文档。  

```php
<?php
// 引用传递
function foo(&$a, &$b) {
    $a ++;
    $b --;
};
$a = $b = 10;
call_user_func_array('foo', [&$a, &$b]);
echo $a."\n", $b; // 11, 9
```

##### 基本用法
基本用法，跟js的闭包类似  
- 普通调用  

```php
<?php
global $tmp = 'hello world';
function foo() {
    var_dump(global $tmp);
}
foo(); // null, 函数内部无法直接调用上级作用域的变量，除非声明为全局变量
```

```php
<?php
$foo1 = function() {
    $a = 10;
    $foo2 = function() {
        var_dump($a);
    };
    return $foo2;
};
$tmp = $foo1();
$tmp();  // null，原因同上 
```

- php想要能够获取上级作用域的变量，需要通过use传递  

```php
<?php
$foo1 = function () {
    $a = 10;
    $foo2 = function () use ($a) {
        var_dump($a);
        $a ++;
    };
    $foo2();
    return $foo2;
};
$tmp = $foo1();
$tmp();  // 10, 10,  use并不能实际改变变量的值，只是值传递
```

```php
<?php
$foo1 = function () {
    $a = 10;
    $foo2 = function () use (&$a) {
        var_dump($a);
        $a ++;
    };
    $foo2();
    return $foo2;
};
$tmp = $foo1();
$tmp();  // 10, 11,  通过值传递改变变量的值
```
  
- 下面两段代码的区别，不是很明白，望大佬指点，为什么后一个值传递就可以获取到已经改变后变量的值。好像都是在调用方法之前，已经执行过变量的递增了吧？  

```php
<?php
// 值传递
$foo1 = function () {
    $a = 10;
    $foo2 = function () use ($a) {
        var_dump($a);
    };
    $a ++;
    return $foo2;
};
$tmp = $foo1();
$tmp();  // 10
```

```php
<?php
// 引用传递
$foo1 = function () {
    $a = 10;
    $foo2 = function () use (&$a) {
        var_dump($a);
    };
    $a ++;
    return $foo2;
};
$tmp = $foo1();
$tmp();  // 11
```

- 正确使用  

```php
<?php
// 值传递
$foo = function () {
    $a = 10;
    $foo2 = function ($num) use ($a) {
        var_dump($num + $a);
    };
    return $foo2;
};
$tmp = $foo();
$tmp(100); // 110
```

```php
<?php
// 引用传递
$foo = function () {
    $a = 10;
    $foo2 = function ($num) use (&$a) {
        var_dump($num + $a);
        $a ++;
    };
    return $foo2;
};
$tmp = $foo();
$tmp(100); // 110
$tmp(100); // 111
$tmp(100); // 112  跟js类似，保证变量常驻内存
```

##### php Closure 类
- [Closure::bind — 复制一个闭包，绑定指定的$this对象和类作用域](http://php.net/manual/zh/closure.bind.php)    
public static Closure Closure::bind ( Closure $closure , object $newthis [, mixed $newscope = 'static' ] )

- [Closure::bindTo — 复制当前闭包对象，绑定指定的$this对象和类作用域](http://php.net/manual/zh/closure.bindto.php)  
public Closure Closure::bindTo ( object $newthis [, mixed $newscope = 'static' ] )  
对这两个方法不是很能理解。。。求指教  

### 共同点
都是为了扩展作用域，获取内部变量  

### 区别
js能够在方法内部直接获取到父级作用域的变量，而php需要通过use声明，并且默认是值传递  

### 应用场景
- 不是很能理解应用场景，搜索了一下，很多只是写了一个闭包实现的购物车，感觉并不是那么的实用。
- 如果只是单纯的使用匿名函数，感觉还不如封装成一个私有方法  
>这些只是个人粗鄙的理解，望指正.