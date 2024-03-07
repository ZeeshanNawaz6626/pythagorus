<?php


session_start();

echo "<pre>";
var_dump($_SESSION);
echo "</pre>";


$_SESSION['requests_aigency_v1_firsterror'] = 0;
$_SESSION['requests_aigency_v1'] = 0;

echo 'limits cleared';