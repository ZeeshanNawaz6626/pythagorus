<?php


//version 3.7Pythagoras-1.0.0
function openai_get_user_aicredits($uid){
    global $gc, $db;
    $uid = (int)$uid;
    $creds = $db->select_single_to_array("users","aicredits","where id = '{$uid}' ");
    if($creds){
        return $creds['aicredits'];
    }else{
        return false;
    }
}

//version 3.7Pythagoras-1.0.0
function openai_update_user_aicredits($uid,$credits_used){
    global $gc, $db;
    $uid = (int)$uid;
    $credits_used = (int) $credits_used;
    
    if($credits_used>0){
        $credits_used = (int) $credits_used;
        $creds = $db->select_single_to_array("users","aicredits","where id = '{$uid}' ");
        if($creds['aicredits']>0){
            $newcreds = max(0,($creds['aicredits']-$credits_used));
            $db->query("update users set aicredits = '{$newcreds}' where id = '{$uid}'  ");
        }else{
            $db->query("update users set aicredits = '0' where id = '{$uid}' ");
            return false;
        }
    }else{
        return true;
    }
}

//version 3.7Pythagoras-1.0.0
function openai_update_usage($credits_used){
    global $gc, $db;
    $credits_used = (int) $credits_used;
    $service = $db->query("update service_status set credits_remaining = credits_remaining - {$credits_used} where service_name = 'openai' ");
}


//----------------
// how assistants work: create assistant, create thread, add messages to thread, then run assistant on thread
//----------------



//openai_create_thread('oracle'); 
// settings = array(
//     'instructions'=>'You are a personal math tutor',
//     'name'=>'Jimmy Boss',
//     'model'=>'gpt-4-1106-preview',
//      'description' => 'Some info about jimmy',
//      'tools' => array(array("type"=>"code_interpreter")), //retrieval, function
// );
// retrieval option allows for context related responses, it will answer influenced by the messages array
// The Retrieval tool requires gpt-3.5-turbo-1106 and gpt-4-1106-preview models.
//"messages": [
//{"role": "system", "content": "You are a helpful assistant."},
//{"role": "user", "content": "Who won the world series in 2020?"}
//],
//"additional_context": {"messages": [
//{"role": "system", "content": "The Los Angeles Dodgers won the World Series in 2020."},
//{"role": "user", "content": "Where was it played?"}
//]}
//}


//https://platform.openai.com/docs/api-reference/assistants
//https://platform.openai.com/playground?assistant=new&mode=assistant

//warning
//it must look for available assistnats and retrieve one of the existing not create a new one each time.
//the asisstants, if they use retrieval tool, cost 0.2$/gb/assistant/day!


//version 3.7Pythagoras-1.1.0
//creates or gets an assistant model by indicator. Stores it in session, returns assistant id
//metadata  will be automatically  loaded with assistant_indicator so it will be retrievable by that name
//https://platform.openai.com/docs/api-reference/assistants/createAssistant
function openai_init_assistant($assistant_indicator, $settings, $forced=false){
    
    $ret = array();

    $settings['metadata']['indicator'] = $assistant_indicator;

    if($forced || !isset($_SESSION['openai_assistant_'.$assistant_indicator]['id'])){

        $found = false; //init as not found
        $assistants = openai_list_assistants();
        if(isset($assistants['assistants_list']) && count($assistants['assistants_list'])>0){
            foreach($assistants['assistants_list'] as $assist){
                if(isset($assist['metadata']['indicator']) && $assist['metadata']['indicator'] == $assistant_indicator){

                    $_SESSION['openai_assistant_'.$assistant_indicator] = array();
                    $_SESSION['openai_assistant_'.$assistant_indicator]['id'] = $assist['id'];
                    $_SESSION['openai_assistant_'.$assistant_indicator]['name'] = $assist['name'];

                    $ret['success'] = true;
                    $ret['assistant_id'] = $assist['id'];    
                                
                    $found = true;
                }
            }
        }
        
        if(!$found){

            

            //create a new one
            $uri = 'https://api.openai.com/v1/assistants';
            $result = openai_api_post_request($uri, $settings, true);


            if(isset($result['error']) && $result['error']) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = "Init curl err:".strtoupper($result['error']).': '.$result['data'];

            }else{
                //var_dump($result); die();
                if(isset($result['result']['id']) && trim($result['result']['id'])) {
                    //all good, assistant created
                    
                    $_SESSION['openai_assistant_'.$assistant_indicator] = array();
                    $_SESSION['openai_assistant_'.$assistant_indicator]['id'] = $result['result']['id'];
                    $_SESSION['openai_assistant_'.$assistant_indicator]['name'] = $settings['name'];
                    
                    $ret['success'] = true;
                    $ret['result'] = $result['result'];
                    $ret['assistant_id'] = $result['result']['id'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = "Init err:".$result['raw_response'];
                }
            }
        }
    }else{
        $ret['success'] = true;
        $ret['assistant_id'] = $_SESSION['openai_assistant_'.$assistant_indicator]['id'];
        $ret['assistant_name'] = $_SESSION['openai_assistant_'.$assistant_indicator]['name'];
    }

    return $ret;
}


//version 3.7Pythagoras-1.2.0
// v.1.2.0 - added $gc['openai_assistants'] in config.openai_asssitants, to delete just these assistants 
// v.1.1.0 - added $forced param to delete all assistants
//loads the assistants found in indicators array, that are also in assistants_config, creates assistant if needed, or retrieves it, and stores it in session
function openai_preload_assistants($indicators, $assistants_config, $forced=false){
    global $gc;
    $ret = '';

    if($forced){
        openai_delete_all_assistants($gc['openai_assistants']);
    }

    //update assistant reference
    if(!isset($_SESSION['openai_assistants_by_id'])){
        $_SESSION['openai_assistants_by_id'] = array();
    }

    foreach($indicators as $indicator){
        if(isset($assistants_config[$indicator])){
            $settings = $assistants_config[$indicator];
            //$settings['metadata']['indicator'] = $indicator;
            $init = openai_init_assistant($indicator, $settings, $forced);
            if(isset($init['assistant_id'])){
                $ret .= "<br>Assistant {$indicator} initialized";
                $_SESSION['openai_assistants_by_id'][$init['assistant_id']] = $indicator;
            }else{
                $ret .= "<br>Assistant {$indicator} failed to initialize";
            }
        }else{
            $ret .= "<br>Assistant {$indicator} not found in config";
        }
    }

    return $ret;
}


