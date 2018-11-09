# Pipeline的理解
### 起因
laravel中有如下一段代码看起来很费劲,好好研究一下
```php
(new Pipeline($this->app))
                    ->send($request)
                    ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                    ->then($this->dispatchToRouter());

/**
 * Set the object being sent through the pipeline.
 *
 * @param  mixed  $passable
 * @return $this
 */
public function send($passable)
{
    $this->passable = $passable;

    return $this;
}

/**
 * Set the array of pipes.
 *
 * @param  array|mixed  $pipes
 * @return $this
 */
public function through($pipes)
{
    $this->pipes = is_array($pipes) ? $pipes : func_get_args();

    return $this;
}

/**
 * Run the pipeline with a final destination callback.
 *
 * @param  \Closure  $destination
 * @return mixed
 */
public function then(Closure $destination)
{
    $pipeline = array_reduce(
        array_reverse($this->pipes), $this->carry(), $this->prepareDestination($destination)
    );
    return $pipeline($this->passable);
}
```

### array_reduce用法分析
array_reduce 和 array_map , array_filter 一起是三个常用的高阶函数(函数的参数可以接收别的函数)

下面来自php手册:

    mixed array_reduce ( array $array , callable $callback [, mixed $initial = NULL ] )
        
    array
    输入的 array。
        
    callback
    mixed callback ( mixed $carry , mixed $item )
        carry
        携带上次迭代里的值； 如果本次迭代是第一次，那么这个值是 initial。
        
        item
        携带了本次迭代的值。
        
    initial
    如果指定了可选参数 initial，该参数将在处理开始前使用，或者当处理结束，数组为空时的最后一个结果。

```php
<?php
function sum($carry, $item)
{
    $carry += $item;
    return $carry;
}

function product($carry, $item)
{
    $carry *= $item;
    return $carry;
}

$a = array(1, 2, 3, 4, 5);
$x = array();

var_dump(array_reduce($a, "sum")); // int(15)
var_dump(array_reduce($a, "product", 10)); // int(1200), because: 10*1*2*3*4*5
var_dump(array_reduce($x, "sum", "No data to reduce")); // string(17) "No data to reduce"
?>
```
上面的例子比较容易理解,但是理解了这个似乎还是看不懂Pipeline的then是如何调用的,请看下面一个例子
```php
<?php
$array = [
    function ($i, $next) {
        return $next($i + 1);
    },
    function ($j, $next) {
        return $next($j * 2);
    },
];

$ret = array_reduce(array_reverse($array), function ($carry, $item) {
    return function ($k) use ($carry, $item) {
        return $item($k, $carry);
    };
}, function ($m) {
    return $m * 100;
});

var_dump($ret);
var_dump($ret(4));
```
打印结果:

        object(Closure)#6 (2) { 
            ["static"]=> array(2) {   // use 
                ["carry"]=> object(Closure)#5 (2) { 
                    ["static"]=> array(2) {  // use
                        ["carry"]=> object(Closure)#4 (1) {["parameter"]=> array(1) { ["$m"]=> string(10) "" } } //initial
                        ["item"]=> object(Closure)#2 (1) {["parameter"]=> array(2) { ["$j"]=> string(10) "" ["$next"]=> string(10) "" } } //array[1]
                    } 
                    ["parameter"]=> array(1) { ["$k"]=> string(10) "" } 
                }
                ["item"]=> object(Closure)#1 (1) {["parameter"]=> array(2) { ["$i"]=> string(10) "" ["$next"]=> string(10) "" } } //array[0]
            } 
            ["parameter"]=> array(1) { ["$k"]=> string(10) "" }
        } 
        int(1000)


