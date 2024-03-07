<?php 

//API.PHP restrictions

//session_start(); //different from the session loaded in config.php
$chat_number = $gc['free_responses'];




if (!isset($_SESSION['requests_aigency_v1'])) {
  $_SESSION['requests_aigency_v1'] = 0;
  $_SESSION['requests_aigency_v1_firsterror'] = 0;
}

if ($_SESSION['requests_aigency_v1'] >= $chat_number) {
  //echo "@@@@1";
  if($_SESSION['requests_aigency_v1_firsterror'] > 2){
    //echo "@@@@1a";
    //if already shown first error as chat msg, show the rest as red warnings
    echo "data: ".json_encode([
      "status" => 0,
      "message" => "The maximum limit of conversations has been reached, please login or register to continue.",
    ]);

    die();
  
  }else{
    //echo "@@@@1b";
    //show first error as a chat message
    echo "data: [PREMIUM]"; 

    $_SESSION['requests_aigency_v1_firsterror'] += 1;
    die();
  }
    
}



$_SESSION['requests_aigency_v1'] += 1;

