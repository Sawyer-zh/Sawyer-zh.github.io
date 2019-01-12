---
title: laravel路由分发
date: 2018-11-13 14:23:15
tags: 
- laravel
- php
categories:
- 源码分析
---

laravel有一个路由文件,那么如何通过定义的REQUEST_URI找到相应的控制器方法并执行呢?

<!-- more -->

### 一个请求从进入index.php之后到完成响应的流程
第一步:自动加载,实例化Ioc容器.绑定Kernel,.具体过程在bootstrap目录里面.如下:
```php
/*	index.php*/

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our application. We just need to utilize it! We'll simply require it
| into the script here so that we don't have to worry about manual
| loading any of our classes later on. It feels great to relax.
|
*/

require __DIR__.'/../bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Turn On The Lights
|--------------------------------------------------------------------------
|
| We need to illuminate PHP development, so let us turn on the lights.
| This bootstraps the framework and gets it ready for use, then it
| will load up this application so that we can run it and send
| the responses back to the browser and delight our users.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';



/* Application Container*/

/**
 * Create a new Illuminate application instance.
 *
 * @param  string|null  $basePath
 * @return void
 */
public function __construct($basePath = null)
{
    if ($basePath) {
        $this->setBasePath($basePath);
    }

    $this->registerBaseBindings();

    $this->registerBaseServiceProviders();

    $this->registerCoreContainerAliases();

}

/**
 * Register the basic bindings into the container.
 *
 * @return void
 */
protected function registerBaseBindings()
{
    static::setInstance($this);

    $this->instance('app', $this);

    $this->instance(Container::class, $this);
}

/**
 * Register all of the base service providers.
 *
 * @return void
 */
protected function registerBaseServiceProviders()
{
    $this->register(new EventServiceProvider($this));

    $this->register(new LogServiceProvider($this));

    $this->register(new RoutingServiceProvider($this));
}

/**
 * Register the core class aliases in the container.
 *
 * @return void
 */
public function registerCoreContainerAliases()
{
	//todo
}

```
第二步: 实例化Httpkernel并处理请求
```php
/* index.php */
/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

/* kernel */
 
 /**
 * Handle an incoming HTTP request.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
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

 /**
 * Send the given request through the middleware / router.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
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

/**
 * Bootstrap the application for HTTP requests.
 *
 * @return void
 */
public function bootstrap()
{
    if (! $this->app->hasBeenBootstrapped()) {
        $this->app->bootstrapWith($this->bootstrappers());
    }
}

 /**
 * The bootstrap classes for the application.
 *
 * @var array
 */
protected $bootstrappers = [
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
];

/**
 * Run the given array of bootstrap classes.
 *
 * @param  array  $bootstrappers
 * @return void
 */
public function bootstrapWith(array $bootstrappers)
{
    $this->hasBeenBootstrapped = true;

    foreach ($bootstrappers as $bootstrapper) {
        $this['events']->fire('bootstrapping: '.$bootstrapper, [$this]);

        $this->make($bootstrapper)->bootstrap($this);

        $this['events']->fire('bootstrapped: '.$bootstrapper, [$this]);
    }
}

/* BootProviders */

class BootProviders
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $app->boot();
    }
}

/* Application */
  
/**
 * Boot the application's service providers.
 *
 * @return void
 */
public function boot()
{
    if ($this->booted) {
        return;
    }

    // Once the application has booted we will also fire some "booted" callbacks
    // for any listeners that need to do work after this initial booting gets
    // finished. This is useful when ordering the boot-up processes we run.
    $this->fireAppCallbacks($this->bootingCallbacks);

    array_walk($this->serviceProviders, function ($p) {
        $this->bootProvider($p);
    });

    $this->booted = true;

    $this->fireAppCallbacks($this->bootedCallbacks);
}

/**
 * Boot the given service provider.
 *
 * @param  \Illuminate\Support\ServiceProvider  $provider
 * @return mixed
 */
protected function bootProvider(ServiceProvider $provider)
{
    if (method_exists($provider, 'boot')) {
        return $this->call([$provider, 'boot']);
    }
}

```
中间比较重要的是`kernel`中`bootstrap()`方法,我们具体来看看给定的几个Bootstrap class, 这几个类中`bootstrap()`方法会依次执行

