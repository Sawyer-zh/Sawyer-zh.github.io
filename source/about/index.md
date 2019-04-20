---
title: about
date: 2019-01-12 21:57:16
---

# Description

My name is Hang Zhang, and Sawyer is my English name. 

I graduated from SWPU majoring Petroleum Engineering at 2013, and then I went further study in CUP majoring Oil and Gas Field Development Engineering. When I graduated at 2016, the price of oil was extremely low and the enterprise can't make profit while the internet industry was very popular. In this way, I chose software development Engineer as my career.

I am now a PHPer in a small company, and my technology stack is LNMP. I am attracted by the spirit of freedom in open source software.

Here is my contact information:
github:https://github.com/Sawyer-zh
wechat:zh--007
e-mail:254091355@qq.com
blog:Sawyer-zh.github.io / www.keepsunshine.cn

> the ability to see the machine as more than when you were first led up to it, that you can make it more.

---

# 联系方式

- 手机：15001332302(北京)/13827474582(深圳)  最优联系时间周末,节假日
- Email：sawyerzhang2017@gmail.com / 254091355@qq.com 
- 微信号：`zh--007`

---

# 个人信息

 - 张航/男/1992 
 - 硕士/中国石油大学(北京) 油气田开发工程系 
 - 工作年限：3年
 - 技术博客：http://Sawyer-zh.github.io / http://keepsunshine.cn
 - Github：https://github.com/Sawyer-zh
 - 期望职位：PHP高级程序员
 - 期望城市：北京

---

# 工作经历

## 北京投融有道科技有限公司（ 2016年4月 ~ 至今 ）

### 自助设备项目 

#### 充电自助项目

- 工作量
  - 前端小程序
  - 后端api
  - 后端数据收集

#### 自助吧快速查找附近设备

- 主要功能
  - 支付功能
  - Android app 

- 遇到的困难

  利用我们自己的小程序扫码,支付其他商家产生的支付宝二维码链接

- 解决方案
  
  小程序端扫码产生的链接传到服务器,然后通过openVPN把线上服务器和本地局域网服务器关联,使得远程服务器可以使用RPC调用本地服务器方法.本地服务器通过adb forward 与Android自己写的一个App进行通信.在Android上完成支付功能.
- 效果

  实现了支付的过程.但不完美

- [技术细节](https://sawyer-zh.github.io/2018/11/29/%E5%B0%8F%E7%A8%8B%E5%BA%8F,app,%E6%9C%8D%E5%8A%A1%E7%AB%AF%E4%B9%8B%E9%97%B4%E7%9A%84%E9%80%9A%E4%BF%A1/)
    
  - 利用信号量和共享内存保证Android端每部手机在使用的过程中无法被其他请求占用
  - 利用adb forward 连接了linux服务器和每个android设备
  - 利用socket连接使本地服务器与app通信
  - app通过拦截notification来获得支付金额从而返回本地服务器
  - 通过openVPN连接生产服务器和本地服务器,从而可以使用RPC远程调用本地服务方法


### 理财项目 

#### 最红星期五

抢红包逻辑的实现,使用红包逻辑的实现

- 遇到的问题

  - 抢红包因为需求的改变,需要最后一个红包保留为发红包的用户.导致还剩两个红包的时候,两个用户同时抢了倒数第二个红包,而没有给发红包的用户保留.
  - 使用红包时,用户可以同时使用多个红包,但是使用的红包的100倍不能超过用户投资的金额.需要给用户提示一个最优的红包选择方案

- [解决问题](https://sawyer-zh.github.io/2018/07/29/dp/)

  - 因为无法准确判断倒数第二红包,所以改为判空,如果为空并且用户不是发红包用户,则把红包重新放进红包队列,这样可以重新来一次.
  - 利用动态规划,一个二维数组保存记录过程.计算最优解


#### 预约投资项目

发预约标, 上线时候, 未预约用户只能投资未预约部分, 一定时间之后, 若标未满才能投资剩余部分.

- 遇到问题

  20W的标,预约8万,在剩余可投2356左右的时候,两个用户同时投了2356,都成功了,导致预约用户实际可投小于预约金额

- 解决问题

  投资是原子操作,但是和检查剩余可投的时候并非是原子操作.需要保证这个逻辑为原子性.比较困难,后来改了逻辑,预约的时候只能预约的人投资,其他人不能看到也不能投.

#### 满标之后的逻辑做成异步处理

- 遇到问题:

  最后一个用户投资完成之后,需要执行一堆业务逻辑,有时候会造成超时.

- 解决问题:
  
  利用exec函数,把输出重定向`/dev/null` 并设置后台执行(`&`),即把逻辑封装成了异步操作.然后给用户返回成功.


#### 网站和微信端分离

- 遇到问题:

  微信端有时候会因为一些活动,被用户举报,导致域名被封.在微信中无法正常显示

- 解决问题

  把微信部分独立出来.但是需要同步微信登录信息和cookie中用户信息.利用ucenter同步登录微信端,和网站端.

 
### 其他项目

- 金融湾Android端App
- 邮币卡投资项目
- 新三板股价监控小程序
- 公众号红包
- 其他日常开发维护工作
- 个人微信公众号
- 个人技术博客
- 个人一些开源项目
 
# 开源项目和作品

## 开源项目

- [一个股票交易助手](https://github.com/Sawyer-zh/trader)

## 技术文章

- [动态规划在工作中的一个应用](http://keepsunshine.cn/2018/07/29/dp/#more)
- [一个解决支付宝支付的不靠谱手段](http://keepsunshine.cn/2018/11/29/%E5%B0%8F%E7%A8%8B%E5%BA%8F,app,%E6%9C%8D%E5%8A%A1%E7%AB%AF%E4%B9%8B%E9%97%B4%E7%9A%84%E9%80%9A%E4%BF%A1/#more) 

## 翻译文章

- [指针是什么](http://keepsunshine.cn/2019/04/14/%E6%8C%87%E9%92%88%E6%98%AF%E4%BB%80%E4%B9%88/)

# 技能清单

以下均为我熟练使用的技能

- 语言：PHP , C ,Js ,Java ,Python
- 框架：Lumen/Laravel , ThinkPHP
- 数据库相关：MySQL/Redis
- 版本管理、文档和自动化部署工具：Svn/Git/Composer
- 单元测试：PHPUnit
- 云和开放平台：微信应用开发/阿里云/腾讯云/亚马逊云均有个人服务器
- linux: 熟悉常用命令和vim
- Nginx: 会编写常用配置文件
- docker: 会编写docker-compose.yml文件,以及基本的操作

---

# 致谢
感谢您花时间阅读我的简历，期待能有机会和您共事。

