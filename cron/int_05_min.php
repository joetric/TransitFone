<?php
$ch = curl_init();
$timeout = 5; // set to zero for no timeout
curl_setopt ($ch, CURLOPT_URL, 'http://transitfone.com/index.php/import/septa_detours');
curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
$file_contents = curl_exec($ch);
curl_close($ch);
echo $file_contents;