//version 3.7Pythagoras-1.0.0
//get a list with all available assistants and their settings, so you can reuse or delete them
function openai_list_assistants($order='desc',$limit=20){
    
    $ret = array();

    $uri = 'https://api.openai.com/v1/assistants';
    $result = openai_api_get_request($uri, array('order'=>$order,'limit'=>$limit), true);


    if(isset($result['error']) && trim($result['error'])) {
        //curl call failed
        $ret['success'] = false;
        $ret['error'] = strtoupper($result['error']).': '.$result['data'];

    }else{
            
        if(isset($result['result']['data'])) {
            //all good, assistant created

            $ret['success'] = true;
            $ret['result'] = $result['result'];
            $ret['assistants_list'] = $result['result']['data'];
        }else{
            $ret['success'] = false;
            $ret['error'] = $result['raw_response'];
        }
    }


    return $ret;
}

//version 3.7Pythagoras-1.2.0
// v.1.2.0 -  added $indicators param to delete just these assistants (meta indicator)
//deletes max 100 assistants at a time, to clean up experiments
function openai_delete_all_assistants($indicators=array()){
    $result = openai_list_assistants('desc',100);
    $ret = "<br>Deleting (max 100) assistants:";
    if(isset($result['assistants_list'])){
        foreach($result['assistants_list'] as $assistant){

            if(!$indicators || (isset($assistant['metadata']['indicator']) && in_array($assistant['metadata']['indicator'],$indicators))){
                $del = openai_delete_assistant($assistant['id']);
                if(isset($del['assistant_id'])){
                    $ret .= "<br>Deleted assistant {$del['assistant_id']}";
                }
            }
        }
        $ret .= "<br>";
    }
    return $ret;
}


//version 3.7Pythagoras-1.0.0
//deletes indicated assistants by indicator
function openai_delete_project_assistants(){
    $ret = '';
    foreach($_SESSION as $key => $val){
        if(strpos($key,'openai_assistant_')!==false){
            $assid = $_SESSION[$key]['id'];
            
            $del = openai_delete_assistant($assid);
            if(isset($del['assistant_id'])){
                echo "<br>Deleted project assistant {$del['assistant_id']}";
            }

            unset($_SESSION[$key]);
        }
    }
    echo $ret;
}


//version 3.7Pythagoras-1.0.0
//deletes an assistant by indicator. Removes it from session too
function openai_delete_assistant($assistant_id){
    
    $ret = array();

        $uri = 'https://api.openai.com/v1/assistants/'.$assistant_id;
        $result = openai_api_delete_request($uri, array(), true);

        if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];
        }else{
            if(isset($result['result']['error']) && $result['result']['error']) {
                //curl ok but api returned error
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }else{
                //all good
                $ret['success'] = true;
                $ret['assistant_id'] = $result['result']['id'];
            }
        }        

    return $ret;         
}


//version 3.7Pythagoras-1.1.0
//v.1.1.0 - add found_in and thread_indicator to return array
//creates or gets an assistant thread by indicator. Stores it in session, returns thread id
//openai_create_thread('discussion_1'); 
//https://platform.openai.com/docs/api-reference/threads/getThread
function openai_init_thread($thread_indicator){
    
    $ret = array();

    if(!isset($_SESSION['openai_thread_'.$thread_indicator]['id'])){
        $uri = 'https://api.openai.com/v1/threads';
        $result = openai_api_post_request($uri, array(), true);

        

        if(isset($result['error']) && $result['error']) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = "Init thread curl err: ".strtoupper($result['error']).': '.$result['data'];

        }else{
            
            if(isset($result['result']['id']) && trim($result['result']['id'])) {
                //all good, thread created
                
                $_SESSION['openai_thread_'.$thread_indicator] = array();
                $_SESSION['openai_thread_'.$thread_indicator]['id'] = $result['result']['id'];
                
                $ret['set_cookies'] = openai_update_threads_cookie();

                $ret['success'] = true;
                $ret['result'] = $result['result'];
                $ret['thread_id'] = $result['result']['id'];
                $ret['thread_indicator'] = $thread_indicator;
                $ret['found_in'] = 'created';
            }else{
                $ret['success'] = false;
                $ret['error'] = "Init thread err:".$result['raw_response'];
            }
        }
    }else{
        //already exists in session, retrieve id
        $ret['success'] = true;
        $ret['found_in'] = 'session';
        $ret['thread_indicator'] = $thread_indicator;
        $ret['thread_id'] = $_SESSION['openai_thread_'.$thread_indicator]['id'];
    }

    return $ret;
}


//version 3.7Pythagoras-1.0.0
//only metadata can be modified. 16 pairs of key-value, 64 => 512 chars each
//https://platform.openai.com/docs/api-reference/threads/modifyThread
function openai_modify_thread($thread_indicator,$meta){
    
    $ret = array();
    
    $thread_init = openai_init_thread($thread_indicator);

    if($thread_init['success']){
        $thread_id = $thread_init['thread_id'];
        $uri = 'https://api.openai.com/v1/threads/'.$thread_id;

        $data = array();
        $data['metadata'] = $meta;

        $result = openai_api_post_request($uri, $data, true);

        $ret['response'] =  $result['raw_response'];

        if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];
            $ret['usage'] = 0;
        }else{
            if(isset($result['result']['error']) && $result['result']['error']) {
                //curl ok but api returned error
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }else{
                //all good
                $ret['success'] = true;
                $ret['meta'] = $result['result']['metadata'];
            }
        }        
    }else{
        $ret['success'] = false;
        $ret['error'] = $thread_init['error'];
    }

    return $ret;         
}


/*
//version 3.7Pythagoras-1.0.0
//undocumented yes in their api, should work if they allow it
function openai_list_threads(){
    $uri = 'https://api.openai.com/v1/threads/';
    $result = openai_api_get_request($uri, array(), true);

    $ret['response'] =  $result['raw_response'];

    if(isset($result['error']) && trim($result['error'])) {
        //curl call failed
        $ret['success'] = false;
        $ret['error'] = "curl get err:".strtoupper($result['error']).': '.$result['data'];
    }else{
        if(isset($result['result']['error']) && $result['result']['error']) {
            //curl ok but api returned error
            $ret['success'] = false;
            $ret['error'] = $result['result']['error'];
        }else{
            //all good

            $ret['success'] = true;
            $ret['result'] = $result['result'];
        }
    } 

    return $ret;
}
*/

