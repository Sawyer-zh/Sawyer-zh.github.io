---
title: laravel事件分发
date: 2018-11-14 06:47:56
tags: 
- laravel
- php

categories:
- 源码分析
---

之前分析了一个请求的生命周期以及laravel路由分发,那么现在来看一下laravel中的事件分发是如何实现的.

<!-- more -->


### 加载`EventServiceProvider`类
和路由分发类似,在`Kernel`里面会注册`EventServiceProvider`类并调用`boot()`.下面看一下这个方法.

```php
/*  Illuminate\Foundation\Support\Provider\EventServiceProvider */

/**
* The event handler mappings for the application.
*
* @var array
*/
protected $listen = [];

/**
 * The subscriber classes to register.
 *
 * @var array
 */
protected $subscribe = [];

/**
 * Register the application's event listeners.
 *
 * @return void
 */
public function boot()
{
    foreach ($this->listens() as $event => $listeners) {
        foreach ($listeners as $listener) {
            Event::listen($event, $listener);
        }
    }

    foreach ($this->subscribe as $subscriber) {
        Event::subscribe($subscriber);
    }
}

/*  Illuminate\Events\Dispatcher */

/**
 * Register an event listener with the dispatcher.
 *
 * @param  string|array  $events
 * @param  mixed  $listener
 * @return void
 */
public function listen($events, $listener)
{

    foreach ((array) $events as $event) {
        if (Str::contains($event, '*')) {
            $this->setupWildcardListen($event, $listener);
        } else {
            $this->listeners[$event][] = $this->makeListener($listener);
        }
    }
}

/**
 * Register an event subscriber with the dispatcher.
 *
 * @param  object|string  $subscriber
 * @return void
 */
public function subscribe($subscriber)
{
    $subscriber = $this->resolveSubscriber($subscriber);

    $subscriber->subscribe($this);
}

/**
 * Fire an event and call the listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @param  bool  $halt
 * @return array|null
 */
public function fire($event, $payload = [], $halt = false)
{
    return $this->dispatch($event, $payload, $halt);
}

/**
 * Fire an event and call the listeners.
 *
 * @param  string|object  $event
 * @param  mixed  $payload
 * @param  bool  $halt
 * @return array|null
 */
public function dispatch($event, $payload = [], $halt = false)
{
    // When the given "event" is actually an object we will assume it is an event
    // object and use the class as the event name and this event itself as the
    // payload to the handler, which makes object based events quite simple.
    list($event, $payload) = $this->parseEventAndPayload(
        $event, $payload
    );

    if ($this->shouldBroadcast($payload)) {
        $this->broadcastEvent($payload[0]);
    }

    $responses = [];

    foreach ($this->getListeners($event) as $listener) {
        $response = $listener($event, $payload);

        // If a response is returned from the listener and event halting is enabled
        // we will just return this response, and not call the rest of the event
        // listeners. Otherwise we will add the response on the response list.
        if ($halt && ! is_null($response)) {
            return $response;
        }

        // If a boolean false is returned from a listener, we will stop propagating
        // the event to any further listeners down in the chain, else we keep on
        // looping through the listeners and firing every one in our sequence.
        if ($response === false) {
            break;
        }

        $responses[] = $response;
    }

    return $halt ? null : $responses;
}
```
`App\Providers\EventServiceProvider`继承自`Illuminate\Foundation\Support\Provider\EventServiceProvider`,它可以覆盖的父类的`$listen`和`$subscribe`.它的`boot()`方法调用了父类的`boot()`方法,为每个`$listen`里面的事件绑定了监听方法.并且执行每个`$subscribe`里面的`subscribe()`方法.

`Event`为`Illuminate\Events\Dispatcher`的`Facade`.里面的`listen()`方法把`listener`变成闭包放到成员里面.需要使用`fire()`调用,而`subscribe()`方法则直接调用`listener`的`subscribe()`方法.
