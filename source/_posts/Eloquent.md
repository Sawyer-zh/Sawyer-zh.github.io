---
title: Eloquent
date: 2019-03-22 18:55:49
tags:
 - laravel
 - php
categories:
 - 源码分析
---

一直不喜欢使用orm,因为换一个框架就需要重新学习一下写法.但是最近用了一下Eloquent,发现其实用起来还是非常方便,顺带看了一下他的实现.


<!-- more -->


# 开启使用Eloquent

项目使用lumen,Eloquent是可选项

bootstrap 里面加上就可以了

```php
$app->withEloquent();
```

以下是具体执行流程:

- lumen Application里面db绑定了`registerDatabaseBindings`

- 最终会调用`DatabaseServiceProvider`和`PaginationServiceProvider`里面的`register`和`boot`方法


```php

public function withEloquent()
{
    $this->make('db');
}

//'db' => 'registerDatabaseBindings',

protected function registerDatabaseBindings()
{
    $this->singleton('db', function () {
        return $this->loadComponent(
            'database', [
                'Illuminate\Database\DatabaseServiceProvider',
                'Illuminate\Pagination\PaginationServiceProvider',
            ], 'db'
        );
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

# DatabaseServiceProvider

可以看出比较关键的几个类`ConnectionFactory`,`DatabaseManager`,`Model`

```php
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels();

        $this->registerConnectionServices();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();
    }

    /**
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }

    /**
     * Register the Eloquent factory instance in the container.
     *
     * @return void
     */
    protected function registerEloquentFactory()
    {
        $this->app->singleton(FakerGenerator::class, function ($app) {
            return FakerFactory::create($app['config']->get('app.faker_locale', 'en_US'));
        });

        $this->app->singleton(EloquentFactory::class, function ($app) {
            return EloquentFactory::construct(
                $app->make(FakerGenerator::class), $this->app->databasePath('factories')
            );
        });
    }

    /**
     * Register the queueable entity resolver implementation.
     *
     * @return void
     */
    protected function registerQueueableEntityResolver()
    {
        $this->app->singleton(EntityResolver::class, function () {
            return new QueueEntityResolver;
        });
    }
}

```

## ConnectionFactory

数据库连接工厂,可以根据配置,获得一个数据库链接,支持`mysql`,`sqlite`...等等

具体有`Connector`和`Connection`以及这两个类的子类

Connection实现了ConnectionIntereface规定的增删改查等等的一些操作,具体使用了PDOStatement类,由`PDO::prepare()`获得

```php
protected function createSingleConnection(array $config)
{
    $pdo = $this->createPdoResolver($config);

    return $this->createConnection(
        $config['driver'], $pdo, $config['database'], $config['prefix'], $config
    );
}

```

具体PDO实例获取,会到Connector里面的`createPdoConnection`方法中

```php
protected function createPdoConnection($dsn, $username, $password, $options)
{
    if (class_exists(PDOConnection::class) && ! $this->isPersistentConnection($options)) {
        return new PDOConnection($dsn, $username, $password, $options);
    }

    return new PDO($dsn, $username, $password, $options);
}
```

## DatabaseManager 

在这里app里面`db`被绑上了单例的DatabaseManager用于管理用`ConnectionFactory`产生的连接

DatabaseManager里面有一个connection成员数组用来保存数据库连接,有一个ConnectionFactory成员用来产生新的连接

```php
/**
 * Get a database connection instance.
 *
 * @param  string  $name
 * @return \Illuminate\Database\Connection
 */
public function connection($name = null)
{
    list($database, $type) = $this->parseConnectionName($name);

    $name = $name ?: $database;

    // If we haven't created this connection, we'll create it based on the config
    // provided in the application. Once we've created the connections we will
    // set the "fetch mode" for PDO which determines the query return types.
    if (! isset($this->connections[$name])) {
        $this->connections[$name] = $this->configure(
            $this->makeConnection($database), $type
        );
    }

    return $this->connections[$name];
}
```

## boot

boot方法为EloquentModel设置了连接处理(DatabaseManager)和事件处理的类


```php

/**
 * Configure the PDO prepared statement.
 *
 * @param  \PDOStatement  $statement
 * @return \PDOStatement
 */
protected function prepared(PDOStatement $statement)
{
    $statement->setFetchMode($this->fetchMode);

    $this->event(new Events\StatementPrepared(
        $this, $statement
    ));

    return $statement;
}

```

### Model

分析一下比如`Model::with()->where(...)->get()`实际发生了什么?

首先model里面有个类成员,`$resolver`,在`boot()`方法里面把实例化的`DatabaseManager`设置为了`$resolver`.

`__callStatic`会先实例化自己,再调用自己的成员方法或者`__call`方法.

`$this->newQuery`是`Eloquent\Builder`的实例,他实际上是`Query\Builder`装饰而来.


```php
/**
 * Set the connection resolver instance.
 *
 * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
 * @return void
 */
public static function setConnectionResolver(Resolver $resolver)
{
    static::$resolver = $resolver;
}
```

```php
/**
 * Handle dynamic method calls into the model.
 *
 * @param  string  $method
 * @param  array  $parameters
 * @return mixed
 */
public function __call($method, $parameters)
{
    if (in_array($method, ['increment', 'decrement'])) {
        return $this->$method(...$parameters);
    }

    return $this->newQuery()->$method(...$parameters);
}

/**
 * Handle dynamic static method calls into the method.
 *
 * @param  string  $method
 * @param  array  $parameters
 * @return mixed
 */