//version 3.7Pythagoras-1.0.0
function openai_get_thread($thread_id){
    $uri = 'https://api.openai.com/v1/threads/'.$thread_id;
    $result = openai_api_get_request($uri, array(), true);

    $ret['response'] =  $result['raw_response'];

    if(isset($result['error']) && trim($result['error'])) {
        //curl call failed
        $ret['success'] = false;
        $ret['error'] = "curl get err:".strtoupper($result['error']).': '.$result['data'];
    }else{
        if(isset($result['result']['error']) && $result['result']['error']) {
            //curl ok but api returned error
            $ret['success'] = false;
            $ret['error'] = $result['result']['error']['message'];
        }else{
            //all good

            $ret['success'] = true;
            $ret['result'] = $result['result'];
        }
    } 

    return $ret;
}

//version 3.7Pythagoras-1.0.0
//updates the cookie with the current threads stored in session
//must run before any content, after session_start() so i put it in header
function openai_update_threads_cookie(){
    global $gc;
    $ret = array();
    $openai_threads = array();
    foreach($_SESSION as $key => $val){
        if(strpos($key,'openai_thread_')!==false){
            //echo "found thread {$key}"; //do not break cookie
            $thread_indicator = str_replace('openai_thread_','',$key);
            $openai_threads[$thread_indicator] = array();
            $openai_threads[$thread_indicator]['id'] = $val['id'];
            if(isset($val['name'])){
                $openai_threads[$thread_indicator]['name'] = $val['name'];
            }
        }
    }

    if($openai_threads){
        $ret['success'] = true;
        $ret['set_cookie'] = setcookie($gc['cookie_name'].'_openai_threads', json_encode($openai_threads), time() + (86400 * 30), "/"); // 86400 = 1 day
        if(!$ret['set_cookie']){
            $ret['success'] = false;
            $ret['error'] = "Unable to set cookie";
        }
        $ret['threads'] = $openai_threads;
    }else{
        $ret['success'] = false;
        $ret['error'] = "No threads found in session";
    }
    //setLazyCookie($gc['cookie_name'].'_openai_threads',json_encode($openai_threads),(time() + (86400 * 30)),"/");
    //echo "<pre>"; var_dump($openai_threads); echo "</pre>";
    return $ret;
}

//version 3.7Pythagoras-1.1.0
//v.1.1.0 - check if thread exists before deleting
//deletes a thread by indicator. Removes it from session
function openai_load_threads_from_cookie(){
    global $gc;
    $ret = array();
    $openai_threads = (isset($_COOKIE[$gc['cookie_name'].'_openai_threads'])) ? json_decode($_COOKIE[$gc['cookie_name'].'_openai_threads'],true) : false;
    if($openai_threads){
        $found = false;
        $threads = array();
        foreach($openai_threads as $thread_indicator => $thread_data){
            if(isset($thread_data['id'])){
                $threads[$thread_indicator] = $thread_data['id'];
                $_SESSION['openai_thread_'.$thread_indicator] = array();
                $_SESSION['openai_thread_'.$thread_indicator]['id'] = $thread_data['id'];
                
                if(isset($thread_data['name'])){
                    $_SESSION['openai_thread_'.$thread_indicator]['name'] = $thread_data['name'];
                }
                $found = true;
            }
        }
        if($found){
            $ret['success'] = true;
            $ret['threads'] = $threads;
        }else{
            $ret['success'] = false;
            $ret['error'] = "No threads found in cookie";
            $ret['cookie'] = $openai_threads;
        }
    }else{
        //echo "no cookie found"; //do not break output!
        $ret['success'] = false;
        $ret['error'] = "No cookie found";
    }

    return $ret;
}



//version 3.7Pythagoras-1.1.0
//v.1.1.0 - check if thread exists before deleting
//deletes a thread by indicator. Removes it from session
function openai_delete_thread($thread_indicator){
    
    $ret = array();
    
    if(isset($_SESSION['openai_thread_'.$thread_indicator]) && isset($_SESSION['openai_thread_'.$thread_indicator]['id']) && $_SESSION['openai_thread_'.$thread_indicator]['id']){
        
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
            
        $exists = openai_get_thread($thread_id); //check if thread exists in openai

        if($exists['success']){
            $uri = 'https://api.openai.com/v1/threads/'.$thread_id;
            $result = openai_api_delete_request($uri, array(), true);

            $ret['response'] =  $result['raw_response'];

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];
            }else{
                if(isset($result['result']['error']) && $result['result']['error']) {
                    //curl ok but api returned error
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }else{
                    //all good
                    if(isset($_SESSION['openai_thread_'.$thread_indicator])){
                        unset($_SESSION['openai_thread_'.$thread_indicator]);
                    }
                    $ret['success'] = true;
                }
            }  
        }else{
            $ret['success'] = false;
            $ret['error'] = "Unable to find thread {$thread_id} ({$thread_indicator}) to delete";
        }      
    }else{
        $ret['success'] = false;
        $ret['error'] = "Unable to identify thread {$thread_indicator} to delete";
    }

    return $ret;         
}


//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/assistants/overview
function openai_thread_add_message($thread_indicator, $role, $content){
    
    $ret = array();

    if(isset($_SESSION['openai_thread_'.$thread_indicator]) && isset($_SESSION['openai_thread_'.$thread_indicator]['id']) && $_SESSION['openai_thread_'.$thread_indicator]['id']){
        
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
    
        $data = array();
        $data['role'] = $role;
        $data['content'] = $content;

        $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/messages';
        $result = openai_api_post_request($uri, $data, true);

        //file_ids
        //metadata

        if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];

        }else{
            
            if(isset($result['result']['id']) && trim($result['result']['id'])) {
                //all good, message created

                $ret['success'] = true;
                $ret['result'] = $result['result'];
                $ret['message_id'] = $result['result']['id'];
            }else{
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }
        }
    
    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} is not initialized";
    }

    return $ret;

}


//version 3.7Pythagoras-1.0.0
//retrieve just the latest message, for when tool run and system message is sent directly to the thread.
//could create conflict and retrieve last message before system one is added
//$dont_wait set means it will not check ongoing runs and return their status as error if nto complete
function openai_has_ongoing_run($thread_indicator){
    
    $ret = array();
    $ret['ongoing'] = true;

    if(isset($_SESSION['openai_thread_'.$thread_indicator]) && isset($_SESSION['openai_thread_'.$thread_indicator]['id']) && $_SESSION['openai_thread_'.$thread_indicator]['id']){
        
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
        //double check if there is something pending or not
        $runlist = openai_list_runs($thread_indicator);
        if(!isset($runlist['result']['data'][0]) || (isset($runlist['result']['data'][0]['status']) && $runlist['result']['data'][0]['status'] == 'completed')){
            $ret['success'] = true;
            $ret['ongoing'] = false;
        }else{
            $ret['success'] = false;
            $ret['error'] = "Run status is ".$runlist['result']['data'][0]['status'];  
        }
    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} is not initialized";
    }

    return $ret;

}



