<?php

//THIS FILE MUST REMAIN IDENTICAL. Put any customizations in the custom_start and custom_end files

//version 3.7Pythagoras-1.2.0
//v.1.2.0 added start and end custom files
//v.1.1.0 awaiting functions result, assistant name check

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

if(!$params['assistant']){
    $json['success'] = false;
    $json['error'] = "Assistant not specified";
    $json['status'] = "Connection error #1";
}

if(!$params['thread']){
    $json['success'] = false;
    $json['error'] = "Thread not specified";
    $json['status'] = "Connection error #2";
}  

if(!isset($_SESSION['openai_thread_'.$params['thread']]['run'])){
    $json['success'] = false;
    $json['error'] = "No run active on thread {$params['thread']}";
    $json['status'] = "I was not ready, can you repeat that please?";
}

require_once($gc['path']['root'].'/core/aichat/ajax/custom_start.ai_assistant_check_run.php');

//$json['error'] = "<pre>".print_r($_SESSION, true);

if(!isset($json['error'])){

    //override
    if(isset($_SESSION['openai_active_assistant'])){
        $params['assistant'] = $_SESSION['openai_active_assistant'];
    }
    
    $thread_indicator = $params['thread'];
    $assistant_indicator = $params['assistant'];

    $thread_id = $_SESSION['openai_thread_'.$thread_indicator]['id'];
    $assistant_id = $_SESSION['openai_assistant_'.$assistant_indicator]['id'];
    $assistant_name = (isset($_SESSION['openai_assistant_'.$assistant_indicator]['name'])) ? $_SESSION['openai_assistant_'.$assistant_indicator]['name'] : ""; //new

    $json['assistant'] = $params['assistant'];
    $json['thread'] = $params['thread'];

    $result = openai_run_check($thread_indicator);

//var_dump($result); die();


    if(isset($result['error']) && $result['error']){
        $json['success'] = false;
        $json['error'] = $result['error'];
        $json['status'] = "Connection error #4";
    }else{
        if(isset($result['status'])){

            if($result['status'] == 'completed'){
              
                $messages = openai_thread_get_messages($thread_indicator);
                
                if(isset($messages['message_list'])){
                    $_SESSION['openai_thread_'.$thread_indicator]['count_messages'] = count($messages['message_list']);
                    $json['success'] = true;
                    $json['name'] = $assistant_name;
                    $json['output'] = "".$messages['message_list'][0]['content'][0]['text']['value'];
                    $json['count_messages'] = count($messages['message_list']);
                }else{
                    $json['success'] = false;
                    $json['error'] = 'Error, failed to get messages list'.print_r($messages,true);
                    $json['status'] = "API response error. #5"; 
                }
                
                //only when status is complete, clear the session to prevent rechecking
                $_SESSION['openai_thread_'.$thread_indicator]['run'] = null; //unset run, so its no longer running this ajax
                $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = null; //unset run, so its no longer running this ajax
        
            }elseif($result['status']=='completed_awaiting_response'){


              $ongoing_run = openai_has_ongoing_run($thread_indicator);
              if(!$ongoing_run['ongoing']) {
                  $json['not_ready_yey'] = false;
                  $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'completed'; //on next round it will run the completed code showing the last message
                  //message will be retrieved in the complete status code, not here
                  //$json['output'] = "FFFFFF".print_r($last_message['last_message']['content'][0]['text']['value'],true);
              }else{
                  $json['not_ready_yey'] = true;
                  //no need to show anything if not ready
                  //$json['output'] = "TTTTTTTT".$last_message['error'];
                  $json['status'] = "AI is writing..";
                  $_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'completed_awaiting_response'; //already set to this, but for clarity
              }

        
            }elseif($result['status']=='requires_action_done'){
                
                if(isset($result['pending_input_processing']) && $result['pending_input_processing']){
                    if(isset($result['result']['required_action']['type']) && $result['result']['required_action']['type'] == 'submit_tool_outputs'){

                        $json['success'] = true;
                        $json['name'] = $assistant_name;
                        $json['tool_output'] = true;

                        $json['status'] = "Processing input..";
                        //$json['raw_result'] = $result;

                        
                        if(isset($result['output'])){
                          //if tools give any output, show it
                            $json['output'] = $result['output'];
                        }else{
                            //$json['output'] = "5385325:no output from tools";
                        }

                        /*
                        $last_message = openai_get_last_message($thread_indicator);
                        if(!$last_message['error']) {
                            $json['not_ready_yey'] = false;
                            //message will be retrieved in the complete status code, not here
                            //$json['output'] = "FFFFFF".print_r($last_message['last_message']['content'][0]['text']['value'],true);
                        }else{
                            $json['not_ready_yey'] = true;
                            //no need to show anything if not ready
                            //$json['output'] = "TTTTTTTT".$last_message['error'];
                            $json['status'] = "AI is writing..";
                        }
                        */

                        
                        //assume first round ai is still busy, no need to check, better wait for the next round
                        $json['not_ready_yey'] = true;
                        $json['status'] = "AI is writing..";

                        //$runlist = openai_list_runs($thread_indicator);
                        //$json['output'] = "GGGGGGGGG<pre>".print_r(['result']['data'][0]['status'],true)."</pre>";

                        /*
                        //---or version 2-----

                        $messages = openai_thread_get_messages($thread_indicator);
                        if(isset($messages['message_list'])){
                            $json['success'] = true;
                            $json['name'] = $assistant_name;
                            $json['output'] = "RRRRR".$messages['message_list'][0]['content'][0]['text']['value'];
                        }else{
                            $json['success'] = false;
                            $json['error'] = 'Error, failed to get messages list'.print_r($messages,true);
                            $json['status'] = "API response error. #5"; 
                        }

                        */

                    }else{
                        $json['success'] = false;
                        $json['error'] = print_r($result,true);
                        $json['status'] = "API unknown action request. #7";                      
                    }
                }else{
                    $json['success'] = true;
                    $json['tool_output'] = true;
                    //$json['output'] =""; //no output, just wait for input
                    $json['status'] = "Processing input..."; 
                }

                //$_SESSION['openai_thread_'.$thread_indicator]['run'] = null; //unset run, so its no longer running this ajax
                //$_SESSION['openai_thread_'.$thread_indicator]['run_status'] = 'completed'; //unset run, so its no longer running this ajax

            }else{
                //in progress 
                $result['status'] = $result['status'];          
            }
        }else{
            $json['success'] = false;
            $json['error'] = print_r($result,true);
            //$json['raw_result'] = $result;      
            $json['status'] = "API response error. #6";      
        }

    }
}


