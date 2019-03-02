---
title: Lumen源码阅读
date: 2019-02-23 23:37:27
tags:
---

最近用Lumen写Api,时不时会去看看源码.整理一下.

# 自动加载

自动加载是通过利用`composer`完成的.主要利用`spl_autoload_register`这个函数注册`composer`里面的自动加载方法`loadClass`

# .env 文件加载

实际上使用`putenv`和`apache_getenv`以及`$_EVN`,`$_SERVER`等等设置环境变量

# 实例化IoC容器

```php
public function __construct($basePath = null)
{
    if (! empty(env('APP_TIMEZONE'))) {
        date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));
    }

    $this->basePath = $basePath;

    $this->bootstrapContainer();
    $this->registerErrorHandling();
    $this->bootstrapRouter();
}
```

具体如下:

## 启动容器

- 绑定实例到`IoC容器`里面,`instance()` 

- 注册别名


## 注册错误及异常处理函数 

- 通过`set_error_handler()`,`set_exception_handler()` ,`register_shutdown_function()`来设置处理错误,异常,关闭的函数

## 启动router

```php
public function bootstrapRouter()
{
    $this->router = new Router($this);
}
```

新建一个`router`成员变量.`router`里面实现了`group`,`post`,`get`等等方法.用来添加`route`,包括`middware`,`namespace`,`prefix`,`as`等等的处理.具体里面有一个`groupStack`成员

# 添加门面,ORM (可选)

```php
$app->withFacades();

$app->withEloquent();
```

Lumen里面这个是可选的,如下
 
## 添加门面

```php
public function withFacades($aliases = true, $userAliases = [])
{
    Facade::setFacadeApplication($this);

    if ($aliases) {
        $this->withAliases($userAliases);
    }
}
```

`Facade`是一个抽象类,但是里面没有抽象方法.如下,这个和抽象方法类似了,如果子类没有实现这个方法就会调用父类的方法直接抛出异常了.

```php
protected static function getFacadeAccessor()
{
    throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
}
```

`withAliases()`方法,里面把用户定义的`$userAliases`和`lumen`里面自己定义的`aliases`合并起来,然后利用`class_alias()`实现别名


`Facade`实现:`Facade`里面有一个`$app`成员,因此通过`app`可以实例化如`db`等等的实例,然后用`__callStatic()`间接调用了这些实例的方法

```php
public static function __callStatic($method, $args)
{
    $instance = static::getFacadeRoot();

    if (! $instance) {
        throw new RuntimeException('A facade root has not been set.');
    }

    return $instance->$method(...$args);
}
```

## 添加Eloquent

```php
public function withEloquent()
{
    $this->make('db');
}
```

# 注册绑定

```php

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

```


# 添加中间键 

```php
$app->middleware([
   App\Http\Middleware\ExampleMiddleware::class
]);

$app->routeMiddleware([
    'auth' => App\Http\Middleware\Authenticate::class,
    'apisign' => App\Http\Middleware\ApiSign::class,
]);
```


# 注册服务提供者

```php
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Overtrue\LaravelWeChat\ServiceProvider::class);
``` 

# 加载路由文件

```php
$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});
``` 

# 处理请求 

```php
$app->run();
``` 
