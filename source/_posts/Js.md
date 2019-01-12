---
title: Js
date: 2018-12-05 11:53:52
tags:
- javascript
categories:
- 计算机基础
---

绕不过去的js.还是得学学了!

[网道](https://wangdoc.com/javascript/)

[阮一峰ECMAScript6入门](http://es6.ruanyifeng.com/)

<!-- more -->

### 入门篇

主要讲了js的作用,历史,和基本语法.这部分略过去了

### 数据结构

介绍了数字(number),字符串(string),布尔(boolean),undefined,null,对象(object)等六种数据类型

`typeof`运算符:特殊的有,函数返回`function`,`null`返回`object`.

1. null/undefined

说实话,没看明白,大都情况下都是undefined,C里面NULL确实表示0

2. boolean

true / false (undefined,null,false,0,NaN,""/'')

3. number

- 浮点数精度:(1个符号位) + (2-12指数) + (13-64小数)
- 数值的范围:超过变成`Infinity`,`Number.MAX_VALUE`,`Number.MIN_VALUE`
- 整数的科学计数法表示:`-3.1e+12`,小数点前的数字多于21位和小数点后的零多于5个会自动转为科学计数法
- 进制:`0x`,`0b`,`0o`
- `+0`/`-0`:基本相同,例外`(1/+0)===(1/-0) // false`当它作分母的时候返回`+Infinity`,`-Infinity`
- `NaN`,`Infinity`
- 全局方法:`parseInt`,`parseFloat`,`isNaN`,`isFinite`

4. 字符串

- 字符串可以被视为字符数组来获取其中元素.`abc[1] // b`
- 字符集:`JavaScript`使用`Unicode`字符集
- `Base64`:把任意值转成`0~9`,`A~Z`,`a~z`,`+`和`/`这64个字符组成的可打印字符,不是为了加密,而是为了不出现特殊字符`btoa`,`atob`两个函数把`ASCII`即(`Base64`)和`binary`进行互转.

5. 对象

- 创建:`var obj={ 'foo':'Hello' , 'bar':'World' }`
- 键名:所有的键都是字符串,所以引号可加可不加(如果不满足标识符的条件则需要加),又被成为属性
- 对象的引用:变量名为对对象的引用,两个指向同一个对象的变量,通过其中一个修改了对象的某个属性,也会影响到其他变量
- 属性的读取/赋值:`obj.p`或者`obj['p']`,`obj.p=1`或者`obj['p']=1`
- 属性的查看:`Object.keys(obj)`
- 属性的删除:`delete obj.p`
- 属性是否存在:`'p' in obj`,`obj.hasOwnProperty('toString')`
- 属性遍历:`for (var i in obj)` `i`为属性名,可以遍历继承过来的属性,只能遍历可遍历的属性
- `with`:操作同一个对象的多个属性,书写方便使用. `with`操作没有改变作用域,内部依然是当前作用域.因此赋值给不存在的属性是会在当前作用域创建一个变量

6. 函数(对象的一种)

- 声明:`function fn()`,`var fn = function()`,`new Function`
- 函数名提升:把声明放到最前面执行,但是赋值并没有
- 函数的属性:
  - `name`:函数名
  - `length`:参数长度,通过这个实现方法重载
  - `toString`:返回函数内容的源码
- 函数作用域:
  - 全局作用域
  - 函数作用域

- 参数:
- 闭包:闭包就是可以读取其他函数内部变量的函数,闭包意思懂,但是不知道指的是这个现象还是还是函数.作用:读取函数内部变量,另一个是始终让这些变量保持在内存中
- 立即调用的函数表达式(IIFE):`(function(){})();`或者`(function(){}())`;
- `eval`

7. 数组(对象的一种)

基本和对象一样...

### 面向对象编程

JavaScript的对象体系基于构造函数`constructor`和原型链`prototype` 

1. `new`命令的原理:
   - 创建一个空对象,作为将要返回的对象实例
   - 将这个空对象的原型,指向构造函数的`prototype`属性
   - 将这个空对象赋值给函数内部的`this`关键字
   - 开始执行构造函数内部的代码

2. `Object.create`创建实例对象

3. `this`关键字:属性或者方法当前所在的对象,函数被赋值给另外一个变量,`this`的指向也会变

4. `this`的实质:对象以指针形式保存,通过访问这个指针所指的对象,可以得到属性的值,如果属性是个函数呢(引擎会将函数单独保存在内存中,然后将函数的地址赋值给这个属性,所以函数是一个单独的值,需要在不同的上下文执行),这个时候需要引入一个`this`来指代函数当前的运行环境

5. `this`的使用场合:`window`,构造函数(指的是实例对象),对象的方法

6. 绑定`this`的方法:
   - `Function.prototype.call`:`fn.call(thisValue,arg1,arg2,...)`
   - `Function.prototype.apply`:`fn.apply(thisValue,[arg1,arg2,...])`
   - `Function.prototype.bind`:返回一个新的绑定到指定对象的函数

7. 对象的继承

  - 通过构造函数实例化对象的时候,无法完成公有属性的共享.

  - 采用原型对象来解决:每个函数都有一个`prototype`属性,指向一个对象

  - 原型链:所有对象都有自己的原型对象(`prototype`).一方面,任何一个对象,都可以充当其他对象的原型,另一方面,原型对象也是对象,所以也有自己的原型,因此会形成一个原型链.最终都可以上溯到`Object.prototype`中去,而它的原型就是`null`.因此原型链的尽头是`null`

  - 原型对象的`constructor`属性:定义在`prototype`对象上面,可以被所有实例对象继承,表明了原型对象与构造函数之间的关联关系

  - 构造函数的继承:
    - 在子类的构造函数中,调用父类的构造函数
    - 让子类的原型指向父类的原型(不要直接赋值)

  - 模块:实现特点功能的一组属性和方法的封装

### 异步操作

- JaveScript只在一个线程上运行

- 同步任务和异步任务:利用事件循环`Event Loop`来实现


