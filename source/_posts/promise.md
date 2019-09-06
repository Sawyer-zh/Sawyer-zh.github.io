---
title:  从小程序的wx.request到js处理异步操作的方法 
date: 2019-08-14 20:33:59
tags:
- js
- 异步回调
- promise
- 小程序

---

# 问题由来--回调地狱

先看一个例子:小程序检查session_key是否过期，登录
```js
wx.checkSession(
    success: function(){
    },
    fail: function() {
        wx.login({
            success: function(res){
                wx.request({
                    data:{},
                    success: function(){
                    },
                    fail: function(){
                    }
                })
            },
            fail: function(){
            }
        })
    }
)
```

优点:

- 比较直接

缺点:

- 影响可读性
- 不利于维护





