<?php

include_once("c43.class.php");

$c43=new C43($argv[1]);

echo "STATUS= ".$c43->status."\n";
echo "MENSAJE= ".$c43->message."\n";

var_dump($c43);

?>
