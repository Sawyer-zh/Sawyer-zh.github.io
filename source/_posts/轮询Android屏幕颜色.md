---
title: 轮询Android屏幕颜色
date: 2018-12-02 16:12:58
tags:
- linux
- Android
- 信号量
- 轮询
categories:
- 奇葩案例
---

轮询Android手机屏幕指定点颜色

<!-- more -->

### 问题由来

调起支付宝支付界面,需要等待手机反应过来,才能够执行点击事件完成支付.要么`sleep`足够长的时间,要么进行轮询.

### 基础

- 读取`framebuffer`数据,从中找出指定像素点的颜色.
- `screencap`截屏然后从文件中获得指定像素点的颜色.
- 利用两个线程实现轮询

### 具体采用方法

- 采用`screencap`方式获取屏幕文件信息`tmp.dump`文件


屏幕`(x,y)`位置的对应的颜色文件位置在`position = width*y + x +3`,为十六进制使用`dd if="position" bs=4 count=1 skip=" + position + " 2>/dev/null | hd`可以打印出十六进制数据类似:`0x00000000 FF FF FF FF                                     ....`,遗憾的在app里面写的时候无法获得,但是去掉`| hd`之后可以获得数据,于是把这个数据转换成16进制数据.可以得到想要的数据.

- 采用一对信号量对读写进行控制:读者首先阻塞,待写者写入数据之后,读者运行,写者`sleep`一段时间,然后继续,直到读者读到想要的数据,跳出循环.

```java

public static Semaphore semaScreenRead = new Semaphore(0);

public static Semaphore semaScreenCap = new Semaphore(1);

public static final String LOCATION = "/sdcard/tmp.dump";

public static final String BUTTON_COLOR = "23 94 E5 FF";


public static boolean ready = false;

private void tellIfReady() {
        // reader

    new Thread(new Runnable() {
        @Override
        public void run() {
            while (true) {
                Log.e("read loop", "entered");
                try {
                    semaScreenRead.acquire();
                    int position = width * AXIS_Y + AXIS_X + 3;
                    String ret = execShellCmd("dd if='" + LOCATION + "' bs=4 count=1 skip=" + position + " 2>/dev/null ", true);
                    Log.e("read ret:", ret + "this is ret");
                    if (ret.contains(BUTTON_COLOR)) {
                        ready = true;
                        semaScreenCap.release();
                        break;
                    }
                    semaScreenCap.release();


                } catch (Exception e) {
                    e.printStackTrace();
                    Log.e("read error", "occurred!");
                }
            }
        }
    }).start();


    while (true) {
        Log.e("write loop", "entered");
        try {
            semaScreenCap.acquire();

            execShellCmd("screencap " + LOCATION, false);

            semaScreenRead.release();

            Thread.sleep(100);

            if (ready) {

                break;
            }

            Log.e("in cap ready status", String.valueOf(ready));

        } catch (Exception e) {
            e.printStackTrace();
            Log.e("write error", "occurred");
        }
    }

    ready = false;
}
```