//version 3.7Pythagoras-1.0.0
//gets message_list with all messages in a thread
function openai_thread_get_messages($thread_indicator){
    
    $ret = array();

    if(isset($_SESSION['openai_thread_'.$thread_indicator]) && isset($_SESSION['openai_thread_'.$thread_indicator]['id']) && $_SESSION['openai_thread_'.$thread_indicator]['id']){
        
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
    
        $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/messages';
        $result = openai_api_get_request($uri, array(), true);

         if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];

        }else{
            
            if(isset($result['result']['data']) && is_array($result['result']['data'])) {
                //all good, message created

                $ret['success'] = true;
                $ret['result'] = $result['result'];
                $ret['message_list'] = $result['result']['data'];
            }else{
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }
        }
    
    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} is not initialized";
    }

    return $ret;

}

//version 3.7Pythagoras-1.0.0
//executes a run , assigning an assistant to a thread
//extra instructions allow for more control over the run, menus, assistant name, etc
//https://platform.openai.com/docs/assistants/overview
function openai_assistant_run($assistant_indicator, $thread_indicator, $extra_instructions){
    
    $ret = array();
 
    if(!isset($_SESSION['openai_thread_'.$thread_indicator]['id']) || !$_SESSION['openai_thread_'.$thread_indicator]['id']){
        $ret['error'] = "Thread {$thread_indicator} not initialized";
    }
    if(!isset($_SESSION['openai_assistant_'.$assistant_indicator]['id']) || !$_SESSION['openai_assistant_'.$assistant_indicator]['id']){
        $ret['error'] = "Assistant {$assistant_indicator} not initialized";
    }


    if(!isset($ret['error'])){

        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
        $assistant_id = $_SESSION['openai_assistant_'.$assistant_indicator]['id'];
        
        $data = array();
        $data['assistant_id'] = $assistant_id;
        $data['instructions'] = $extra_instructions;

        $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/runs';
        $result = openai_api_post_request($uri, $data, true);

        //file_ids
        //metadata

        if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];

        }else{
            
            if(isset($result['result']['id']) && trim($result['result']['id'])) {
                //all good, run created
                
                $_SESSION['openai_thread_'.$thread_indicator]['run'] = $result['result']['id'];
                
                $ret['success'] = true;
                $ret['result'] = $result['result'];
                $ret['run_id'] = $result['result']['id'];
            }else{
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }
        }
    }else{
        $ret['success'] = false;
    }

    return $ret;
    /*
    //openai_assistant_run returns a run object
    {
        "id": "run_abc123",
        "object": "thread.run",
        "created_at": 1699063290,
        "assistant_id": "asst_abc123",
        "thread_id": "thread_abc123",
        "status": "queued",
        "started_at": 1699063290,
        "expires_at": null,
        "cancelled_at": null,
        "failed_at": null,
        "completed_at": 1699063291,
        "last_error": null,
        "model": "gpt-4",
        "instructions": null,
        "tools": [
        {
            "type": "code_interpreter"
        }
        ],
        "file_ids": [
        "file-abc123",
        "file-abc456"
        ],
        "metadata": {}
    }
    */
}


//version 3.7Pythagoras-1.0.0
//data is collected in the chat, and this is triggered by ai_assistant_check_run ajax, in case the assistant detected data was provided to run a function
function openai_run_submit_tools_input($thread_indicator, $tool_calls_data){
    
    $ret = array();

    if(isset($_SESSION['openai_thread_'.$thread_indicator]['id'])){
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
        
        if(isset($_SESSION['openai_thread_'.$thread_indicator]['run']) && $_SESSION['openai_thread_'.$thread_indicator]['run_status'] == 'requires_action'){
            
            $run_id = $_SESSION['openai_thread_'.$thread_indicator]['run'];
            $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/runs/'.$run_id.'/submit_tool_outputs';
            
            if(isset($tool_calls_data) && is_array($tool_calls_data)){
                
                //----- prepare the data nicer
                $collect_calls = array();
                foreach($tool_calls_data as $call){
                    if($call['type'] == 'function'){
                        //get just those functions that expect input
                        if($call['type'] == 'function' ) { //&& strpos($call['function']['name'], 'input_') === 0
                            $collect_calls[$call['id']] = array(
                                'call_id' => $call['id'],
                                'run_id' => $run_id,
                                'thread_id' => $thread_id,
                                //'assistant_id' => ?,
                                'function' => $call['function']['name'],
                                'arguments' => $call['function']['arguments']
                            );
                            
                        }
                    }
                }
                //-----

                $data_calls = array();  
                foreach($collect_calls as $call_id => $call_data){
                    $dt = array();
                    $dt['tool_call_id'] = $call_id;
                    //@@todo: function to check $call_data['function'] and act accordingly (save details in session, check order etc);
                    
                    $dt['output'] = openai_run_assistant_function($call_data['function'], json_decode($call_data['arguments'],true)); //debug
                    $data_calls[] = $dt;
                }

                $data = array("tool_outputs"=>$data_calls);

                $result = openai_api_post_request($uri, $data, true);

                if(isset($result['error']) && trim($result['error'])) {
                    //curl call failed
                    $ret['success'] = false;
                    $ret['error'] = strtoupper($result['error']).': '.$result['data'];
                }else{
                    if(isset($result['result']['error'])){
                        $ret['success'] = false;
                        $ret['error'] = $result['result']['error']['message'];
                    }else{

                        //$_SESSION['openai_thread_'.$thread_indicator]['run'] = '';
                        $_SESSION['openai_thread_'.$thread_indicator]['run_status'] == 'completed'; //was requires_action

                        //OK
                        $ret['success'] = true;
                        $ret['submit_result'] = $result;
                        $ret['output'] = $data_calls;

                        /*
                        //@@todo analyyze if error response
                        if(isset($result['result']['status']) && trim($result['result']['status']) == 'completed') {
                            //all good, run completed
                            
                            $ret['success'] = true;
                            $ret['result'] = $result['result'];
                            $ret['run_status'] = $result['result']['status'];

                        }else{

                            $ret['success'] = false;
                            $ret['result'] = $result['result'];
                            $ret['error'] = $result['raw_response'];
                        }
                        */
                    }
                }
            }else{
                $ret['success'] = false;
                $ret['error'] = "Thread {$thread_indicator} does not have any expecting calls";
            }
        }else{
            $ret['success'] = false;
            $ret['error'] = "Thread {$thread_indicator} is not expecting any input";
        }
    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} is not loaded";
    }

    return $ret;
}


