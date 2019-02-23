---
title: linux一些命令
date: 2018-12-29 14:02:11
tags: 
- linux
categories:
- 计算机基础
---

平时用到的,感觉会忘记的

<!-- more -->

# 基本命令

## 帮助相关

### man , whatis , info , which

这个命令千万不能忘记!!!
`man man`: 查看`man`的用法,很多命令后面有个括号括着一个数字,表示这个命令是属于哪个部分的.

 - 1   Executable programs or shell commands
 - 2   System calls (functions provided by the kernel)
 - 3   Library calls (functions within program libraries)
 - 4   Special files (usually found in /dev)
 - 5   File formats and conventions eg /etc/passwd
 - 6   Games
 - 7   Miscellaneous (including macro packages and conventions), e.g. man(7), groff(7)
 - 8   System administration commands (usually only for root)
 - 9   Kernel routines [Non standard]

如果有多个如`kill`, 可以使用`man kill.2`查看系统调用的用法 , 或者`man 2 kill`

`man -k keyword`:要是部分命令记不全了可以使用这个.如`man -k ipc`

`whatis`:一行简介

`info`:详细文档

## 文件操作相关

### mkdir , rm  , mv , cp

### cd , pwd

### ls ,ls -lrt

### find , locate

`find ./ -name "*.c" -exec rm {}`:递归当前目录及子目录删除`.c`文件

`locate string`: find 是实时查找,locate是根据文件索引信息

`updatedb`

### cat, vi, head, tail, more, diff

`tail -f`

### egrep, grep


### chown, chmod

### ln 

### | , ; , || , && , > , >> , < , & 

### .profile , .bashrc 

`export PATH`


## 文本处理

### `find . \( -name "*.txt" -o -name "*.pdf" \) -print`:查找pdf和txt文件

- 定制:
  - `-maxdepth 1`:
  - `-type f`: f / l / d
  - `-regex ".\(\.txt|\.pdf)$"`:
  - `-atime 7`: mtime ctime (访问,修改,元素或者权限变化 单位:天) ,分钟是-amin
  - `-size +2k`: +大于,-小于
  - `-perm 644`:权限
  - `-user sawyer`:用户名

- 后续执行动作
  - `-delete`
  - `| xargs rm`
  - `-exec rm {} \` : {}是一个特殊的字符串,对于每一个匹配的文件,他会替换成相应的文件名
  - `-exec ./commands.sh {} \` 
  - `-printO`:使用\O作为文件的定界符,这样就可以搜索包含空格的文件

### `grep pattern file`
 
  - `-o`:只输出匹配的文本行
  - `-v`:只输出没有匹配的文本行

### `xargs`

`ps -ef | grep php | grep  master | awk '{print $2}' | xargs sudo kill -USR2`:重启php-fpm


  - `-d`:定义界定符
  - `-n`:指定输出为多行
  - `-I {}`:指定替换字符串
  - `-0`:指定0为输入界定符

### `sort`

  - `-n`:按字数进行排序
  - `-d`:按字典序进行排序
  - `-r`
  - `-k N`
 
### `uniq`

### `tr`


## 磁盘管理

###  `df` , `du` 

`du -sh `ls` | sort`: `for i in `ls`; do du -sh $i;done | sort`

  - `-s`
  - `-h`

### `tar`

`tar -xvf demo.tar`

  - `-x`:z,j,J(gz,bz2,xz)
  - `-c`
  - `-v`
  - `-f`

## 查询进程

### `ps`, `top`, `lsof`,`kill`

## 性能监控

### `sar` , `free` , `df` ,`du`

## 网络工具

### `netstat` , `lsof`

`netstat -tnlp`

### `route` ,`ping` ,`ifconfig` , `traceroute` ,`host`

### `wget` , `ssh`, `scp`

## 用户管理工具

### `useradd`,`passwd`,`userdel`

### `groups`,`usermod`

### `chown` , `chmod`

### 环境变量

`/etc/profile`,`/etc/bashrc`:系统全局环境变量
`~/.profile`,`~/.bashrc`:用户目录下的私有环境变量设定

profile 只能登入的时候执行一次,而bashrc执行shell脚本就会使用一次

## 系统管理及IPC资源

### `uname` , `/proc/cpuinfo` ,`/proc/meminfo`,`arch` ,`date` ,`tzselect`

### `ipcs` , `ulimit`

程间通信的共享内存,信号量和消息队列

## 其他

### xdg-open

使用用户设置的软件来打开文件


# 进阶命令


