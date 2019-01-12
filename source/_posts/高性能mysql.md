---
title: 高性能mysql
date: 2018-07-31 16:24:42
tags:
- mysql
categories:
- 计算机基础
---

高性能mysql读书笔记

<!-- more -->

## mysql架构
### mysql的逻辑架构
- 各种服务:连接处理,授权认证,安全等等
- 查询解析,分析,优化,缓存及各种内建函数.存储过程,触发器,视图
- 存储引擎
### 并发控制
- 共享锁/读锁  排它锁/写锁
- 锁粒度
  - 表锁
  - 行级锁
### 事务
- Atomicity原子性
- Consistency一致性
- Isolation隔离性
- Durability持久性
- 隔离级
  - READ UNCOMMITTED 读取未提交内容 脏读
  - READ COMMITTED 读取提交内容 不可重复读
  - REPEATALBE READ 可重读 幻读 (mysql 默认事务隔离级)
  - SERIALIZABLE 可串行化
- 死锁
- 事务日志
- mysql中的事务
  - AUTOCOMMIT 自动提交
  - 在事务中混合使用存储引擎 对于非事务性表进行事务操作通常不会得到警告或报错 但有时回滚事务会产出警告信息
  - 隐式和显示锁定
    - 隐式 事务执行过程中任何时候可以获得锁,commit或者rollback释放
    - 显式 select ... lock in share mode ; select ... for update;
### 多版本并发控制
### mysql的存储引擎
- show table status;
- myisam
- innodb

## 寻找瓶颈:基准测试与性能分析
### 为什么要进行基准测试
- 

## 架构优化和索引
### 选择优化的数据类型
- 更小通常更好
- 简单就好
- 尽量避免null

