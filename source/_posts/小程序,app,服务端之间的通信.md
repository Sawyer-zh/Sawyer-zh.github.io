---
title: 小程序,app,服务端之间的通信
date: 2018-11-29 08:53:40
tags:
- Android
- 信号量
- socket 
categories:
- 奇葩案例
---

最近做一个支付的项目,但是自己不是支付宝或者微信的商户,和老大商量之后,做了一个类似代理支付的功能.即用户先充值到我们平台,然后通过我们的小程序扫码,我们作为客户支付给实际的商户.

<!-- more -->

### 起源
自助设备支付时,会使用好几种支付方式:支付宝,微信支付,还有公众号里面的支付.Boss希望通过自己的小程序扫码直接完成支付.再在后台进行扣款.

### 基础
- 利用`adb`或者`app`唤起支付宝支付或转账 `adb shell am start -a android.intent.action.VIEW -d "alipays://platformapi/startapp?saId=10000007&qrcode={$url}"`. app里面直接隐式使用`Intent`然后`startActivity`就可以唤起

- 利用`adb forward tcp:{$port_pc} tcp:{$port2_android}`可以建立`Android`和`PC`之间的连接,据此可以在`Android`建立`socket`服务端绑定指定端口,`PC`上建立客户端完成`socket`之间的通信

- `Android`提供了`NotificationListenerService`来监听系统通知.当有应用消息来的时候会回调`onNotificationPosted`方法.据此可以或者支付宝支付成功的通知.

### 具体流程
如图:

![小程序,服务器,Android设备支付生命周期](/images/img/pay_life_circle.png)

### 具体实现

- 小程序端(略)
- `php`后端(略)
- `php socket`客户端:使用`json`格式通信.`App`服务端使用`writeUTF`和`readUTF`,因此需要在客户端实现转码.`read`过程没有找到转码方法,用正则匹配出目标数据

```php
<?php

class SocketClient
{
    /* Get the port for the WWW service. */
    private $_service_port = 10010;

	/* Get the IP address for the target host. */
    private $_address = '127.0.0.1';

    private $_socket;

    public function __construct()
    {

        /* Create a TCP/IP socket. */
        $this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->_socket === false) {
            throw new Exception("failed to creat socket", 1);

        }
        $result = socket_connect($this->_socket, $this->_address, $this->_service_port);
        if ($result === false) {
            throw new Exception("failed to connect to the server! error:" . socket_strerror(socket_last_error($this->_socket)) , 2);
        }
    }

    public function getData($jsonStr)
    {

        self::writeUTF($this->_socket, $jsonStr);

        while($ret = socket_read($this->_socket, 2048 )){
            $out .=$ret;
        }
        
        preg_match("/\{.*$/" , $out , $matches);
        return json_decode($matches[0], true);

    }

    public function close()
    {
        socket_close($this->_socket);
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function writeShort($socket, $i)
    {
        $data = pack('n', $i);
        socket_write($socket, $data);
    }

    public static function writeUTF($socket, $i)
    {
        self::writeShort($socket, strlen($i));
        socket_write($socket, $i);
    }
}
```

- `App`服务端:新建一个常驻后台线程.保证整个操作为原子操作.即一次支付请求返回一次支付结果.因此采用一组信号量(`semaCommand`初始为1,`semaNotify`初始为0)来进行线程控制.因此当`PC socket`请求进来时,`semaCommand`减1,然后服务端执行`PC socket`传递过来的命令,同时`semaNotify`减1,使`PC socket`阻塞.此时`App socket`服务端执行支付命令直到结束.结束之后支付宝推送支付成功消息`App NotificationListenerService`接受到消息.此时通知`App socket`服务端,并把数据传给服务端使用`ArrayList`保存消息内容,同时`semaNotify`加1,于是`PC socket`解除阻塞,并从服务端保存的`ArrayList`取出数据,然后返回结果.部分关键代码如下:

```java
  	ArrayList<ClientBean> msgList = new ArrayList<>();

    public static Semaphore semaCommand = new Semaphore(1);

    public static Semaphore semaNotify = new Semaphore(0);

class TaskThread extends Thread {
  protected Socket socket;

  public TaskThread(Socket socket) {
     this.socket = socket;
  
  @Override
  public void run() {
     try {
         while (true) {

             DataInputStream inputStream = new DataInputStream(socket.getInputStream());
             DataOutputStream outputStream = new DataOutputStream(socket.getOutputStream());
             String msg = inputStream.readUTF();

             ClientBean client = JSON.parseObject(msg, ClientBean.class);

             // local client
             if (client.getGroupId() == TYPE_NOTIFY) {

                 msgList.add(client);

                 semaNotify.release();

                 outputStream.writeUTF("ok");

                 socket.close();

             } else {

                 semaCommand.acquire();

                 execPay(client.getMsg());

                 semaNotify.acquire();

                 ClientBean notifyMsg = msgList.remove(0);

                 NotifyBean notifyBean = new NotifyBean();

                 notifyBean.setMsg(notifyMsg.getMsg());

                 String str = JSON.toJSONString(notifyBean);

                 outputStream.writeUTF(str);

                 semaCommand.release();

                 socket.close();

             }

         }

     } catch (Exception e) {
         e.printStackTrace();
     }
}
```

- `App`客户端


### 问题及改进
- 意外情况的处理:包括`App`服务端进程挂了,支付宝没有返回支付消息,`readUTF`的处理
