### mongodb
#### 1.场景
1.  没有任何需求的爬虫...
2.  全部给存起来...
3.  直接存文档...

#### 2.安装
* docker很方便了.写compose文件,直接使用
* 踩坑[The default Docker setup on Windows and OS X uses a VirtualBox VM to host the Docker daemon. Unfortunately, the mechanism VirtualBox uses to share folders between the host system and the Docker container is not compatible with the memory mapped files used by MongoDB (see vbox bug, docs.mongodb.org and related jira.mongodb.org bug). This means that it is not possible to run a MongoDB container with the data directory mapped to the host.](https://hub.docker.com/_/mongo/).大致就是说window和os x中的docker使用的是virtualbox VM,用它实现宿主和容器之间共享文件的机制和MongoDB不同,因此不能把数据目录放到宿主机上.事实证明总是报错:file busy.因此直接把数据放到容器里面了
* yaml文件
```yaml
mongodb:
  image: mongo:4.0
  container_name: mongodb
  environment:
      MONGO_INITDB_ROOT_USERNAME: root
      MONGO_INITDB_ROOT_PASSWORD: root
  ports:
    - 27017:27017
```

#### 3.常用操作
[文档](https://docs.mongodb.com/manual/introduction/)