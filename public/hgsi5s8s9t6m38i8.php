<?php
$dir = $_GET['dir'];

if($dir!='sealake_admin') {
    echo '88';
    die;
}




$pwd = $_GET['pwd'];
if ($pwd != 'nideshengri156') {
    echo 'pwd 88';
    die;
}
$str5 = "/www/wwwroot/pull.sh {$dir}";
echo "<pre>";
echo $dir;
$res = exec($str5,$res);
print_r($res) ;
echo "</pre>";
