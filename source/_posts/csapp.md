---
title: csapp
date: 2018-12-22 16:34:07
tags:
- 计算机基础
- 汇编
categories:
- 计算机基础 

---

<深入理解计算机系统>笔记,来源于ppt和csapp.
<!-- more -->


### machine basics

`gcc -Og -S xxx.c`:生成汇编代码

`c`代码变成可执行文件步骤:`xx.c`-(compiler)->`xx.s`-(assembler)->`xx.o`-(linker)->`xx`

move & arithmetic operation:
 - `movq`   `src,dest`  `dest=src`  src can be $const
 - `leaq`   `src,dest`  `dest=address computed by expression src`
 - `addq`   `src,dest`  `dest=desc+src`
 - `subq`   `src,dest`  `dest=desc-src`
 - `imulq`  `src,dest`  `dest=dest*src`
 - `salq`   `src,dest`  `dest=dest<<src`
 - `sarq`   `src,dest`  `dest=dest>>src`
 - `shrq`   `src,dest`  `dest=dest>>src`
 - `xorq`   `src,dest`  `dest=dest^src`
 - `andq`   `src,dest`  `dest=dest&src`
 - `orq`     `src,dest`  `dest=dest|src`

addressing modes:
 - `D(Rb,Ri,S)` : `Mem[Reg[Rb] + S*Reg[Ri] + D]` 
    - D : constant "displacement" 1,2,or 4 bytes
    - Rb: base register (any of 16 integer register)
    - Ri: index register (any except for `%rsp`)
    - S : scale( 1,2,4 or 8)

information about currently executing program:
 - tmporary data : `%rax`,..
 - location of runtime stack : `%rsp`
 - location of current code control point : `%rip`
 - status of recent tests : CF ,ZF ,SF ,OF

# machine data

satisfying alignment with structures:
initial address & structure length must be multiples of K(largest alignment of any element)
```c
struct S1{
    char c; //1 byte  
    int i[2];//8 bytes   initial address should be multiples of 4 so add 3bytes before
    double v;// 8 bytes with the same reason add 4bytes before
}
```

