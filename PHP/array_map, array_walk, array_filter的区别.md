```php
<?php
// array_map, array_walk, array_filter的区别
$arr = [1, 2, 3, 4];

function foo($value)
{
    return $value * $value;
}
function filter($value)
{
    return $value > 2;
}
function walk(&$value)
{
    $value = $value * $value;
}

// array_map 会返回新的数组, 不对原数组产生影响
$new_arr = array_map('foo', $arr);
echo '原数组:'.var_export($arr, 1)."\n", 'array_map修改后的数组：'.var_export($new_arr, 1)."\n"."<hr/>";

// array_walk 返回bool值，callback需要通过值传递改变原有数组
$res = array_walk($arr, 'walk');
echo '修改后的数组:'.var_export($arr, 1)."\n", 'array_walk返回结果：'.var_export($res, 1)."\n"."<hr/>";

// array_filter 返回筛选后的新数组，不对原数组产生影响
$new_arr = array_filter($arr, 'filter');
echo '原数组:'.var_export($arr, 1)."\n", 'array_filter返回结果：'.var_export($new_arr, 1)."\n"."<hr/>";
```