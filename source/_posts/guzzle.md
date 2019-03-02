---
title: guzzle
date: 2019-01-19 11:26:54
tags:
- php
categories:
- 源码分析
---

之前使用php发网络请求主要用的是`file_get_contents()` 和`curl`扩展.每次来新需求的时候自己就会封装一个网络请求库.无论是自己使用还是团队合作,都不利于积累,每次都需要重新封装,太复杂了.我想其他人肯定也考虑到了这个问题,因此才会有[psr7标准](https://www.php-fig.org/psr/psr-7),有了标准,大家的代码就可以复用了.所以尝试看一下`guzzlehttp`的源码,看看他是如何实现网络请求的.

<!-- more -->

### 一个简单的例子

首先发送一个简单的请求,然后追踪代码看看是如何执行的.

```php
// 同步
$client = new GuzzleHttp\Client;

$resp = $client ->get('http://www.baidu.com');

var_dump($resp->getBody()->getContents());

// 异步
$promise1 = $client ->getAsync('http://www.baidu.com');

$p = $promise1->then(function($res){

    var_dump($res);

},function($res){

    var_dump($res);

});

$p->wait();

```

### 实例化Client 

首先实例化client的时候会检查一下$config['handler'],如果没有则使用默认的.

根据代码可以发现:

- `$config['handler']`是一个`HandlerStack`对象,后面会调用这个对象的`__invoke`方法
- `HandlerStack`有一个`stack`成员,栈中的成员是[callable , name]数组
- `HandlerStack`有一个`handler`成员.这个成员是个闭包,调用这个闭包相当于调用`CurlHandler` , `CurlMultiHandler`中的`__invoke`方法

```php

$config['handler'] = HandlerStack::create();

public static function create(callable $handler = null)
{
    $stack = new self($handler ?: choose_handler());
    $stack->push(Middleware::httpErrors(), 'http_errors');
    $stack->push(Middleware::redirect(), 'allow_redirects');
    $stack->push(Middleware::cookies(), 'cookies');
    $stack->push(Middleware::prepareBody(), 'prepare_body');

    return $stack;
}

public function __construct(callable $handler = null)
{
    $this->handler = $handler;
}

function choose_handler()
{
    $handler = null;
    if (function_exists('curl_multi_exec') && function_exists('curl_exec')) {
        $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
    } elseif (function_exists('curl_exec')) {
        $handler = new CurlHandler();
    } elseif (function_exists('curl_multi_exec')) {
        $handler = new CurlMultiHandler();
    }

    if (ini_get('allow_url_fopen')) {
        $handler = $handler
            ? Proxy::wrapStreaming($handler, new StreamHandler())
            : new StreamHandler();
    } elseif (!$handler) {
        throw new \RuntimeException('GuzzleHttp requires cURL, the '
            . 'allow_url_fopen ini setting, or a custom HTTP handler.');
    }

    return $handler;
}

public static function wrapSync(
    callable $default,
    callable $sync
) {
    return function (RequestInterface $request, array $options) use ($default, $sync) {
        return empty($options[RequestOptions::SYNCHRONOUS])
            ? $default($request, $options)
            : $sync($request, $options);
    };
}

```

### 请求get,getAsync方法

这两个方法都会进入同一个方法.都会进入到`requestAsync`,不同的是,前者返回一个`fulfilledpromise`之后,调用了`wait`方法,直接返回了请求结果,后者是直接返回`promise`,需要调用`then`最终调用`wait`执行.

```php

public function __call($method, $args)
{
    if (count($args) < 1) {
        throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
    }

    $uri = $args[0];
    $opts = isset($args[1]) ? $args[1] : [];

    return substr($method, -5) === 'Async'
        ? $this->requestAsync(substr($method, 0, -5), $uri, $opts)
        : $this->request($method, $uri, $opts);
}

public function requestAsync($method, $uri = '', array $options = [])
{
    $options = $this->prepareDefaults($options);
    // Remove request modifying parameter because it can be done up-front.
    $headers = isset($options['headers']) ? $options['headers'] : [];
    $body = isset($options['body']) ? $options['body'] : null;
    $version = isset($options['version']) ? $options['version'] : '1.1';
    // Merge the URI into the base URI.
    $uri = $this->buildUri($uri, $options);
    if (is_array($body)) {
        $this->invalidBody();
    }
    $request = new Psr7\Request($method, $uri, $headers, $body, $version);
    // Remove the option so that they are not doubly-applied.
    unset($options['headers'], $options['body'], $options['version']);

    return $this->transfer($request, $options);
}

public function request($method, $uri = '', array $options = [])
{
    $options[RequestOptions::SYNCHRONOUS] = true;
    return $this->requestAsync($method, $uri, $options)->wait();
}


private function transfer(RequestInterface $request, array $options)
{
    // save_to -> sink
    if (isset($options['save_to'])) {
        $options['sink'] = $options['save_to'];
        unset($options['save_to']);
    }

    // exceptions -> http_errors
    if (isset($options['exceptions'])) {
        $options['http_errors'] = $options['exceptions'];
        unset($options['exceptions']);
    }

    $request = $this->applyOptions($request, $options);
    $handler = $options['handler'];

    try {
        return Promise\promise_for($handler($request, $options));
    } catch (\Exception $e) {
        return Promise\rejection_for($e);
    }
}
```

分开来看:

####  `$handler($request , $options)`

之前分析过,$handler是HandlerStack的实例,因此,这里会调用他的`__invoke`方法.

- `$fn[0]`就是构造方法加入的`callable`.
- `$prev`就是HandlerStack的`handler`成员.
- 这个resolve就是用按照进栈的顺序,用`stack`里面的`callable`把`$handler`包裹起来...  这个很像laravel加入中间件的Pipeline的用法.具体代码在Middleware里面,这里不展开了.顺序的话,闭包中包裹的$handler在最里面,因此也是最先执行.在最外面的最晚执行.
- $handler 分别是`CurlHandler`,和`CurlMultiHandler`,因此会调用`__invoke`方法. 
- 区别在这里出来了,getAsync返回的是promise,get返回的是FulfilledPromise
- 在这里$this->factory->create返回的是实际上执行了curl_init,$easy为EasyHandle的实例,里面包装了一个请求的基本情况.


```php
public function __invoke(RequestInterface $request, array $options)
{
    $handler = $this->resolve();

    return $handler($request, $options);
}

public function resolve()
{
    if (!$this->cached) {
        if (!($prev = $this->handler)) {
            throw new \LogicException('No handler has been specified');
        }

        foreach (array_reverse($this->stack) as $fn) {
            $prev = $fn[0]($prev);
        }

        $this->cached = $prev;
    }

    return $this->cached;
}

// CurlMultiHandler
public function __invoke(RequestInterface $request, array $options)
{
    $easy = $this->factory->create($request, $options);
    $id = (int) $easy->handle;

    $promise = new Promise(
        [$this, 'execute'],
        function () use ($id) {
            return $this->cancel($id);
        }
    );

    $this->addRequest(['easy' => $easy, 'deferred' => $promise]);

    return $promise;
}

// CurlHandler
public function __invoke(RequestInterface $request, array $options)
{
    if (isset($options['delay'])) {
        usleep($options['delay'] * 1000);
    }

    $easy = $this->factory->create($request, $options);
    curl_exec($easy->handle);
    $easy->errno = curl_errno($easy->handle);

    return CurlFactory::finish($this, $easy, $this->factory);
}
```


####  Promise

Guzzle/Promise实现了[Promise/A+标准](https://segmentfault.com/a/1190000002452115)

- then方法.返回一个Promise并把当前Promise放到新Promise等待列表中.

```php
public function then(
    callable $onFulfilled = null,
    callable $onRejected = null
) {
    if ($this->state === self::PENDING) {
        $p = new Promise(null, [$this, 'cancel']);
        $this->handlers[] = [$p, $onFulfilled, $onRejected];
        $p->waitList = $this->waitList;
        $p->waitList[] = $this;
        return $p;
    }

    // Return a fulfilled promise and immediately invoke any callbacks.
    if ($this->state === self::FULFILLED) {
        return $onFulfilled
            ? promise_for($this->result)->then($onFulfilled)
            : promise_for($this->result);
    }

    // It's either cancelled or rejected, so return a rejected promise
    // and immediately invoke any callbacks.
    $rejection = rejection_for($this->result);
    return $onRejected ? $rejection->then(null, $onRejected) : $rejection;
}


public function wait($unwrap = true)
{
    $this->waitIfPending();

    $inner = $this->result instanceof PromiseInterface
        ? $this->result->wait($unwrap)
        : $this->result;

    if ($unwrap) {
        if ($this->result instanceof PromiseInterface
            || $this->state === self::FULFILLED
        ) {
            return $inner;
        } else {
            // It's rejected so "unwrap" and throw an exception.
            throw exception_for($inner);
        }
    }
}

private function waitIfPending()
{
    if ($this->state !== self::PENDING) {
        return;
    } elseif ($this->waitFn) {
        $this->invokeWaitFn();
    } elseif ($this->waitList) {
        $this->invokeWaitList();
    } else {
        // If there's not wait function, then reject the promise.
        $this->reject('Cannot wait on a promise that has '
            . 'no internal wait function. You must provide a wait '
            . 'function when constructing the promise to be able to '
            . 'wait on a promise.');
    }

    queue()->run();

    if ($this->state === self::PENDING) {
        $this->reject('Invoking the wait callback did not resolve the promise');
    }
}

private function invokeWaitFn()
{
    try {
        $wfn = $this->waitFn;
        $this->waitFn = null;
        $wfn(true);
    } catch (\Exception $reason) {
        if ($this->state === self::PENDING) {
            // The promise has not been resolved yet, so reject the promise
            // with the exception.
            $this->reject($reason);
        } else {
            // The promise was already resolved, so there's a problem in
            // the application.
            throw $reason;
        }
    }
}

private function invokeWaitList()
{
    $waitList = $this->waitList;
    $this->waitList = null;

    foreach ($waitList as $result) {
        while (true) {
            $result->waitIfPending();

            if ($result->result instanceof Promise) {
                $result = $result->result;
            } else {
                if ($result->result instanceof PromiseInterface) {
                    $result->result->wait(false);
                }
                break;
            }
        }
    }
}
```

