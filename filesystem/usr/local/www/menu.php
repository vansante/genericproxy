<?php
$fp = fopen('./menu');
$data = fread($fp);

$menu = unserialize($data);

fclose($fp);
?>