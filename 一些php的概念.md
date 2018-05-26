# php中一些概念

### static 后期静态绑定
* 1.$this,self,static,parent 的一个例子 ,php手册里面例子也非常棒
      
        <?php
        class GrandPa{
        
            private function ta(){
                echo __METHOD__ . str_repeat(" ", 5);
            }
        
            public static function tb(){
                echo __METHOD__ . str_repeat(" ", 5);
            }
        }
        
        class Father extends GrandPa{
        
            public function test(){
                $this->ta();
                static::tb();
                self::tb();
                parent::tb();
                static->ta();
            }
        
            private function ta(){
                echo __METHOD__ . str_repeat(" ", 5);
            }
        
            public static function tb(){
        
                echo __METHOD__ . str_repeat(" ", 5);
            }
        
        }
        
        class Son extends Father{
        
            private function ta(){
                echo __METHOD__ . str_repeat(" ", 5);
            }
        
            public static function tb(){
                echo __METHOD__ . str_repeat(" ", 5);
            }
        }
        
        
        $obj = new Son();
        $obj->test();

* 输出:

        Father::ta 
        Son::tb 
        Father::tb 
        GrandPa::tb  

* 2.结论:
        
  * a.static 调用静态方法:调用者对应的类的静态方法, self是自己,parent是父亲
  * b.static 调用非静态方法:相当于在father 中调用son的ta私有方法 不能访问 因此最后一行报错
  * c.static 转发和非转发调用:利用类名不会被转发,self,parent,static均会转发直到找到一个具体的类为止
  * d.$this->ta() 中$this是object(son),但是由于ta是private方法,在father中不可见,于是调用的是father中的ta()

