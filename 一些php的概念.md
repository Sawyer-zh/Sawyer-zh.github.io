# php中一些概念

### static 后期静态绑定

        $this,self,static,parent 的一个例子 ,php手册里面例子也非常棒
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

输出:

        Father::ta Son::tb Father::tb GrandPa::tb  

结论:
        
        * static 调用静态方法:调用者对应的类的静态方法, self是自己,parent是父亲
        * static 调用非静态方法:相当于在father 中调用son的ta私有方法 不能访问 因此最后一行报错
        * static 转发和非转发调用:利用类名不会被转发,self,parent,static均会转发直到找到一个具体的类为止
        * $this->ta() 中$this是object(son),但是由于ta是private方法,在father中不可见,于是调用的是father中的ta()

[reference](http://php.net/manual/zh/language.oop5.late-static-bindings.php/)


## Callback 类型
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

输出:

        my_callback_functionMyClass::myCallbackMethodMyClass::myCallbackMethodMyClass::myCallbackMethodMyClass::myCallbackMethod__invokehello

结论:

        * 传递函数:传函数的名字字符串
        * 静态方法的传递: 1使用字符串 2使用array 3可使用call_user_func(array('B','parent::methodname'))
        * 成员方法的传递: 使用array($obj,str $method)
        * 闭包传递:直接传闭包

[reference](http://php.net/manual/zh/language.types.callable.php/)


## Closure 闭包
        Closure::bind($closure,$newObj [, $scope = 'static']);
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

输出:

        string(2) "m1" string(2) "m2" string(2) "m3" string(2) "m4" string(5) "hello"string(2) "m3"

结论:

        * 参数意思: 1 closure 表示传入一个闭包 ,2 $newthis 这个闭包绑定在那个对象上面(可以为null) 3 $scope闭包的作用域,不传为'static'表示当前环境,new A() 和 'A' 和A::class 意思一样表示在A里面
        * $c1 ,$c2 : 因为$f1中使用了$_m1是私有变量,因此在第三参数需要在A里面,所以$c1,$c2的第三参数不同
        * $c3,$4,$5 :由于是静态调用,因此第二个参数可以为null,而c3,c4中使用了self和static,所以需要指定$scope来指明谁是self和static,而$f5中A::$m3在外部也可以调用,因此第三个参数为'static',如果是A::$_m4则不能调用成功

[reference](http://php.net/manual/zh/closure.bind.php/)

## spl_autoload

        <?php
        function my_autoload($class){
            var_dump($class);
            var_dump("my_autoload");
        }
        $ret = spl_autoload_register('my_autoload');
        var_dump(spl_autoload_functions());
        $a = new DDD();


输出:

        array(1) { [0]=> string(11) "my_autoload" } string(3) "DDD" string(11) "my_autoload"

结论:

        * new 一个对象的时候 会先调用spl_autolad_register中注册的函数,最后找不到报了个错误

[reference](http://php.net/manual/zh/function.spl-autoload.php/)

## Reflection
