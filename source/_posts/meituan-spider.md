---
title: 爬取美团数据遇到的问题以及解决思路
date: 2019-08-16 23:28:50
tags: 
- PHP 
- 爬虫
categories:
- 奇葩案例 
---

最近要采集美团网上的商家数据放到自己库里以供用户查找附近商家

<!-- more -->

## 初步尝试

### 找相应数据对应的页面(app,小程序,网站...)

```
http://bj.meituan.com/meishi/c20003/pn1/
```

```
// api
http://bj.meituan.com/meishi/api/poi/getPoiList?cityName=北京&cateId=20003&areaId=0&sort=&dinnerCountAttrId=&page=2&userId=&uuid=f0996a7a0ad84f0392de.1565774935.1.0.0&platform=1&partner=126&originUrl=http://bj.meituan.com/meishi/c20003/pn2/&riskLevel=1&optimusCode=10&_token=eJyNT8mum0AQ/Je5GnmGbWAs5YABWyReMTxiPflgMBgw++ohyr9nkPIOuUVqqZYulbp/gcZ6gBWPEEGIA0PYgBXgl2iJAQe6lm1kLKsKL2AZK4QDwb8eQQIH/ObDAKtPRUUckchtNmymP3kiII5HKrpxX1wWbpwgsZlTFguBuOuqFYR+uszDpOvvxTIoc8h4GycwEBBCIqwKAbJz/icKWHHuzMUSFjkBS7Pxmg2G97/Yfek9e5YVt8mzYCz8Pj5Shz++De1s9wsau52ibMfLsz/r/tUzsZFqpkmEITjDtn1YorUNL6KmT8+TOB6aOlS9gozyVV8vHmvfTNwxMDTrcD39VNUrNRQCC34B911hLWya5DzV4L6KiEn7t1Hj6EW35bmOkimlJOveUR79gC7m3a3ajSNN3Q3vZ+GwqYao 
```

## 分析链接相关参数

尝试：

- 直接请求
- 修改分页等参数请求

都能成功！！！

## 问题以及解决方案

### token时效太短

token生成规则:

- 生成签名
- 生成token

```php
public $queryArr= [
    "cityName"=>"北京", // var
    "cateId"=> "17", //
    "areaId"=> "0", //
    "sort"=>"",
    "dinnerCountAttrId"=>"",
    "page"=>"2", //
    "userId"=>"",
    "uuid"=>"8ab42457-ae82-4f23-aef5-12eef071b42d", //
    "platform"=>"1",
    "partner"=>"126",
    "originUrl"=>"http://bj.meituan.com/meishi/c17/pn2/", //
    "riskLevel"=>"1",
    "optimusCode"=>"10",
];

public $tokenArr = [
    "rId"=>100900,
    "ver"=>"1.0.6",
    "ts"=>1563631753444, //
    "cts"=>1563631753604, //
    "brVD"=>[ 834,949 ],
    "brR"=>[
        [1920,1080],
        [1920,1052],
        24,
        24,
    ],
    "bI"=>[
        "http://bj.meituan.com/meishi/c17/pn2/", //
        "http://bj.meituan.com/meishi/c17/pn1/",//
    ],
    "mT"=>[],
    "kT"=>[],
    "aT"=>[],
    "tT"=>[],
    "aM"=>"",
    "sign"=>'',
];

public function genSign()
{
    $queryArr = $this->queryArr;

    ksort($queryArr);

    $ret = [];

    foreach ($queryArr as $k => $v) {
        $ret[] = "$k=$v";
    }

    $ret = join('&', $ret);

    $ret = '"'.$ret.'"';

    $signCal = base64_encode(zlib_encode($ret, ZLIB_ENCODING_DEFLATE));

    $this->sign = $signCal;

    return $signCal;
}


$signCal = base64_encode(zlib_encode($ret, ZLIB_ENCODING_DEFLATE));

$tokenCal = base64_encode(zlib_encode(json_encode($tokenArr, JSON_UNESCAPED_SLASHES), ZLIB_ENCODING_DEFLATE));
```

### 403错误 

更换uuid

### 返回空内容,跳转到图形验证码界面

```
https://verify.meituan.com/v2/web/general_page?action=spiderindefence&requestCode=6394e74446ec4ad7bb0f546c7c0f9db1&platform=1000&adaptor=auto&succCallbackUrl=https%3A%2F%2Foptimus-mtsi.meituan.com%2Foptimus%2FverifyResult%3ForiginUrl%3Dhttp%253A%252F%252Fbj.meituan.com%252Fmeishi%252Fc11%252F
```

输入验证码

## 结论及建议 

### 爬数据关键:

- 找到正确完整的数据源
- 尝试

### 反爬虫策略最关键点: 

- 签名
- IP和图形验证码的配合
