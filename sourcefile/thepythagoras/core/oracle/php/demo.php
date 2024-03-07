<?php 
$chat_number = $gc['free_responses'];
if (!isset($_SESSION['requests'])) {
  $_SESSION['requests'] = 0;
}

if ($_SESSION['requests'] >= $chat_number) {
    echo json_encode([
        "status" => 0,
        "message" => "The maximum limit of conversations in demo mode has been reached, please login or register to continue.",
    ]);
  die();
}
$_SESSION['requests']++;

