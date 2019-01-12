---
title: php文件操作
date: 2018-07-27 17:33:52
tags:
- php
- php文件操作
categories:
- 源码分析
---

介绍一些php操作文件的方法...项目中需要把log写成文件,然后利用脚本把log发送到专门的服务器,实现log与正式的服务器分离.减轻分析log给正式服务器造成的压力.

<!-- more -->

#### logmeta.cfg
该文件记录了写入文件的名字,记录的条数,以及上次更新的时间

```json
{
    "logName":"NewHawk.log",
    "MAX_LOG_COUNT":10,
    "logCount":0,
    "logFile":0,
    "lastUpdate0":1494299890,
    "lastUpdate1":1494299940
}
```

#### sendmeta.log
该文件记录了上传log文件的信息:上传条数,当前文件指针的位置,当前上传的文件名称,最近上传时间
```json
{
    "currCount":0,
    "currPos":0,
    "currFile":0,
    "lastUpdate":1494299943
}
```

#### 写文件 write.php
名称为0/1的两个文件来回写,达到设定的最大条数切换文件
读配置文件的时候加上排它锁避免多个进程同时读到,并且更改配置文件

```php
<?php

$i = 1;
while (1) {
    my_log($i);
    $i ++;
    sleep(rand(1,3));
}

function my_log($data)
{
    clearstatcache();
    $cfgFile = 'logmeta.cfg';
    $cfp = fopen($cfgFile, 'r+');
    if (flock($cfp, LOCK_EX)) {
        $cfg = json_decode(fread($cfp, filesize($cfgFile)));
        if (!$cfg) {
            flock($cfp, LOCK_UN);
            fclose($cfp);
            return false;
        }
        $fileBaseName = $cfg->logName;
        if ($cfg->logCount >= $cfg->MAX_LOG_COUNT) {
            $cfg->logCount = 1;
            $fileName = $cfg->logFile = ($cfg->logFile + 1) % 2;
            $mode = 'w+';
        } else {
            $cfg->logCount++;
            $fileName = $cfg->logFile;
            $mode = 'a+';
        }
        $lastUpdate = 'lastUpdate' . $fileName;
        $cfg->$lastUpdate = time();

        ftruncate($cfp, 0);
        fseek($cfp, 0);
        fwrite($cfp, json_encode($cfg));
        flock($cfp, LOCK_UN);
        fclose($cfp);

        $fp = fopen($fileBaseName . $fileName, $mode);
        fwrite($fp, $data . "\n");
        fclose($fp);
    }
}
```

#### 上传文件send.php

读取sendmeta.log和logmeta.cfg文件.找到未上传的部分上传.

```php
<?php

function my_send()
{
    clearstatcache();
    $logFile = "logmeta.cfg";
    $sendFile = "sendmeta.log";
    $cfp = fopen($logFile, "r");
    $sdp = fopen($sendFile, "r+");
    if (!$cfp || !$sdp) {
        return "can not open file";
    }

    $cfgContent = fread($cfp, filesize($logFile));
    $sdContent = fread($sdp, filesize($sendFile));

    if (!$cfgContent || !$sdContent)  {
        fclose($cfp);
        fclose($cdp);
        return "can not read file";
    }
    $cfg = json_decode($cfgContent);
    $sd = json_decode($sdContent);

    if ($sd->currCount >= $cfg->MAX_LOG_COUNT) {
        $sd->currCount = 0;
        $sd->currPos = 0;
        $sd->currFile = ($sd->currFile + 1) % 2;
    }

    $lastTime = 'lastUpdate'.$sd->currFile;
    $updated = $sd->lastUpdate < $cfg->$lastTime;

    if (!$updated) {
        return;
    }

    $addSize = filesize($cfg->logName . $sd->currFile) - $sd->currPos;
    if ($addSize>0) {
        $f = fopen($cfg->logName . $sd->currFile, 'r');
        fseek($f, $sd->currPos);
        $content = fread($f, $addSize);
        $addCount = send_log($content);
        $sd->currCount += $addCount;
        $sd->currPos += $addSize;
        $sd->lastUpdate = $cfg->$lastTime;
        ftruncate($sdp, 0);
        fseek($sdp, 0);
        fwrite($sdp, json_encode($sd));
    }
    
    fclose($sdp);
    fclose($cfp);
    
}

function send_log($data)
{
    $ret = explode("\n", $data);
    return sizeof($ret) - 1;
}

while (1) {
    my_send();
    sleep(1);
}
```

#### 一些相关文件操作
* fopen(filename, mode)
* flock(handle, operation)
* fread(handle, length)
* fwrite(handle, string)
* ftruncate(handle, size)
* fseek(handle, offset)
* fclose(handle)
* filesize(filename)

[reference](http://php.net/manual/zh/function.fopen.php/)

#### 多进程中的文件操作

