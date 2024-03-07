<?php


//this runs inside ai_assistant_message.php ajax call, just before the json output
//any customized scripts can be added here
//---------------------------------------------


if(!isset($json['error'])){
    //count messages in this assistant
    if(!isset($_SESSION['ai_chat_messages_count'])){
        $_SESSION['ai_chat_messages_count'] = 0;
    }
    $_SESSION['ai_chat_messages_count'] += 1;
    
    $json['replies'] = $_SESSION['ai_chat_messages_count'];

    setActiveAssistant(); //update current assistant based on number of messages sent
}

?>