require_once($gc['path']['root'].'/core/aichat/ajax/custom_end.ai_assistant_check_run.php');

//$json['session'] = print_r($_SESSION, true);
echo json_encode($json);


/*



//raw_result require_action_done response from openai_run_check
{
    "assistant": "jimmy",
    "thread": "talkto_jimmy",
    "success": true,
    "name": "Jimmy Boss",
    "tool_output": true,
    "status": "Processing input..",
    "raw_result": {
        "tool_result": {
            "success": true,
            "submit_result": {
                "raw_response": "{\n  \"id\": \"run_JaRDQnGKBZODphvzvjORwqxA\",\n  \"object\": \"thread.run\",\n  \"created_at\": 1706463320,\n  \"assistant_id\": \"asst_oq1FMNqeK910ZyKkPSV1eF4H\",\n  \"thread_id\": \"thread_Cl1HEoDRF7mspO8LA36awVFK\",\n  \"status\": \"queued\",\n  \"started_at\": 1706463320,\n  \"expires_at\": 1706463920,\n  \"cancelled_at\": null,\n  \"failed_at\": null,\n  \"completed_at\": null,\n  \"last_error\": null,\n  \"model\": \"gpt-4-turbo-preview\",\n  \"instructions\": \"If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss.\",\n  \"tools\": [\n    {\n      \"type\": \"retrieval\"\n    },\n    {\n      \"type\": \"function\",\n      \"function\": {\n        \"name\": \"input_fullname\",\n        \"description\": \"Store user full name when provided\",\n        \"parameters\": {\n          \"type\": \"object\",\n          \"properties\": {\n            \"full_name\": {\n              \"type\": \"string\",\n              \"description\": \"The full name of the user\"\n            }\n          },\n          \"required\": [\n            \"full_name\"\n          ]\n        }\n      }\n    },\n    {\n      \"type\": \"function\",\n      \"function\": {\n        \"name\": \"input_orderandemail\",\n        \"description\": \"Set user email and orderid to identify order\",\n        \"parameters\": {\n          \"type\": \"object\",\n          \"properties\": {\n            \"email\": {\n              \"type\": \"string\",\n              \"description\": \"User email associated with the given order id\"\n            },\n            \"orderid\": {\n              \"type\": \"string\",\n              \"description\": \"Order id associated with the given email\"\n            }\n          },\n          \"required\": [\n            \"orderid\"\n          ]\n        }\n      }\n    }\n  ],\n  \"file_ids\": [\n    \"file-IaMCygZMTdoqs67IqTIVDqWj\"\n  ],\n  \"metadata\": {},\n  \"usage\": null\n}",
                "success": true,
                "result": {
                    "id": "run_JaRDQnGKBZODphvzvjORwqxA",
                    "object": "thread.run",
                    "created_at": 1706463320,
                    "assistant_id": "asst_oq1FMNqeK910ZyKkPSV1eF4H",
                    "thread_id": "thread_Cl1HEoDRF7mspO8LA36awVFK",
                    "status": "queued",
                    "started_at": 1706463320,
                    "expires_at": 1706463920,
                    "cancelled_at": null,
                    "failed_at": null,
                    "completed_at": null,
                    "last_error": null,
                    "model": "gpt-4-turbo-preview",
                    "instructions": "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss.",
                    "tools": [
                        {
                            "type": "retrieval"
                        },
                        {
                            "type": "function",
                            "function": {
                                "name": "input_fullname",
                                "description": "Store user full name when provided",
                                "parameters": {
                                    "type": "object",
                                    "properties": {
                                        "full_name": {
                                            "type": "string",
                                            "description": "The full name of the user"
                                        }
                                    },
                                    "required": [
                                        "full_name"
                                    ]
                                }
                            }
                        },
                        {
                            "type": "function",
                            "function": {
                                "name": "input_orderandemail",
                                "description": "Set user email and orderid to identify order",
                                "parameters": {
                                    "type": "object",
                                    "properties": {
                                        "email": {
                                            "type": "string",
                                            "description": "User email associated with the given order id"
                                        },
                                        "orderid": {
                                            "type": "string",
                                            "description": "Order id associated with the given email"
                                        }
                                    },
                                    "required": [
                                        "orderid"
                                    ]
                                }
                            }
                        }
                    ],
                    "file_ids": [
                        "file-IaMCygZMTdoqs67IqTIVDqWj"
                    ],
                    "metadata": [],
                    "usage": null
                }
            },
            "output": "subres datacalls:<pre>Array\n(\n    [0] => Array\n        (\n            [tool_call_id] => call_fvF3awv20SYaP39N3JFyRQyD\n            [output] => running function input_fullname with args {\"full_name\": \"Lucas Korn\"}\n        )\n\n    [1] => Array\n        (\n            [tool_call_id] => call_sBpld08wTbJ0q6jKLgDCvkam\n            [output] => running function input_orderandemail with args {\"orderid\": \"3909302\"}\n        )\n\n)\n<\/pre>"
        },
        "success": true,
        "result": {
            "id": "run_JaRDQnGKBZODphvzvjORwqxA",
            "object": "thread.run",
            "created_at": 1706463320,
            "assistant_id": "asst_oq1FMNqeK910ZyKkPSV1eF4H",
            "thread_id": "thread_Cl1HEoDRF7mspO8LA36awVFK",
            "status": "requires_action",
            "started_at": 1706463320,
            "expires_at": 1706463920,
            "cancelled_at": null,
            "failed_at": null,
            "completed_at": null,
            "required_action": {
                "type": "submit_tool_outputs",
                "submit_tool_outputs": {
                    "tool_calls": [
                        {
                            "id": "call_fvF3awv20SYaP39N3JFyRQyD",
                            "type": "function",
                            "function": {
                                "name": "input_fullname",
                                "arguments": "{\"full_name\": \"Lucas Korn\"}"
                            }
                        },
                        {
                            "id": "call_sBpld08wTbJ0q6jKLgDCvkam",
                            "type": "function",
                            "function": {
                                "name": "input_orderandemail",
                                "arguments": "{\"orderid\": \"3909302\"}"
                            }
                        }
                    ]
                }
            },
            "last_error": null,
            "model": "gpt-4-turbo-preview",
            "instructions": "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss.",
            "tools": [
                {
                    "type": "retrieval"
                },
                {
                    "type": "function",
                    "function": {
                        "name": "input_fullname",
                        "description": "Store user full name when provided",
                        "parameters": {
                            "type": "object",
                            "properties": {
                                "full_name": {
                                    "type": "string",
                                    "description": "The full name of the user"
                                }
                            },
                            "required": [
                                "full_name"
                            ]
                        }
                    }
                },
                {
                    "type": "function",
                    "function": {
                        "name": "input_orderandemail",
                        "description": "Set user email and orderid to identify order",
                        "parameters": {
                            "type": "object",
                            "properties": {
                                "email": {
                                    "type": "string",
                                    "description": "User email associated with the given order id"
                                },
                                "orderid": {
                                    "type": "string",
                                    "description": "Order id associated with the given email"
                                }
                            },
                            "required": [
                                "orderid"
                            ]
                        }
                    }
                }
            ],
            "file_ids": [
                "file-IaMCygZMTdoqs67IqTIVDqWj"
            ],
            "metadata": [],
            "usage": null
        },
        "status": "requires_action_done",
        "pending_input_processing": true,
        "output": "5385325runcheck:<pre>subres datacalls:<pre>Array\n(\n    [0] => Array\n        (\n            [tool_call_id] => call_fvF3awv20SYaP39N3JFyRQyD\n            [output] => running function input_fullname with args {\"full_name\": \"Lucas Korn\"}\n        )\n\n    [1] => Array\n        (\n            [tool_call_id] => call_sBpld08wTbJ0q6jKLgDCvkam\n            [output] => running function input_orderandemail with args {\"orderid\": \"3909302\"}\n        )\n\n)\n<\/pre><\/pre>"
    },
    "output": "5385325:generate input based on this:subres datacalls:<pre>Array\n(\n    [0] => Array\n        (\n            [tool_call_id] => call_fvF3awv20SYaP39N3JFyRQyD\n            [output] => running function input_fullname with args {\"full_name\": \"Lucas Korn\"}\n        )\n\n    [1] => Array\n        (\n            [tool_call_id] => call_sBpld08wTbJ0q6jKLgDCvkam\n            [output] => running function input_orderandemail with args {\"orderid\": \"3909302\"}\n        )\n\n)\n<\/pre>"
}





//requires actions run
array(3) {
  ["success"]=>
  bool(false)
  ["result"]=>
  array(19) {
    ["id"]=>
    string(28) "run_VCKjw77dcXOHRjtjjmr7q2ij"
    ["object"]=>
    string(10) "thread.run"
    ["created_at"]=>
    int(1706445792)
    ["assistant_id"]=>
    string(29) "asst_7CKyb2zVlsmDJ2bB9XfJa92x"
    ["thread_id"]=>
    string(31) "thread_EI73JQcIA6Icuj7dQhMOO0lb"
    ["status"]=>
    string(15) "requires_action"
    ["started_at"]=>
    int(1706445792)
    ["expires_at"]=>
    int(1706446392)
    ["cancelled_at"]=>
    NULL
    ["failed_at"]=>
    NULL
    ["completed_at"]=>
    NULL
    ["required_action"]=>
    array(2) {
      ["type"]=>
      string(19) "submit_tool_outputs"
      ["submit_tool_outputs"]=>
      array(1) {
        ["tool_calls"]=>
        array(1) {
          [0]=>
          array(3) {
            ["id"]=>
            string(29) "call_Ef1hyFokDD04TolJUf39h1S6"
            ["type"]=>
            string(8) "function"
            ["function"]=>
            array(2) {
              ["name"]=>
              string(19) "input_orderandemail"
              ["arguments"]=>
              string(50) "{"email":"user@example.com","orderid":"WSW123123"}"
            }
          }
        }
      }
    }
    ["last_error"]=>
    NULL
    ["model"]=>
    string(19) "gpt-4-turbo-preview"
    ["instructions"]=>
    string(337) "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss."
    ["tools"]=>
    array(3) {
      [0]=>
      array(1) {
        ["type"]=>
        string(9) "retrieval"
      }
      [1]=>
      array(2) {
        ["type"]=>
        string(8) "function"
        ["function"]=>
        array(3) {
          ["name"]=>
          string(19) "input_birthlocation"
          ["description"]=>
          string(39) "Store user birth location when provided"
          ["parameters"]=>
          array(3) {
            ["type"]=>
            string(6) "object"
            ["properties"]=>
            array(2) {
              ["birth_city"]=>
              array(2) {
                ["type"]=>
                string(6) "string"
                ["description"]=>
                string(45) "City name where place where the user was born"
              }
              ["birth_country"]=>
              array(2) {
                ["type"]=>
                string(6) "string"
                ["description"]=>
                string(48) "Country name where place where the user was born"
              }
            }
            ["required"]=>
            array(1) {
              [0]=>
              string(10) "birth_city"
            }
          }
        }
      }
      [2]=>
      array(2) {
        ["type"]=>
        string(8) "function"
        ["function"]=>
        array(3) {
          ["name"]=>
          string(19) "input_orderandemail"
          ["description"]=>
          string(44) "Set user email and orderid to identify order"
          ["parameters"]=>
          array(3) {
            ["type"]=>
            string(6) "object"
            ["properties"]=>
            array(2) {
              ["email"]=>
              array(2) {
                ["type"]=>
                string(6) "string"
                ["description"]=>
                string(45) "User email associated with the given order id"
              }
              ["orderid"]=>
              array(2) {
                ["type"]=>
                string(6) "string"
                ["description"]=>
                string(40) "Order id associated with the given email"
              }
            }
            ["required"]=>
            array(2) {
              [0]=>
              string(5) "email"
              [1]=>
              string(7) "orderid"
            }
          }
        }
      }
    }
    ["file_ids"]=>
    array(1) {
      [0]=>
      string(29) "file-IaMCygZMTdoqs67IqTIVDqWj"
    }
    ["metadata"]=>
    array(0) {
    }
    ["usage"]=>
    NULL
  }
  ["status"]=>
  string(1829) "{"id":"run_VCKjw77dcXOHRjtjjmr7q2ij","object":"thread.run","created_at":1706445792,"assistant_id":"asst_7CKyb2zVlsmDJ2bB9XfJa92x","thread_id":"thread_EI73JQcIA6Icuj7dQhMOO0lb","status":"requires_action","started_at":1706445792,"expires_at":1706446392,"cancelled_at":null,"failed_at":null,"completed_at":null,"required_action":{"type":"submit_tool_outputs","submit_tool_outputs":{"tool_calls":[{"id":"call_Ef1hyFokDD04TolJUf39h1S6","type":"function","function":{"name":"input_orderandemail","arguments":"{\"email\":\"user@example.com\",\"orderid\":\"WSW123123\"}"}}]}},"last_error":null,"model":"gpt-4-turbo-preview","instructions":"If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss.","tools":[{"type":"retrieval"},{"type":"function","function":{"name":"input_birthlocation","description":"Store user birth location when provided","parameters":{"type":"object","properties":{"birth_city":{"type":"string","description":"City name where place where the user was born"},"birth_country":{"type":"string","description":"Country name where place where the user was born"}},"required":["birth_city"]}}},{"type":"function","function":{"name":"input_orderandemail","description":"Set user email and orderid to identify order","parameters":{"type":"object","properties":{"email":{"type":"string","description":"User email associated with the given order id"},"orderid":{"type":"string","description":"Order id associated with the given email"}},"required":["email","orderid"]}}}],"file_ids":["file-IaMCygZMTdoqs67IqTIVDqWj"],"metadata":{},"usage":null}"
}



//normal run
{
  ["success"]=>
  bool(true)
  ["result"]=>
  array(18) {
    ["id"]=>
    string(28) "run_d7VvoqkcOYZXunQPFHcC13co"
    ["object"]=>
    string(10) "thread.run"
    ["created_at"]=>
    int(1706444280)
    ["assistant_id"]=>
    string(29) "asst_mBak3IncwaoEKZjRxHwnFoGQ"
    ["thread_id"]=>
    string(31) "thread_Z13fFL3px1XThsEPr7WUttfI"
    ["status"]=>
    string(11) "in_progress"
    ["started_at"]=>
    int(1706444280)
    ["expires_at"]=>
    int(1706444880)
    ["cancelled_at"]=>
    NULL
    ["failed_at"]=>
    NULL
    ["completed_at"]=>
    NULL
    ["last_error"]=>
    NULL
    ["model"]=>
    string(18) "gpt-4-1106-preview"
    ["instructions"]=>
    string(337) "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer Jimmy Boss. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss."
    ["tools"]=>
    array(1) {
      [0]=>
      array(1) {
        ["type"]=>
        string(9) "retrieval"
      }
    }
    ["file_ids"]=>
    array(1) {
      [0]=>
      string(29) "file-IaMCygZMTdoqs67IqTIVDqWj"
    }
    ["metadata"]=>
    array(0) {
    }
    ["usage"]=>
    NULL
  }
  ["status"]=>
  string(11) "in_progress"
}


*/
?>