//version 3.7Pythagoras-1.0.0
//list all runs ongoing on this thread
function openai_list_runs($thread_indicator){

    if(isset($_SESSION['openai_thread_'.$thread_indicator]['id'])){
        $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];

        $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/runs';
        $result = openai_api_get_request($uri, array(), true);

        if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];

        }else{
            

            if(isset($result['result']['error'])){
                
            
                $ret['success'] = false;
                $ret['error'] = $result['result']['error']['message'];
                
            }else{

                if(isset($result['result']['data']) && count($result['result']['data'])>0) {
                    //all good, run completed
                    
                    $ret['success'] = true;
                    $ret['result'] = $result['result'];
                    $ret['run_list'] = $result['result']['data'];

                }else{

                    $ret['success'] = false;
                    $ret['result'] = $result['result'];
                    $ret['error'] = $result['raw_response'];
                }
            }
        }

    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} is not loaded";
    }

    return $ret;
}


//version 3.7Pythagoras-1.1.0
//v.1.1.0 - add requires_action status check
//gets the run status if the thread is run exists but its not 'completed'. Run periodically in ajax. When its completed, it means message is ready to be shown
function openai_run_check($thread_indicator){

    $ret = array();

    if(isset($_SESSION['openai_thread_'.$thread_indicator]['run']) && $_SESSION['openai_thread_'.$thread_indicator]['run']){
        
        if(isset($_SESSION['openai_thread_'.$thread_indicator]['run_status']) && $_SESSION['openai_thread_'.$thread_indicator]['run_status'] == 'completed'){

            //if this sess is set, it means the run was already checked and actions performed
            $ret['success'] = true;
            $ret['status'] = 'completed';

        }elseif(isset($_SESSION['openai_thread_'.$thread_indicator]['run_status']) && $_SESSION['openai_thread_'.$thread_indicator]['run_status'] == 'requires_action'){
            
            //if this sess is set, it means the run was already checked and actions performed
            $ret['success'] = true;
            $ret['status'] = 'requires_action';
            $ret['pending_input_processing'] = false; //as it was already preapred and output when run session was set

        }else{

            $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
            $run_id = $_SESSION['openai_thread_'.$thread_indicator]['run'];

            $uri = 'https://api.openai.com/v1/threads/'.$thread_id.'/runs/'.$run_id;
            $result = openai_api_get_request($uri, array(), true);

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['error'])){
                    $ret['success'] = false;
                    $ret['error'] = $result['result']['error']['message'];
                }else{
                    if(isset($result['result']['status']) && trim($result['result']['status']) == 'completed') {
                        //all good, run completed
                        
                        $ret['success'] = true;
                        $ret['result'] = $result['result'];
                        $ret['status'] = "completed"; //its completed

                        $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'completed';
                        //var_dump("AAAAA",$result); die();
                    }elseif(isset($result['result']['status']) && trim($result['result']['status']) == 'in_progress') {
                        
                        $ret['success'] = true;
                        $ret['result'] = $result['result'];
                        $ret['status'] = 'in_progress';

                    }elseif(isset($result['result']['status']) && trim($result['result']['status']) == 'requires_action') {
                       //get the received data and submit it to the assigned functions
                       if(isset($result['result']['required_action']['submit_tool_outputs']['tool_calls'])){                        

                            //this will be set to completed in openai_run_submit_tools_input()
                            $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'requires_action';
                            //$_SESSION['openai_thread_'.$thread_indicator]['run_calls'] = $tool_calls_data; //expected tool calls inputs

                                
                            //if current run returned requires_action, run assigned functions and send the message to the tools
                            //the run itself assigns data to the tools, and expects the tool output if any
                            $tool_calls_data = $result['result']['required_action']['submit_tool_outputs']['tool_calls'];
                            $toolmsg = openai_run_submit_tools_input($thread_indicator, $tool_calls_data);
                            
                            $ret['tool_result'] = $toolmsg;

                            if(!isset($toolmsg['error'])){
                                //OK
                                $ret['success'] = true;
                                $ret['result'] = $result['result'];
                                $ret['status'] = 'requires_action_done';
                                $ret['pending_input_processing'] = true; //tell the check_run ajax to trigger prepare input

                                //overwrite message with tool output
                                //$ret['output'] = print_r($toolmsg['output'],true);//

                                if(isset($toolmsg['output']) && $toolmsg['output']){
                                    $output_html_arr=array();
                                    foreach($toolmsg['output'] as $tool_output){
                                        $output_html_arr[] = $tool_output['output'];
                                    }
                                    $output_html = implode("<br>",$output_html_arr);
                                    $ret['output'] = $output_html;
                                }else{
                                   // $ret['output'] = "debug: no output from tools";
                                }

                                
                                //unset($_SESSION['openai_thread_'.$thread_indicator]['run']);
                                $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'completed_awaiting_response';

                            }else{
                                
                                $ret['success'] = false;
                                $ret['error'] = "toolmsg err: ".$toolmsg['error'];
                                $ret['status'] = "Response error #11";
                            }



                        }else{
                            $ret['success'] = false;
                            $ret['error'] = "Found no function calls in run requiring action";
                            $ret['status'] = "Response error #17";                           
                        }


                    }else{

                        $ret['success'] = false;
                        $ret['result'] = $result['result'];
                        $ret['status'] = 'undefined_status'; //
                        $ret['error'] = $result['raw_response'];
                    }
                }
            }
        }
    }else{
        $ret['success'] = false;
        $ret['error'] = "Thread {$thread_indicator} does not have an active run";
    }

    return $ret;
}




