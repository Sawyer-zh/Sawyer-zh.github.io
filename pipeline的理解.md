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

>mixed array_reduce ( array $array , callable $callback [, mixed $initial = NULL ] )
>    
>array
>输入的 array。
>    
>callback
>mixed callback ( mixed $carry , mixed $item )
>    carry
>    携带上次迭代里的值； 如果本次迭代是第一次，那么这个值是 initial。
>    
>    item
>    携带了本次迭代的值。
>    
>initial
>如果指定了可选参数 initial，该参数将在处理开始前使用，或者当处理结束，数组为空时的最后一个结果。

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

        object(Closure)#6 (2) { 第二步返回的闭包
            ["static"]=> array(2) {   // use 
                ["carry"]=> object(Closure)#5 (2) { //第一步 返回的闭包
                    ["static"]=> array(2) {  // use
                        ["carry"]=> object(Closure)#4 (1) {["parameter"]=> array(1) { ["$m"]=> string(10) "" } } //initial 10
                        ["item"]=> object(Closure)#2 (1) {["parameter"]=> array(2) { ["$j"]=> string(10) "" ["$next"]=> string(10) "" } } //array[1]  $carry(5 * 2)
                    } 
                    ["parameter"]=> array(1) { ["$k"]=> string(10) "" } // 5
                }
                ["item"]=> object(Closure)#1 (1) {["parameter"]=> array(2) { ["$i"]=> string(10) "" ["$next"]=> string(10) "" } } //array[0]  $carry(4+1)
            } 
            ["parameter"]=> array(1) { ["$k"]=> string(10) "" } // 4
        } 
        int(1000)

分析闭包执行流程

1. #5 是第一步返回的闭包,也就是$carray的值#5, #6则是第二步返回的闭包,也就是$carry/$ret的值
2. 第一次传递参数4 执行$item(4,$carry) 返回$carry(5)  // $item 为$array[0]
3. 第二次传递参数5 执行$item(5,$carry) 返回$carry(10) // $item 为$array[1]
4. 第三次传递参数10 执行$carry(10)  返回1000 // $carry 为$initial

### Pipeline分析
有了上面的例子之后就比较容易理解Pipeline的then方法了,代码的意思就是在路由分发之前先把请求通过middleware

继续看`dispatchToRouter`这个方法最终干了什么?

```php
/* Kenel */

/**
 * Get the route dispatcher callback.
 *
 * @return \Closure
 */
protected function dispatchToRouter()
{
    return function ($request) {
        $this->app->instance('request', $request);

        return $this->router->dispatch($request);
    };
}

/* Router */

/**
 * Dispatch the request to the application.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function dispatch(Request $request)
{
    $this->currentRequest = $request;

    return $this->dispatchToRoute($request);
}


/**
 * Dispatch the request to a route and return the response.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return mixed
 */
public function dispatchToRoute(Request $request)
{
    // First we will find a route that matches this request. We will also set the
    // route resolver on the request so middlewares assigned to the route will
    // receive access to this route instance for checking of the parameters.
    $route = $this->findRoute($request);

    $request->setRouteResolver(function () use ($route) {
        return $route;
    });

    $this->events->dispatch(new Events\RouteMatched($route, $request));

    $response = $this->runRouteWithinStack($route, $request);

    return $this->prepareResponse($request, $response);
}

/**
 * Run the given route within a Stack "onion" instance.
 *
 * @param  \Illuminate\Routing\Route  $route
 * @param  \Illuminate\Http\Request  $request
 * @return mixed
 */
protected function runRouteWithinStack(Route $route, Request $request)
{
    $shouldSkipMiddleware = $this->container->bound('middleware.disable') &&
                            $this->container->make('middleware.disable') === true;

    $middleware = $shouldSkipMiddleware ? [] : $this->gatherRouteMiddleware($route);

    return (new Pipeline($this->container))
                    ->send($request)
                    ->through($middleware)
                    ->then(function ($request) use ($route) {
                        return $this->prepareResponse(
                            $request, $route->run()
                        );
                    });
}

/* Route */

/**
 * Run the route action and return the response.
 *
 * @return mixed
 */
public function run()
{
    $this->container = $this->container ?: new Container;

    try {
        if ($this->isControllerAction()) {
            return $this->runController();
        }

        return $this->runCallable();
    } catch (HttpResponseException $e) {
        return $e->getResponse();
    }
}
```
可以看到在Router里面又使用了一个Pipeline,但是两个使用的middleware并不是同一个

从接受到请求到交给控制器处理,中间使用了两次Pipeline,经过了两组中间件