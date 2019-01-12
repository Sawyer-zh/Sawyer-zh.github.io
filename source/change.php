<?php

$msg = file_get_contents(__dir__ . '/log.txt');

$array = explode("\n" , $msg);

$len = sizeof($array);


$key = [];

$value = [];

for($i = 0 ; $i < $len ; $i++){
    
    if($i % 2 == 0){
        $key[] = $array[$i];
    }else{
        $value[] = $array[$i];
    }
}

$hash = array_combine($key ,$value);

var_dump($hash);

$headerTmpl = " 
---
title: %s
date: %s
tags:
---

";



$basePath = '/srv/hexo-blog/source/_posts';

$dir = opendir($basePath);

while($file = readdir($dir)){

    if(in_array($file,['.','..']) || is_dir($basePath .'/'. $file)  || strpos( $file , '.md') === false  ){
        continue;
    }


    $name = explode("." , $file) [0];

    echo $name, "\n";

    //$ctime = date( 'Y-m-d H:i:s' ,filectime($basePath .'/'. $file)) ; 

    $ctime = $hash[$name];

    echo $ctime, "\n";

    $origin = file_get_contents($basePath. '/' . $file);
    

    // exit
    $header = sprintf($headerTmpl , $name , $ctime);

    echo $header , "\n";
   
    file_put_contents($basePath . '/' . $file , $header . $origin);

}