1. `\Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class`:加载并设置环境变量,设置`$_ENV`,`$_SERVER`的值,涉及到的类`\Dotenv\Dotenv`, `\Dotenv\Loader`,涉及相关的函数`putenv()`等等

2. `\Illuminate\Foundation\Bootstrap\LoadConfiguration::class`:向`app`容器里面添加`config`到`instance`中,其中`config`读取`config`目录下的`php`文件

3. `\Illuminate\Foundation\Bootstrap\HandleExceptions::class`: 注册错误,异常,关闭处理函数,涉及相关的函数`set_error_handler()`,`set_exception_handler()`,`register_shutdown_function()`等等

4. `\Illuminate\Foundation\Bootstrap\RegisterFacades::class`:把`config`里面的`app['alias']`设置自动加载,相关类`AliasLoader`,相关函数`spl_autoload_register()`,`class_alias()`等等

5. `\Illuminate\Foundation\Bootstrap\RegisterProviders::class`:注册`config`里面`app['providers']`,这里有个`compileManifest`文件,位置在`bootstrap/cache/services.php`,第一次生成的时候需要实例化每一个`serviceprovide`来判断他们的一些属性如`isdefer`,`when`等等,而这些行为一般不会变化,因此第一次执行的时候实例化他们,并保存起来,以后就不用每次都实例化了.如果发生了变化呢?代码里面是通过比较`providers`的值是否变化,如果变化了则重新编译.这样的结果就把各个`serviceprovider`按照一些特性分组了,如果`eager`则实例化这个`serviceprovider`并调用它的`register()` 方法,如果是`isdefered`则只是把它和并到这个数组里面.

6. `\Illuminate\Foundation\Bootstrap\BootProviders::class`:这一步就是把执行各个`serviceprovider`里面的`boot`方法

`bootstrap()`之后的就是利用`Pipeline`经过中间件之后到`dispatchToRouter()`
```php

/*Router*/

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

/* RouteCollection */

/**
 * Find the first route matching a given request.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Routing\Route
 *
 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
 */
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

 /**
 * Determine if a route in the array matches the request.
 *
 * @param  array  $routes
 * @param  \Illuminate\http\Request  $request
 * @param  bool  $includingMethod
 * @return \Illuminate\Routing\Route|null
 */
protected function matchAgainstRoutes(array $routes, $request, $includingMethod = true)
{
    return Arr::first($routes, function ($value) use ($request, $includingMethod) {
            return $value->matches($request, $includingMethod);
        });
}

/* Route */

/**
 * Determine if the route matches given request.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  bool  $includingMethod
 * @return bool
 */
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


/* ControllerDispatcher */

/**
 * Dispatch a request to a given controller and method.
 *
 * @param  \Illuminate\Routing\Route  $route
 * @param  mixed  $controller
 * @param  string  $method
 * @return mixed
 */
public function dispatch(Route $route, $controller, $method)
{
    $parameters = $this->resolveClassMethodDependencies(
        $route->parametersWithoutNulls(), $controller, $method
    );

    if (method_exists($controller, 'callAction')) {
        return $controller->callAction($method, $parameters);
    }

    return $controller->{$method}(...array_values($parameters));
}

```
可以看出`dispatchToRoute`首先是`findRoute()`就是在`routeCollections`里面寻找第一个匹配的路由.具体的做法是编译路由会产生正则表达式,然后使用`validator`来验证这个路由是否和这个请求匹配.找到路由之后经过一组路由中间件然后执行对应到的控制器方法,最后执行这个方法返回结果.具体过程也使用了`ControllerDispatcher`类

第三步:处理结束请求的回调
```php
/* index.php */

$kernel->terminate($request, $response);

/* Application */

/**
 * Terminate the application.
 *
 * @return void
 */
public function terminate()
{
    foreach ($this->terminatingCallbacks as $terminating) {
        $this->call($terminating);
    }
}
```

### 几个问题
分析了整个请求从`index.php`到响应客户端的过程回答下面几个问题就很容易了:

1. `routes`目录下面的路由是何时加载的?

从分析中可以看出:`app/Providers/RouteServiceProvider.php`中的`map()`方法.也就是在`Kernel`执行`bootstrap()`中对`ServiceProvider`并执行`boot()`之后,路由文件就加载进去了.
