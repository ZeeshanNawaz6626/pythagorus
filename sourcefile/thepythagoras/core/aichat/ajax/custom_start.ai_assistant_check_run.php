<?php


//this runs inside ai_assistant_check_run.php ajax call, just after first rules set
//any customized scripts or check rules can be added here
//---------------------------------------------


if(!isset($_SESSION['openai_active_assistant'])){
    $json['success'] = false;
    $json['error'] = "No active assistant found, please refresh";
    $json['status'] = "Session expired";
}  


?>