//version 3.7Pythagoras-1.2.0
//v.1.2.0 - models are now loaded from $gc['openai_models']
//v.1.1.0 - more clear output structure, result became output, and checks for curl error separately
function openai_command_gpt4($task,$content, $max_tokens=3000, $temperature=0.7, $gptmax=false){
    global $gc;
    $data = array();

    //--- the order is important, load more capable models down the list as required
    $data["model"] = $gc['openai_models']['turbo']; //default

    if(strlen($task)>200){ 
        $data["model"] = $gc['openai_models']['performance']; //complex orders
    }

    if($gptmax){
        $data["model"] = $gc['openai_models']['performance']; //forcefully set to gpt-4 unless content is to big (below)
    }  

    if(strlen($content)>4000){
        $data["model"] = $gc['openai_models']['turbo-16k']; //prefer over gpt-4 because content is larger
    } 
 
    //apparently its in the docs but does not exist yet?
    //if(strlen($content)>16000){
    //    $data["model"] = 'gpt-4-32k'; //prefered over anything else because content is the most larger
    //}

    //----

    $data["messages"] = array(
        array('role'=>'system','content'=>$task),
        array('role'=>'user','content'=>$content)
    );
    $data["temperature"] = $temperature;
    $data["max_tokens"] = $max_tokens;

    $uri = 'https://api.openai.com/v1/chat/completions';
    //$uri = 'https://api.openai.com/v1/completions';


    $result = openai_api_post_request($uri, $data);

    $ret = array();
    $ret['response'] =  $result['raw_response'];

    if(isset($result['error']) && trim($result['error'])) {
        //curl call failed
        $ret['success'] = false;
        $ret['error'] = strtoupper($result['error']).': '.$result['data'];
        $ret['usage'] = 0;
    }else{
        if(isset($result['result']['error']) && $result['result']['error']) {
            //curl ok but api returned error
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['result']['error']['type']).': '.$result['result']['error']['message']; //$result['raw_response']
            $ret['usage'] = $result['result']['usage']['total_tokens'];
        }else{
            //all good
            $ret['success'] = true;
            $ret['output'] = $result['result']['choices'][0]['message']['content']; //$data["model"].'@'.
            $ret['usage'] = $result['result']['usage']['total_tokens'];
        }
    }

return $ret; 
}


//version 3.7Pythagoras-1.1.0
// v.1.1.0 - more clear output structure, result became output, and checks for curl error separately
//obsolete, for older models
function openai_command_davinci($prompt, $max_tokens=3000, $temperature=0.7){
    global $gc, $db;

    $service_status = get_service_status('openai');

    if($service_status['functional']){

        $data = array();
        $data["model"] = 'text-davinci-003'; //$gc['api']['openai_default_brain']; //text-davinci-003
        $data["prompt"] = $prompt;
        $data["temperature"] = $temperature;
        $data["max_tokens"] = $max_tokens;

        $uri = 'https://api.openai.com/v1/completions';

        $result = openai_api_post_request($uri, $data);

        $ret = array();
        $ret['debug'] =  $result['raw_response'];//['raw_result'];

        if(isset($result['error']) && trim($result['error'])) {
            //check if curl connection error
            $ret['output'] = strtoupper($result['error']['type']).': '.$result['error']['message'];
            $ret['usage'] = 0;
        }else{
            if(isset($result['result']['error']) && $result['result']['error']) {
                //check if openai returned error
                $ret['output'] = $result['raw_response'];
                $ret['usage'] = 0;
            }else{
                //all good
                $ret['output'] = $result['result']['choices'][0]['text'];
                $ret['usage'] = $result['result']['usage']['total_tokens'];
                openai_update_usage($ret['usage']);
            }
        }
    }else{
        $ret['output'] = 'USAGE LIMIT REACHED: Usage limit for this month has been reached. We appologise for the inconvenience. ';
        $ret['usage'] = 0;
    }
    
    return $ret; 
}


