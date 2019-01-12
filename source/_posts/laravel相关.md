---
title: laravel相关
date: 2018-11-29 08:54:10
tags:
---

新项目使用了laravel,分析一下源码.

<!-- more -->

## 一个请求完整的流程

### 先从`public\index.php`入手:

```php 
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
```

### 继续看看`Illuminate\Foundation\Http\Kernel`handle方法

```php
public function handle($request)
{
    try {
        $request->enableHttpMethodParameterOverride();

        $response = $this->sendRequestThroughRouter($request);
    } catch (Exception $e) {
        $this->reportException($e);

        $response = $this->renderException($request, $e);
    } catch (Throwable $e) {
        $this->reportException($e = new FatalThrowableError($e));

        $response = $this->renderException($request, $e);
    }

    $this->app['events']->dispatch(
        new Events\RequestHandled($request, $response)
    );

    return $response;
}
```

可以看到handle方法首先把请求通过路由`sendRequestThroughRouter`,然后再把事件进行分发`dispatch`


```php
protected function sendRequestThroughRouter($request)
{
    $this->app->instance('request', $request);

    Facade::clearResolvedInstance('request');

    $this->bootstrap();

    return (new Pipeline($this->app))
                ->send($request)
                ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                ->then($this->dispatchToRouter());
}
```

这一段大概意思就是把请求先通过中间件,最后到达指定的路由

继续

```php
protected function dispatchToRouter()
{
    return function ($request) {
        $this->app->instance('request', $request);

        return $this->router->dispatch($request);
    };
}
```

### 继续 `Illuminate\Routing\Router`

```php
public function dispatch(Request $request)
{
    $this->currentRequest = $request;

    return $this->dispatchToRoute($request);
}
```

```php
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
```


```php
protected function findRoute($request)
{
    $this->current = $route = $this->routes->match($request);

    $this->container->instance(Route::class, $route);

    return $route;
}
```

### `Illuminate\Routing\RouteCollection` 的match方法

```php
public function match(Request $request)
{
    $routes = $this->get($request->getMethod());

    // First, we will see if we can find a matching route for this current request
    // method. If we can, great, we can just return it so that it can be called
    // by the consumer. Otherwise we will check for routes with another verb.
    $route = $this->matchAgainstRoutes($routes, $request);

    if (! is_null($route)) {
        return $route->bind($request);
    }

    // If no route was found we will now check if a matching route is specified by
    // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
    // inform the user agent of which HTTP verb it should use for this route.
    $others = $this->checkForAlternateVerbs($request);

    if (count($others) > 0) {
        return $this->getRouteForMethods($request, $others);
    }

    throw new NotFoundHttpException;
}
```



```php
protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
{
    return Arr::first($routes, function ($value) use ($request, $includingMethod) {
        return $value->matches($request, $includingMethod);
    });
}
```

### `Illuminate\Routing\Route` 的matches 这里如果成功就说明找到了route

```php
public function matches(Request $request, $includingMethod = true)
{
    $this->compileRoute();

    foreach ($this->getValidators() as $validator) {
        if (! $includingMethod && $validator instanceof MethodValidator) {
            continue;
        }

        if (! $validator->matches($this, $request)) {
            return false;
        }
    }

    return true;
}
```



