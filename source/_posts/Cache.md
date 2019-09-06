---
title: Lumen中的Cache
date: 2019-09-06 23:28:50
tags: 
- PHP
- Lumen
categories:
- 源码分析 
---

看Lumen源码的时候，在`bootstrap.php`里面并未看到注册`cache`相关的代码，那为什么`Cache::get()`可以直接使用呢？

<!-- more -->


# Facade门面

`Cache`里面只有一个方法，这个方法重写了父类Facade中同名方法（这个方法可以看做抽象方法，因为它直接抛出了异常）

```php
class Cache extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cache';
    }
}
```

`Facade`里面最主要的就是一个静态代理方法,所以他会根据具体的门面来实例化相应的类,再继续调用这个类的方法

实际调用的方法的实例是`static::$app[$name]`,`Container`实现了`ArrayAccess`接口,因此实际上会调用`offsetGet`,里面实际上是`make`,`Lumen`的`Application`重写了`make`方法,经过这一系列操作之后,`IoC`容器里面的`cache`绑定的就是`CacheManager`的实例

Facade.php

```php

public static function __callStatic($method, $args)
{
    $instance = static::getFacadeRoot();

    if (! $instance) {
        throw new RuntimeException('A facade root has not been set.');
    }

    return $instance->$method(...$args);
}

public static function getFacadeRoot()
{
    return static::resolveFacadeInstance(static::getFacadeAccessor());
}

protected static function resolveFacadeInstance($name)
{
    if (is_object($name)) {
        return $name;
    }

    if (isset(static::$resolvedInstance[$name])) {
        return static::$resolvedInstance[$name];
    }

    return static::$resolvedInstance[$name] = static::$app[$name];
}

public static function setFacadeApplication($app)
{
    static::$app = $app;
}
```

Container.php

```php
public function offsetGet($key)
{
    return $this->make($key);
}

public function make($abstract, array $parameters = [])
{
    return $this->resolve($abstract, $parameters);
}
```

Application.php

```php

public function make($abstract, array $parameters = [])
{
    $abstract = $this->getAlias($abstract);

    if (array_key_exists($abstract, $this->availableBindings) &&
        ! array_key_exists($this->availableBindings[$abstract], $this->ranServiceBinders)) {
        $this->{$method = $this->availableBindings[$abstract]}();

        $this->ranServiceBinders[$method] = true;
    }

    return parent::make($abstract, $parameters);
}

protected function registerCacheBindings()
{
    $this->singleton('cache', function () {
        return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider');
    });
    $this->singleton('cache.store', function () {
        return $this->loadComponent('cache', 'Illuminate\Cache\CacheServiceProvider', 'cache.store');
    });
}

public function loadComponent($config, $providers, $return = null)
{
    $this->configure($config);

    foreach ((array) $providers as $provider) {
        $this->register($provider);
    }

    return $this->make($return ?: $config);
}
```
    
CacheServiceProvider.php 

```php
public function register()
{
    $this->app->singleton('cache', function ($app) {
        return new CacheManager($app);
    });

    $this->app->singleton('cache.store', function ($app) {
        return $app['cache']->driver();
    });

    $this->app->singleton('memcached.connector', function () {
        return new MemcachedConnector;
    });
}
```

# CacheManager及其相关类

从`Facade`分析可以知道,`Cache`实际上代理的是`CacheManager`类

- `CacheManager`实现了`Factory`接口,该接口有一个`store`方法，给定一个`store`的类型,返回`Repository`,该类代理了`Repository`类

- `Repository`接口继承了`\Psr\SimpleCache\CacheInterface`接口,并添加了其他一些常用的方法.

- `Store`接口抽象出了对各种类型`Store`实现的共用方法,`FileStore`,`DatabaseStore`,`RedisStore`,`NullStore`等实现了这个接口

- `Repository`类实现了`Repository`接口，并代理了`Store`

# 类图

![Facade以及Cache类类图](/images/img/cache.png)


# 总结

- php里面经常会用`\__call`,`\__callStatic` 方法来做代理.`CacheManager`代理`Repository`,`Repository`又代理`Store`

- 依赖于接口而不是实现. `Repository`依赖于`Store`接口,以后每增加一个`Store`类型,只需要实现`Store`接口就可以了

- `ArrayAccess`接口的使用
