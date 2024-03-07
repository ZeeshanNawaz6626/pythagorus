<?php
//version 3.6Pythagoras-2.1.0
//v.2.1.0 added custom file inclusion before json output
//v.2.0.0 no longer init things here, they must be prepared in session before used here
require_once('../../../config.php'); 
require_once($gc['path']['root'].'/config/config.openai_assistants.php'); 
require_once($gc['path']['root'].'/core/aichat/func.openai_assistant_functions.php');

if($gc['openai_chat_in_admin']){
    check_auth();
}

//===================================================================================================

header('Content-Type: application/json');

$json = array();


$request = file_get_contents("php://input"); // gets the raw data
$params = json_decode($request,true); // true for return as array




//assistant indicator
if(!isset($params['assistant']) || !$params['assistant']){
    $json['success'] = false;
    $json['error'] = "Assistant not specified";
    $json['status'] = "Call error #7";
    echo json_encode($json);
    die();
}else{
    $assistant_indicator = $params['assistant'];
}

//thread indicator
if(!isset($params['thread']) || !$params['thread']){
    $json['success'] = false;
    $json['error'] = "Thread not specified";
    $json['status'] = "Call error #8";
    echo json_encode($json);
    die();
}else{
    $thread_indicator = $params['thread'];
}  

//configured assistant
if(!isset($assistants[$params['assistant']])){
    $json['success'] = false;
    $json['error'] = "Requested assistant [{$params['assistant']}] is not available";
    $json['status'] = "Invalid assistant #9";
    echo json_encode($json);
    die();
}  

//loaded assistant
if(!isset($_SESSION['openai_assistant_'.$assistant_indicator]['id']) || !$_SESSION['openai_assistant_'.$assistant_indicator]['id']){
    $json['success'] = false;
    $json['error'] = "{$params['assistant']} not loaded";
    $json['status'] = "Assistant not loaded #15";
    echo json_encode($json);
    die();
}else{
    $assistant_id = $_SESSION['openai_assistant_'.$assistant_indicator]['id'];
}

//loaded thread
if(!isset($_SESSION['openai_thread_'.$thread_indicator]['id']) || !$_SESSION['openai_thread_'.$thread_indicator]['id']){
    $json['success'] = false;
    $json['error'] = "Thread not initialized before receiving message";
    $json['status'] = "Connection error #14";
    echo json_encode($json);
    die();
}else{
    $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
}

//check in progress or expecting tool output
if(isset($_SESSION['openai_thread_'.$thread_indicator]['run']) && isset($_SESSION['openai_thread_'.$thread_indicator]['run_status'])  && in_array($_SESSION['openai_thread_'.$thread_indicator]['run_status'], array('in_progress','requires_action'))){
    $run_id = $_SESSION['openai_thread_'.$thread_indicator]['run'];
    $json['success'] = false;
    $json['error'] = "There is a run in progress ({$run_id}) thread can't receive messages. Status {$_SESSION['openai_thread_'.$thread_indicator]['run_status']}";
    $json['status'] = "Ai is busy...";
    echo json_encode($json);
    die();
}else{
    //all good, no run in progress
}



//no errors, continue
if(!isset($json['error'])){


    //override
    if(isset($_SESSION['openai_active_assistant'])){
        $params['assistant'] = $_SESSION['openai_active_assistant'];
    }

    $assistant_settings = $assistants[$assistant_indicator];
    $assistant_priority_rules = $priority_rules[$assistant_indicator];

    
    $json['assistant'] = $assistant_indicator;
    $json['thread'] = $thread_indicator;



    if(trim($params['message'])){ //harcoded command
        if($params['message']!="/retry"){
            $addmsg = openai_thread_add_message($thread_indicator, 'user', $params['message']);
        
            if(isset($addmsg['error'])){
                $json['success'] = false;
                $json['error'] = "addmsg err: ".$addmsg['error'];
                $json['status'] = "Response error #10";
            }
        }else{
            //retry command
            $json['success'] = true;
            $json['output'] = "Retry command received";
            $json['status'] = "Retrying...";
        }

    }else{
        $json['success'] = false;
        $json['error'] = "Empty message";
        $json['status'] = "Empty message #16";
    }

    //if no errors, run the assistant on the created thread
    if(!isset($json['error'])){
        //message sent successfully, initiate run
        $run = openai_assistant_run($assistant_indicator, $thread_indicator, $assistant_priority_rules);
        if(isset($run['error'])){
            $json['success'] = false;
            $json['error'] = "Run error: ".$run['error'];//.print_r($assistant_indicator,true).print_r($assistant_settings,true);
            $json['status'] = "Assistant error #13";
        }else{
            $json['success'] = true;
            $json['output'] = $params['message'];
        }

    }
}


require_once($gc['path']['root'].'/core/aichat/ajax/custom_end.ai_assistant_message.php');


echo json_encode($json);

?>