public static function __callStatic($method, $parameters)
{
    return (new static)->$method(...$parameters);
}
```

这个方法,根据`$table`成员返回`Eloquent\Query`,可以看出,`setModel`方法里面,把`Database\Query`里面的`from`成员设置为`Eloquent\Model`里面定义的`$table`,这样查询的时候就知道从那张表查询了`Eloquent\Query`里面也定义了一个`__call`方法,具体就是会调用`Database\Query`或者`trait`里面的方法

```php
/**
 * Get a new query builder for the model's table.
 *
 * @return \Illuminate\Database\Eloquent\Builder
 */
public function newQuery()
{
    return $this->registerGlobalScopes($this->newQueryWithoutScopes());
}
```

```php
/**
 * Set a model instance for the model being queried.
 *
 * @param  \Illuminate\Database\Eloquent\Model  $model
 * @return $this
 */
public function setModel(Model $model)
{
    $this->model = $model;

    $this->query->from($model->getTable());

    return $this;
}
```

`with`的实现,大致就是先把关系放进`Eloquent\Query`的`eagerLoad`成员里面,


```php

/**
 * Begin querying a model with eager loading.
 *
 * @param  array|string  $relations
 * @return \Illuminate\Database\Eloquent\Builder|static
 */
public static function with($relations)
{
    return (new static)->newQuery()->with(
        is_string($relations) ? func_get_args() : $relations
    );
}

```

```php

/**
 * Set the relationships that should be eager loaded.
 *
 * @param  mixed  $relations
 * @return $this
 */
public function with($relations)
{
    $eagerLoad = $this->parseWithRelations(is_string($relations) ? func_get_args() : $relations);

    $this->eagerLoad = array_merge($this->eagerLoad, $eagerLoad);

    return $this;
}

```

```php

/**
 * Execute the query as a "select" statement.
 *
 * @param  array  $columns
 * @return \Illuminate\Database\Eloquent\Collection|static[]
 */
public function get($columns = ['*'])
{
    $builder = $this->applyScopes();

    // If we actually found models we will also eager load any relationships that
    // have been specified as needing to be eager loaded, which will solve the
    // n+1 query issue for the developers to avoid running a lot of queries.
    if (count($models = $builder->getModels($columns)) > 0) {
        $models = $builder->eagerLoadRelations($models);
    }

    return $builder->getModel()->newCollection($models);
}

```

```php

/**
 * Eagerly load the relationship on a set of models.
 *
 * @param  array  $models
 * @param  string  $name
 * @param  \Closure  $constraints
 * @return array
 */
protected function eagerLoadRelation(array $models, $name, Closure $constraints)
{
    // First we will "back up" the existing where conditions on the query so we can
    // add our eager constraints. Then we will merge the wheres that were on the
    // query back to it in order that any where conditions might be specified.
    $relation = $this->getRelation($name);

    $relation->addEagerConstraints($models);

    $constraints($relation);

    // Once we have the results, we just match those back up to their parent models
    // using the relationship instance. Then we just return the finished arrays
    // of models which have been eagerly hydrated and are readied for return.
    return $relation->match(
        $relation->initRelation($models, $name),
        $relation->getEager(), $name
    );
}

```

这里以`hasOneOrMany`为例,实际上,执行的是`whereIn`,这里既不是通过`join`,也不是通过循环一条一条查询的.

```php

/**
 * Set the constraints for an eager load of the relation.
 *
 * @param  array  $models
 * @return void
 */
public function addEagerConstraints(array $models)
{
    $this->query->whereIn(
        $this->foreignKey, $this->getKeys($models, $this->localKey)
    );
}

```

这里`parseWithRelations`的时候,`constraint`是一个闭包

```php

/**
 * Parse a list of relations into individuals.
 *
 * @param  array  $relations
 * @return array
 */
protected function parseWithRelations(array $relations)
{
    $results = [];

    foreach ($relations as $name => $constraints) {
        // If the "relation" value is actually a numeric key, we can assume that no
        // constraints have been specified for the eager load and we'll just put
        // an empty Closure with the loader so that we can treat all the same.
        if (is_numeric($name)) {
            $name = $constraints;

            list($name, $constraints) = Str::contains($name, ':')
                        ? $this->createSelectWithConstraint($name)
                        : [$name, function () {
                            //
                        }];
        }

        // We need to separate out any nested includes. Which allows the developers
        // to load deep relationships using "dots" without stating each level of
        // the relationship with its own key in the array of eager load names.
        $results = $this->addNestedWiths($name, $results);

        $results[$name] = $constraints;
    }

    return $results;
}


/**
 * Create a constraint to select the given columns for the relation.
 *
 * @param  string  $name
 * @return array
 */
protected function createSelectWithConstraint($name)
{
    return [explode(':', $name)[0], function ($query) use ($name) {
        $query->select(explode(',', explode(':', $name)[1]));
    }];
}

```


```php

/**
 * Match the eagerly loaded results to their many parents.
 *
 * @param  array   $models
 * @param  \Illuminate\Database\Eloquent\Collection  $results
 * @param  string  $relation
 * @param  string  $type
 * @return array
 */
protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
{
    $dictionary = $this->buildDictionary($results);

    // Once we have the dictionary we can simply spin through the parent models to
    // link them up with their children using the keyed dictionary to make the
    // matching very convenient and easy work. Then we'll just return them.
    foreach ($models as $model) {
        if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
            $model->setRelation(
                $relation, $this->getRelationValue($dictionary, $key, $type)
            );
        }
    }

    return $models;
}

```

执行到这里,`model`里面`$relation`数组里面的关系就有了数据,看看`toArray`方法,果然会把这个结果`merge`进去

```php

public function toArray()
{
    return array_merge($this->attributesToArray(), $this->relationsToArray());
}

```

以上基本就是`Model::with(..)->where()->get()->toArray()`的具体流程.主要明白了`with`是通过`whereIn`来查询的,天真的我一直以为用的是`join`来实现的
