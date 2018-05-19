# php中一些概念
### static 
        $this,self,static,parent
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
        1.static 调用静态方法:调用者对应的类的静态方法, self是自己,parent是父亲
        2.static 调用非静态方法:相当于在father 中调用son的ta私有方法 不能访问 因此最后一行报错
        3.static 转发和非转发调用:利用类名不会被转发,self,parent,static均会转发直到找到一个具体的类为止
        4.$this->ta() 中$this是boject(son),但是由于ta是private方法,在father中不可见,于是调用的是father中的ta()
        5.[referance](http://php.net/manual/zh/language.oop5.late-static-bindings.php)


## spl_autoload



## Closure
        Closure::bind();
## Reflection
