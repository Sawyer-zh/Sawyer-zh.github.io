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
- 连接,选择数据库,创建集合(类似于table),插入文档(类似于一条记录)
     - mongo dbname -u username -p password 直接mongo进去操作出现unauthorized
     - use dbname  如果不存在则创建
     - db.dropDatabase() 删库
     - db.createCollection("mycol", {capped : true, autoIndexId : true, size : 6142800, max : 10000 }) 
     - db.test_collection.insertOne({"test":"test"}) 直接插入文档自动创建集合
     - show collections
     - db.collectionname.drop()
     - 

