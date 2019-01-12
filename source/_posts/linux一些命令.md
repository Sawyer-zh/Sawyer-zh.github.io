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

### man
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

如果有多个如`kill`, 可以使用`man kill.2`查看系统调用的用法

### ipcs , ipcrm