//version 3.7Pythagoras-1.1.0
function openai_api_get_request($uri,$data=array(),$assistant=false){
    global $gc, $db;

    $api_key = $gc['api']['openai_api_key'];

    $ret = array();
    if (!$api_key) {
        $ret['success'] = false;
        $ret['error'] = "Missing openai api key";
        return $ret;
    }

    $data_query = http_build_query($data);
    $uri  = $uri . '?' . $data_query;

    //-H 'OpenAI-Organization: org-****************'
    $headers = array();
    $headers = array('Authorization: Bearer ' . $api_key, 'Content-Type: application/json');
    if ($assistant) {
        $headers[] = 'OpenAI-Beta: assistants=v1';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $raw_response = curl_exec($ch);
    $result = json_decode($raw_response, true);

    $ret['raw_response'] = $raw_response;

    if (curl_errno($ch)) {
        $ret['success'] = false;
        $ret['error'] = "Curl error: " . curl_error($ch);
        $ret['data'] = curl_error($ch);
        return $ret;
    }

    curl_close($ch);

    if (!$result) {
        $ret['success'] = false;
        $ret['error'] = $raw_response;
        return $ret;
    } else {
        $ret['success'] = true;
        $ret['result'] = $result;
        return $ret;
    }

}

//version 3.7Pythagoras-1.0.0
//sends a DELETE request to openai. nearly identical to post_request
function openai_api_delete_request($uri,$data=array(),$assistant=false){
    global $gc, $db;

    $api_key = $gc['api']['openai_api_key'];
    
    $ret = array();
    if(!$api_key){
        $ret['success'] = false;
        $ret['error'] = "Missing openai api key";
        return $ret;
    }

    $data = json_encode($data);
    //-H 'OpenAI-Organization: org-****************'
    $headers = array();
    $headers = array('Authorization: Bearer '.$api_key, 'Content-Type: application/json');
    if($assistant){
        $headers[] = 'OpenAI-Beta: assistants=v1';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $raw_response = curl_exec($ch);
    $result = json_decode($raw_response,true);

    $ret['raw_response'] = $raw_response;

    if (curl_errno($ch)){
        $ret['success'] = false;
        $ret['error'] = "Curl error: ".curl_error($ch);
        $ret['data'] = curl_error($ch);
        return $ret;
    }

    curl_close($ch);
    
    if(!$result){
        $ret['success'] = false;
        $ret['error'] = $raw_response;
        return $ret;
    }else{
        $ret['success'] = true;
        $ret['result'] = $result;
        return $ret;
    }

}



//version 3.7Pythagoras-1.1.0
//v.1.1.0 - api response in $ret['result'], returned ret[error] means curl error
//v.1.1.1 - added assistant support
function openai_api_post_request($uri,$data=array(),$assistant=false){
    global $gc, $db;

    $api_key = $gc['api']['openai_api_key'];
    
    $ret = array();
    if(!$api_key){
        $ret['success'] = false;
        $ret['error'] = "Missing openai api key";
        return $ret;
    }

    $data = json_encode($data);
    //-H 'OpenAI-Organization: org-****************'
    $headers = array();
    $headers = array('Authorization: Bearer '.$api_key, 'Content-Type: application/json');
    if($assistant){
        $headers[] = 'OpenAI-Beta: assistants=v1';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $raw_response = curl_exec($ch);
    $result = json_decode($raw_response,true);

    $ret['raw_response'] = $raw_response;

    if (curl_errno($ch)){
        $ret['success'] = false;
        $ret['error'] = "Curl error: ".curl_error($ch);
        $ret['data'] = curl_error($ch);
        return $ret;
    }

    curl_close($ch);
    
    if(!$result){
        $ret['success'] = false;
        $ret['error'] = $raw_response;
        return $ret;
    }else{
        $ret['success'] = true;
        $ret['result'] = $result;
        return $ret;
    }


}


//version 3.7Pythagoras-1.0.0
//dowload audio file from openai api
function openai_curl_save_file($uri, $data=array(),$file_path){
    global $gc, $db;

    $api_key = $gc['api']['openai_api_key'];
    
    $ret = array();
    if(!$api_key){
        $ret['success'] = false;
        $ret['error'] = "Missing openai api key";
        return $ret;
    }

    $fp = fopen ($file_path, 'w+') or die("Can't create file");

    //-H 'OpenAI-Organization: org-****************'
    $headers = array();
    $headers = array(
        'Authorization: Bearer '.$api_key, 
        'Content-Type: application/json'
    );

    $data=json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $data = curl_exec($ch);

    if (curl_errno($ch)){
        $ret['success'] = false;
        $ret['error'] = "Curl error: ".curl_error($ch);
        $ret['data'] = curl_error($ch);
        return $ret;
    }

    curl_close($ch);

    if(!$data){
        $ret['success'] = false;
        $ret['error'] = "no data";
        $ret['data'] = $data;
        return $ret;
    }else{
        $ret['success'] = true;
        $ret['file_path'] = $file_path;
        return $ret;
    }


}

//=========== OPENAI FILES ===============



//version 3.7Pythagoras-1.1.0
//v.1.1.0 - api response in $ret['result'], returned ret[error] means curl error
//v.1.1.1 - added assistant support
function openai_api_postfile_request($uri, $file_path ,$data=array(),$assistant=false){
    global $gc, $db;

    $api_key = $gc['api']['openai_api_key'];
    
    $ret = array();
    if(!$api_key){
        $ret['success'] = false;
        $ret['error'] = "Missing openai api key";
        return $ret;
    }

    $cFile = curl_file_create($file_path);
    $data['file'] = $cFile;

    //$data = json_encode($data);
    //var_dump($data); die();
    //-H 'OpenAI-Organization: org-****************'
    $headers = array();
    $headers = array(
        'Authorization: Bearer '.$api_key, 
        //'Content-Type: application/json',
        'Content-Type: multipart/form-data'
    );
    if($assistant){
        $headers[] = 'OpenAI-Beta: assistants=v1';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $raw_response = curl_exec($ch);
    $result = json_decode($raw_response,true);

    $ret['raw_response'] = $raw_response;

    if (curl_errno($ch)){
        $ret['success'] = false;
        $ret['error'] = "Curl error: ".curl_error($ch);
        $ret['data'] = curl_error($ch);
        return $ret;
    }

    curl_close($ch);
    
    if(!$result){
        $ret['success'] = false;
        $ret['error'] = $raw_response;
        return $ret;
    }else{
        $ret['success'] = true;
        $ret['result'] = $result;
        return $ret;
    }


}

//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/files/create?lang=curl
//maximum of 512 MB or 2 million tokens
function openai_upload_file_byurl($file_path, $file_name, $purpose){
    global $gc;
    $ret = array();

    if(filter_var($file_path, FILTER_VALIDATE_URL)){

        $storage_path = $gc['path']['root']."/storage/aifiles/" . $file_name; // Define the storage path
        // Download the file from the URL and save it to the storage path
        if(file_put_contents($storage_path, file_get_contents($file_path))){
            $ret['success'] = true;
            $ret['path'] = $storage_path;
            $ret['result_upload'] = openai_upload_file($storage_path, $purpose);
        } else {
            $ret['success'] = false;
            $ret['error'] = "Failed to download and save the file.";
        }
    }else{
        $ret['success'] = false;
        $ret['error'] = "Invalid URL";
    }

        return $ret;
}


//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/files/create?lang=curl
//
//maximum of 512 MB or 2 million tokens
function openai_upload_file($file_path, $purpose){
    
    $ret = array();

    if(file_exists($file_path)){

        if (filesize($file_path) < 512 * 1024 * 1024) {
            

            $data = array();
            $data['purpose'] = $purpose; //"fine-tune" or "assistants"
            //$data['file'] = file_get_contents($file_path);

            $uri = 'https://api.openai.com/v1/files';
            $result = openai_api_postfile_request($uri, $file_path, $data, false);

            //file_ids
            //metadata

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['id']) && trim($result['result']['id'])) {
                    //all good, file uploaded

                    $ret['success'] = true;
                    $ret['result_postfile'] = $result['result'];
                    $ret['file_id'] = $result['result']['id'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }
            }
        
        }else{
            $ret['success'] = false;
            $ret['error'] = "File size exceeds the maximum limit of 512MB";
        }
    }else{
        $ret['success'] = false;
        $ret['error'] = "File {$file_path} does not exist";
    }

    return $ret;

}

/*
{
  "id": "file-abc123",
  "object": "file",
  "bytes": 120000,
  "created_at": 1677610602,
  "filename": "mydata.jsonl",
  "purpose": "fine-tune",
}
*/

//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/assistants/createAssistantFile
//$cf = new CURLFile("FILE-TO-UPLOAD.EXT");
//maximum of 512 MB or 2 million tokens
function openai_attach_assistant_file($assistant_indicator, $file_id){
    
    $ret = array();

    $assistant_id = (isset($_SESSION['openai_assistant_'.$assistant_indicator]['id'])) ? $_SESSION['openai_assistant_'.$assistant_indicator]['id'] : false;

    if($assistant_id){// Check file size

            $data = array();
            $data['file_id'] = $file_id;

            $uri = 'https://api.openai.com/v1/assistants/'.$assistant_id.'/files';
            $result = openai_api_post_request($uri, $data, true);

            //file_ids
            //metadata

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['id']) && trim($result['result']['id'])) {
                    //all good, file assigned to assistnat

                    $ret['success'] = true;
                    $ret['result_post'] = $result['result'];
                    $ret['file_id'] = $result['result']['id'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }
            }
        

    }else{
        $ret['success'] = false;
        $ret['error'] = "Can not upload file to assistant {$assistant_indicator} because it was not initialized";
    }
    return $ret;

}
/*
{
  "id": "file-abc123",
  "object": "assistant.file",
  "created_at": 1699055364,
  "assistant_id": "asst_abc123"
}
*/