* 3.[reference](http://php.net/manual/zh/language.oop5.late-static-bindings.php/)


### Callback 类型
* 1.栗子

        <?php
        function my_callback_function(){
            echo __FUNCTION__;
        }
        
        class MyClass{
        
            static function myCallbackMethod(){
                echo __METHOD__;
            }
        
            public function __invoke($echo ){
                echo $echo;
            }
        }
        
        $fun = function($echo){
            echo $echo;
        };
        #1
        call_user_func('my_callback_function');
        #2
        call_user_func(array(MyClass::class,'myCallbackMethod'));
        call_user_func(array('MyClass','myCallbackMethod'));
        call_user_func('MyClass::myCallbackMethod');
        #3
        $obj = new MyClass();
        call_user_func(array($obj,'myCallbackMethod'));
        call_user_func($obj,'__invoke');
        #4
        call_user_func($fun,"hello ");

* 输出:

        my_callback_function
        MyClass::myCallbackMethod
        MyClass::myCallbackMethod
        MyClass::myCallbackMethod
        MyClass::myCallbackMethod
        __invoke
        hello

* 2.结论:

  * a.传递函数:传函数的名字字符串
  * b.静态方法的传递: 1使用字符串 2使用array 3可使用call_user_func(array('B','parent::methodname'))
  * c.成员方法的传递: 使用array($obj,str $method)
  * d.闭包传递:直接传闭包

* 3.[reference](http://php.net/manual/zh/language.types.callable.php/)


### Closure 闭包

* 1.Closure::bind($closure,$newObj [, $scope = 'static']);        


        <?php       
        class A{
            private $_m1 = 'm1';
            public $m2 = 'm2';
            public static $m3 = 'm3';
            private static $_m4 = 'm4';
        }
        
        $f1 = function(){
            var_dump($this->_m1);
        };
        
        $f2 = function(){
            var_dump($this->m2);
        };
        
        $f3 = function(){
            var_dump(self::$m3);
        };
        
        $f4 = function(){
            var_dump(static::$_m4);
        };
        
        $f5 = function(){
            var_dump('hello');
            var_dump(A::$m3);
        };
        
        $c1 = Closure::bind($f1,new A(),A::class);
        $c2 = Closure::bind($f2,new A());
        $c3 = Closure::bind($f3,new A(),A::class);
        $c4 = Closure::bind($f4,null,new A);
        $c5 = Closure::bind($f5,null);
        $c1();
        call_user_func($c2);
        $c3();
        call_user_func($c4);
        $c5();

* 输出:

        string(2) "m1" 
        string(2) "m2" 
        string(2) "m3" 
        string(2) "m4" 
        string(5) "hello"
        string(2) "m3"

* 2.结论:

  * a.参数意思: 1 closure 表示传入一个闭包 ,2 $newthis 这个闭包绑定在那个对象上面(可以为null) 3 $scope闭包的作用域,不传为'static'表示当前环境,new A() 和 'A' 和A::class 意思一样表示在A里面
  * b.$c1 ,$c2 : 因为$f1中使用了$_m1是私有变量,因此在第三参数需要在A里面,所以$c1,$c2的第三参数不同
  * c.$c3,$4,$5 :由于是静态调用,因此第二个参数可以为null,而c3,c4中使用了self和static,所以需要指定$scope来指明谁是self和static,而$f5中A::$m3在外部也可以调用,因此第三个参数为'static',如果是A::$_m4则不能调用成功
* 3.[reference](http://php.net/manual/zh/closure.bind.php/)

### spl_autoload
* 1.例子

        <?php
        function my_autoload($class){
            var_dump($class);
            var_dump("my_autoload");
        }
        $ret = spl_autoload_register('my_autoload');
        var_dump(spl_autoload_functions());
        $a = new DDD();


* 输出:

        array(1) { [0]=> string(11) "my_autoload" } string(3) "DDD" string(11) "my_autoload"

* 2.结论:

  * a.new 一个对象的时候 会先调用spl_autolad_register中注册的函数,最后找不到报了个错误

* 3.[reference](http://php.net/manual/zh/function.spl-autoload.php/)

## Reflection

* 1.先看一些例子 大致了ReflectionClass , ReflectionMethod ,ReflectionParams 等等
        
        <?php
        
        class A{
            public function __construct(B $b , C $c){
        
            }
        }
        
        class B{
        
        }
        
        class C{
            public function __construct(D $d){
        
            }
        }
        
        class D{
        
        }
        
        #reflectionclass 
        $class = new ReflectionClass(A::class);
        var_dump($class);
        
        #reflectionmethod 不存在构造方法返回null 本身没有但是父类有也算有
        $constructor = $class->getConstructor();
        var_dump($constructor);
        
        #reflectionparams 组成的array
        $params = $constructor->getParameters();
        
        var_dump($params);
        
        foreach ($params as $param) {
            var_dump($param->getClass());
        }

* 输出:

        object(ReflectionClass)#1 (1) { ["name"]=> string(1) "A" } 
        object(ReflectionMethod)#2 (2) { ["name"]=> string(11) "__construct" ["class"]=> string(1) "A" } 
        array(2) { [0]=> object(ReflectionParameter)#3 (1) { ["name"]=> string(1) "b" } [1]=> object(ReflectionParameter)#4 (1) { ["name"]=> string(1) "c" } } 
        object(ReflectionClass)#5 (1) { ["name"]=> string(1) "B" }
        object(ReflectionClass)#5 (1) { ["name"]=> string(1) "C" }


* 2.再看一个厉害一点的例子

        class Build{
        
            protected $buildStack = [];
            protected $with = [];
        
            public function make($class){
                
                $this->buildStack[] = $class;
                
                $reflectionCalss = new ReflectionClass($class);
                
                $constructor = $reflectionCalss->getConstructor();
                #递归终点
                if (is_null($constructor)) {
                   array_pop($this->buildStack);
                   return $reflectionCalss->newInstance();
                }
                #处理递归
                $params = $constructor->getParameters();
        
                foreach ($params as $param) {
                    $this->with[$class][] = $this->make($param->getClass()->name);
                }
                
                $args = array_pop($this->with);
                
                return $reflectionCalss->newInstanceArgs($args);
            }
        }
        
        $build = new Build;
        
        var_dump($build->make(A::class));

* 输出:

        object(A)#11 (0) { }

* 3说明:
            
  * a.正常实例化A需要实例话B,C,而实例化C之前需要实例化D... 等等! 这个只是个简单的例子,实际中的情况更复杂...一个依赖另外一个.所以我们需要简单的方式来实例化A
  * b.有什么好的方式? 因为构造方法里面记录了它自己依赖的类,我们可以通过反射来获取这个类,并实例化,然后当作参数传给他.
  * c.如何具体实现? 其实是一个递归! 递归的终点是没有依赖,即自己没有构造方法.直接实例化返回,递归的思路是利用实例化好的参数实例化当前对象.
  * d.有什么值得学习的技巧? 利用buildStack成员记录当前正在实例化的对象保证实例化顺序不会错.利用with记录实例化该对象所需要的参数保证参数不会错
  * e.代码有点眼熟? 是啊,最近看了laravel启动时候Container中的代码.然后模仿写的...哈哈哈.

* 4.[reference](http://php.net/manual/zh/class.reflectionclass.php/)


