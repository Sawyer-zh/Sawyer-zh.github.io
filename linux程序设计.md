### shell语法
#### 变量
声明:不需要声明,直接赋值;区分大小写;变量存储类型是字符串;使用read 变量名 来用这个变量名保存用户输入字符串
访问:变量前面加$
注意空格

* 引号: 单引号,双引号  反斜杠
* 环境变量 大写字母
  * $HOME
  * $PATH
  * $PS1:命令提示符如#,$
  * $PS2:二级命令提示符如>
  * $IFS:输入域分隔符,通常是空格
  * $0:shell脚本的名字
  * $#:传递脚本参数个数
  * $$:进程号
* 参数变量
  * $1,$2,...:脚本程序的参数
  * $*:列出所有参数,使用环境变量中的IFS
  * $@:$*的变体,即使IFS为空,参数也不会挤在一起

#### 条件
test / [ 命令   
如果把then 和if 放在同一行必须用;把test语句和then分隔开
 * 字符串比较
 * 算数比较
 * 文件条件测试

#### 控制结构
 * if ... then ... elif ... then ... else ... fi
 * for ... in ... do ... done
 * while ... do ... done
 * case ... in ... ... ) ...;;esac
 * 命令列表
    * AND && 前面的命令执行成功的情况下,才执行后面的命令
    * OR || 前面一条命令执行失败才执行后面的命令

#### 函数
 声明:fun_name(){}
 调用:fun_name

#### 命令
 * break
 * :
 * continue
 * .
 * echo
 * eval
 * exec
 * exit n
 * export
 * expr
 * printf
 * return
 * set
 * shift
 * trap
 * unset
 * find
 * grep

#### 命令的执行
 * 算数扩展 : $(())
 * 参数扩展 : ${param:-default} ...

#### here 文档
 * << !xxx!

#### 调试
 * sh -n/v/x/u

### 