//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/assistants/deleteAssistantFile
function openai_detach_assistant_file($assistant_indicator, $file_id){
    
    $ret = array();

    $assistant_id = (isset($_SESSION['openai_assistant_'.$assistant_indicator]['id'])) ? $_SESSION['openai_assistant_'.$assistant_indicator]['id'] : false;

    if($assistant_id){// Check file size

            $data = array();

            $uri = 'https://api.openai.com/v1/assistants/'.$assistant_id.'/files/'.$file_id.'';
            $result = openai_api_delete_request($uri, $data, true);

            //file_ids
            //metadata

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['id']) && trim($result['result']['id'])) {
                    //all good, file detached from assistnat

                    $ret['success'] = true;
                    $ret['result_delete'] = $result['result'];
                    $ret['file_id'] = $result['result']['id'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }
            }
        

    }else{
        $ret['success'] = false;
        $ret['error'] = "Can not upload file to assistant {$assistant_indicator} because it was not initialized";
    }
    return $ret;

}

/*
{
  id: "file-abc123",
  object: "assistant.file.deleted",
  deleted: true
}
*/

//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/files/create?lang=curl
//
//maximum of 512 MB or 2 million tokens
function openai_get_uploaded_files(){
    
    $ret = array();



            $data = array();

            $uri = 'https://api.openai.com/v1/files';
            $result = openai_api_get_request($uri, $data);

            //file_ids
            //metadata

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['data']) && trim($result['result']['data'])) {
                    //all good, file uploaded

                    $ret['success'] = true;
                    $ret['result_get'] = $result['result'];
                    $ret['file_list'] = $result['result']['data'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }
            }
        

    return $ret;

}

/*
{
  "data": [
    {
      "id": "file-abc123",
      "object": "file",
      "bytes": 175,
      "created_at": 1613677385,
      "filename": "salesOverview.pdf",
      "purpose": "assistants",
    },
    {
      "id": "file-abc123",
      "object": "file",
      "bytes": 140,
      "created_at": 1613779121,
      "filename": "puppy.jsonl",
      "purpose": "fine-tune",
    }
  ],
  "object": "list"
}
*/

//version 3.7Pythagoras-1.0.0
function openai_check_uploaded_file_exists($filename){
    $ret = array();
    $ret['exists'] = false;
    $ret['file_id'] = false;

    $file_list = openai_get_uploaded_files();

    if($file_list['success']){
        foreach($file_list['file_list'] as $file){
            if($file['filename'] == $filename){
                $ret['exists'] = true;
                $ret['file_id'] = $file['id'];
                break;
            }
        }
    }else{
        $ret['error'] = $file_list['error'];
    }

    return $ret;
}



//version 3.7Pythagoras-1.0.0
//https://platform.openai.com/docs/api-reference/assistants/deleteAssistantFile
function openai_delete_file($file_id){
    
    $ret = array();


            $data = array();

            $uri = 'https://api.openai.com/v1/files/'.$file_id.'';
            $result = openai_api_delete_request($uri, $data, true);

            //file_ids
            //metadata

            if(isset($result['error']) && trim($result['error'])) {
                //curl call failed
                $ret['success'] = false;
                $ret['error'] = strtoupper($result['error']).': '.$result['data'];

            }else{
                
                if(isset($result['result']['id']) && trim($result['result']['id'])) {
                    //all good, file detached from assistnat

                    $ret['success'] = true;
                    $ret['result_delete'] = $result['result'];
                    $ret['deleted'] = $result['result']['deleted'];
                }else{
                    $ret['success'] = false;
                    $ret['error'] = $result['raw_response'];
                }
            }
        

    return $ret;

}
/*
{
  "id": "file-abc123",
  "object": "file",
  "deleted": true
}
*/

//version 3.7Pythagoras-1.0.0
function openai_delete_file_by_name($filename){
    $ret = array();
    $ret['success'] = false;

    $file_list = openai_get_uploaded_files();

    if($file_list['success']){
        foreach($file_list['file_list'] as $file){
            if($file['filename'] == $filename){
                $ret = openai_delete_file($file['id']);
                break;
            }
        }
    }else{
        $ret['error'] = $file_list['error'];
    }

    return $ret;
}

//version 3.7Pythagoras-1.1.0
//v.1.1.0 - filtered unwanted script and html from text input
//text to speach mp3 output
function openai_text_to_speach($text,$title,$voice='alloy',$hd=false){
    global $gc;

    $ret = array();

    $text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);

    $data = array();
    if($hd){
        $data['model'] = "tts-1-hd";
    }else{
        $data['model'] = "tts-1";
    }

    $data['input'] = $text;
    $data['voice'] = $voice;

    $file_url = $gc['path']['web_root'] . "/storage/audio/" . $title . ".aac";
    $file_path = $gc['path']['root'] . "/storage/audio/" . $title . ".aac"; // Define the storage path

    if (!is_dir(dirname($file_path))) {
        mkdir(dirname($file_path), 0777, true);
    }


        $uri = 'https://api.openai.com/v1/audio/speech';
        $result = openai_curl_save_file($uri, $data, $file_path);

         if(isset($result['error']) && trim($result['error'])) {
            //curl call failed
            $ret['success'] = false;
            $ret['error'] = strtoupper($result['error']).': '.$result['data'];

        }else{
            
            if(isset($result['success']) && $result['success']) {
                //all good, message created

                $ret['success'] = true;
                $ret['result'] = $result;
                $ret['file_path'] = $result['file_path'];
                $ret['file_url'] = $file_url;
            }else{
                $ret['success'] = false;
                $ret['error'] = $result['raw_response'];
            }
        }
    


    return $ret;

}



//version 3.7Pythagoras-1.1.0
//v.1.1.0 reset cookie as well, this func must run before content output
//will handle ?reset=1 and ?reset=2 requests to clear session and/or assistants
function openai_reset_handle(){
    //level 1 reset
    global $gc, $default_thread;

    if(isset($_GET['reset']) && $_GET['reset']==1){
        foreach($_SESSION as $key => $val){
            if(strpos($key,'openai_thread_')!==false || strpos($key,'openai_assistant_')!==false){
                $_SESSION[$key] = [];
                unset($_SESSION[$key]);
            }
        }
        setcookie($gc['cookie_name'].'_openai_threads', json_encode(array()), time() - 99999, "/"); //reset cookie
        openai_delete_thread($default_thread);

        echo "<br>reset openai session done";
        echo '<meta http-equiv="refresh" content="1;url=./">';
        die();
    }

    //level 2 reset
    if(isset($_GET['reset']) && $_GET['reset']==2){
        $_SESSION = [];
        setcookie($gc['cookie_name'].'_openai_threads', json_encode(array()), time() - 99999, "/"); //reset cookie
        openai_delete_all_assistants();

        echo "<br>reset assistants and session done";
        echo '<meta http-equiv="refresh" content="1;url=./">';
        die();
    }

}

?>