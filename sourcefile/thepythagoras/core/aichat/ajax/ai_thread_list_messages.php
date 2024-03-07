<?php

//version 3.7Pythagoras-1.1.0
//v.1.1.0 include assistants config

require_once('../../../config.php'); 
require_once($gc['path']['root'].'/config/config.openai_assistants.php'); 
require_once($gc['path']['root'].'/core/aichat/func.openai_assistant_functions.php');

if($gc['openai_chat_in_admin']){
    check_auth();
}

header('Content-Type: application/json');

$json = array();

$request = file_get_contents("php://input"); // gets the raw data
$params = json_decode($request,true); // true for return as array

//$params['thread'] = 'talkto_jimmy'; //thread_BkpnNKRGT1SBLlFopAFmpdD6

if(!isset($params)){
    $json['success'] = false;
    $json['error'] = "Params missing";
    $json['status'] = "Connection error #1b";
}

if(!isset($params['thread']) || !$params['thread']){
    $json['success'] = false;
    $json['error'] = "Thread not specified";
    $json['status'] = "Connection error #2b";
}  

//$json['error'] = "<pre>".print_r($_SESSION, true);

if(!isset($json['error'])){

    $thread_indicator = $params['thread'];

    $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];

    $json['thread'] = $params['thread'];

    $messages = openai_thread_get_messages($thread_indicator);
    if(isset($messages['message_list'])){

        if(count($messages['message_list'])>0){
            $msg_collection = array();
            foreach($messages['message_list'] as $k => $msg){
                $onemsg = array();
                $onemsg['id'] = $msg['id'];
                $onemsg['text'] = $msg['content'][0]['text']['value'];
                $onemsg['time'] = $msg['created_at'];
                $onemsg['assistant_id'] = $msg['assistant_id'];

                if(isset($_SESSION['openai_assistants_by_id'][$msg['assistant_id']]) && isset($assistants[$_SESSION['openai_assistants_by_id'][$msg['assistant_id']]]['name'])){
                    $onemsg['assistant_name'] = $assistants[$_SESSION['openai_assistants_by_id'][$msg['assistant_id']]]['name']; 
                }else{
                    $onemsg['assistant_name'] = "Ai Assistant";
                }
                
                $onemsg['role'] = $msg['role'];
                $msg_collection[] = $onemsg;
            }
            
            $msg_collection = array_reverse($msg_collection); // Reverse the order of elements

            $json['success'] = true;
            $json['thread_id'] = $thread_id;
            $json['messages'] = $msg_collection;
            $json['has_history'] = true;
        }else{
            $json['success'] = true;
            $json['has_history'] = false;
        }
    }else{
        $json['success'] = false;
        $json['error'] = 'Error, failed to get messages list'.print_r($messages,true);
        $json['status'] = "API response error. #5b"; 
    }
}

//$json['session'] = print_r($_SESSION, true);
echo json_encode($json);


/*
[message_list] => Array
        (
            [11] => Array
                (
                    [id] => msg_zZdMbRkpP1wMj9OeWdyfUhgy
                    [object] => thread.message
                    [created_at] => 1706294481
                    [thread_id] => thread_BkpnNKRGT1SBLlFopAFmpdD6
                    [role] => user
                    [content] => Array
                        (
                            [0] => Array
                                (
                                    [type] => text
                                    [text] => Array
                                        (
                                            [value] => Tell me your name and what sort of questions i can ask you.
                                            [annotations] => Array
                                                (
                                                )

                                        )

                                )

                        )

                    [file_ids] => Array
                        (
                        )

                    [assistant_id] => 
                    [run_id] => 
                    [metadata] => Array
                        (
                        )

                )
                */
?>