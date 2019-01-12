---
title: C基础
date: 2019-01-11 10:01:48
tags:
- C语言
categories:
- 计算机基础

---

看php源码补充的基础知识
<!-- more -->

### 指针变量值占用多少内存

首先应该明白地址空间.对于程序而言,内存被抽象成虚拟内存,每一个进程都把内存看作自己独占在使用.而地址空间就是虚拟内存大小的一个范围.而这个范围取决于字长(即我们常常说的多少位的计算机).比如32位计算机,它可以表示的地址空间为0-2<sup>32</sup>,所以对应的指针变量的值占多少内存空间呢?很显然,指针变量的值实际上就是内存的地址,而32位的地址当然需要用32位即4个字节来保存.

### typedef用法(编译)
- 为基本数据类型定义新的类型`typedef int ElementType`
- 为结构体定义简介类型名称
```c
typedef struct tagPoint{
    double x;
    double y;
    double z;
} Point;
```


### #ifndef等等的用法(预处理部分就完成了)
- 简单的define定义, `#define MAX 100`:把MAX替换成100
- 函数定义,`#define MAX(x,y) (x)>(y)?(x):(y)`:可能存在风险,因为只是简单的文本替换
- 单行定义,如下,比如x=1
    - `#define A(x) T_##x`:`T_1`   (`##`把宏参数与代码中的标识符连接起来,形成一个新的标识符) 
    - `#define B(x) #@x`:`'1'`  (`#@`把宏参数变成单字符,即加单引号)
    - `#define C(x) #x`:`"1"`   (`#`把宏参数字符串化,加双引号)
- 多行定义,在每一个换行的时候需要加一个`\`.
```c
#define MAX(x,y) do{ \
    statement1; \
    statement2; \
}while(0) 
```
- 条件编译
```c
#ifdef WIN32
...
(#else)
...
#endif
```
- 取消宏`#undef MAX`
- 避免重复包含(`.h`文件可能被重复`include`,一般是定义这个文件名前后加下划线,中间点也变成下划线`stdio.h`)
```c
#ifndef _STDIO_H_
#define _STDIO_H_
...
...
#endif
```

### ___`___attribute__ ((visibility("default")))_`

来源于`php-src`,如下.意思就是如果设置成`hidden`就不能被`shared objects`调用
```c
#ifdef PHP_WIN32
#	ifdef SAPI_EXPORTS
#		define SAPI_API __declspec(dllexport)
#	else
#		define SAPI_API __declspec(dllimport)
#	endif
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define SAPI_API __attribute__ ((visibility("default")))
#else
#	define SAPI_API
#endif

SAPI_API void sapi_startup(sapi_module_struct *sf){
    ...
}

```

> In the GNU compiler collection (GCC) environment, the term that is used for exporting is visibility. As it applies to functions and variables in a shared object, visibility refers to the ability of other shared objects to call a C/C++ function. Functions with default visibility have a global scope and can be called from other shared objects. Functions with hidden visibility have a local scope and cannot be called from other shared objects.

### `__declspec()`
```c
#ifdef LIBZEND_EXPORTS
#	define ZEND_API __declspec(dllexport)
#else
#	define ZEND_API __declspec(dllimport)
#endif
```
### extern "C"

作用是编译的时候使C和C++产生相同的符号.因为C++支持重载,编译时会在符号后面添上参数类型.[参见](https://blog.csdn.net/MonroeD/article/details/54880944)

```c

#ifdef __cplusplus
#define BEGIN_EXTERN_C() extern "C" {
#define END_EXTERN_C() }

```
