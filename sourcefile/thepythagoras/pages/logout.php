<?php
session_start();

require("../config.php");
only_by_permalink();


if(isset($_COOKIE[$gc['cookie_name']])){
    setcookie($gc['cookie_name'], "", time()-(60*60*24*100),"/",$_SERVER['HTTP_HOST'],true,false);
}


session_unset();
session_destroy();


header("Location: {$gc['path']['web_root']}");
?>