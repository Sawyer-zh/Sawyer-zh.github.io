<?php

$dir = opendir(__dir__ . '/_posts');

while($file = readdir($dir)){

    if(in_array($file,['.','..']) || is_dir(__dir__ . './_posts/' . $file) ){
        continue;
    }

    echo date( 'Y-m-d H:i:s' ,filectime(__dir__ . '/_posts/' . $file)) , "\n";


}
