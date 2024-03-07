<?php
/* This script is for customer support purposes only */

//if (isset($_GET["password"]) && $_GET["password"] == "Ç_M4tr1x123_Ç") {
//    phpinfo();
//    die();
//}

ini_set("display_errors", 0);

include('../../config.php');

/*
die();
function emulate_api_response($str){
    $ret = '';

    $data = array();
    $data['id'] = 'chatcmpl-null';
    $data['object'] = 'chat.completion.chunk';
    $data['created'] = time();
    $data['model'] = 'gpt-4-0613';
    $data['system_fingerprint'] = null;
    $data['choices'] = array();
    $data['choices'][0] = array();
    $data['choices'][0]['index'] = 0;
    $data['choices'][0]['delta'] = array();
    $data['choices'][0]['delta']['role'] = 'assistant';
    $data['choices'][0]['delta']['content'] = "";
    $data['choices'][0]['logprobs'] = null;
    $data['choices'][0]['finish_reason'] = null;
    $retjson = json_encode($data);

    $ret .= 'data: '.$retjson. PHP_EOL;

    $data = array();
    $data['id'] = 'chatcmpl-null';
    $data['object'] = 'chat.completion.chunk';
    $data['created'] = time();
    $data['model'] = 'gpt-4-0613';
    $data['system_fingerprint'] = null;
    $data['choices'] = array();
    $data['choices'][0] = array();
    $data['choices'][0]['index'] = 0;
    $data['choices'][0]['delta'] = array();
    //$data['choices'][0]['delta']['role'] = 'assistant';
    $data['choices'][0]['delta']['content'] = $str;
    $data['choices'][0]['logprobs'] = null;
    $data['choices'][0]['finish_reason'] = null;
    $retjson = json_encode($data);

    $ret .= 'data: '.$retjson. PHP_EOL;

    $data = array();
    $data['id'] = 'chatcmpl-null';
    $data['object'] = 'chat.completion.chunk';
    $data['created'] = time();
    $data['model'] = 'gpt-4-0613';
    $data['system_fingerprint'] = null;
    $data['choices'] = array();
    $data['choices'][0] = array();
    $data['choices'][0]['index'] = 0;
    $data['choices'][0]['delta'] = array();
    //$data['choices'][0]['delta']['role'] = 'assistant';
    //$data['choices'][0]['delta']['content'] = $str;
    $data['choices'][0]['logprobs'] = null;
    $data['choices'][0]['finish_reason'] = null;
    $retjson = json_encode($data);

    $ret .= 'data: '.$retjson. PHP_EOL;
    $ret .= 'data: '.'[DONE]'. PHP_EOL;
    return $ret;
}


echo emulate_api_response("The maximum limit of conversations in demo mode has been reached, please login or register to continue.");
ob_flush();
flush();
die();
*/


//include('key.php');
$API_KEY = $gc['api']['openai_api_key'];

//if free credits are over, show error
include('premium-chat.php');



//var_dump($_SESSION);

// Read input data
$model = $_POST["model"];
$messages = $_POST["array_chat"];
$messages = urldecode($messages);
$messages = json_decode($messages, true);

$character_name = $_POST["character_name"];
$temperature = floatval($_POST["temperature"]);
$frequency_penalty = floatval($_POST["frequency_penalty"]);
$presence_penalty = floatval($_POST["presence_penalty"]);

$header = [
    "Authorization: Bearer " . $API_KEY,
    "Content-type: application/json",
];

if (strpos($model, "gpt") !== false) {
    //Turbo model
    $isTurbo = true;
    $url = "https://api.openai.com/v1/chat/completions";
    $params = json_encode([
        "messages" => $messages,
        "model" => $model,
        "temperature" => $temperature,
        "max_tokens" => 1024,
        "frequency_penalty" => $frequency_penalty,
        "presence_penalty" => $presence_penalty,
        "stream" => true
    ]);
} else {
    $isTurbo = false;
    //Not a turbo model
    $chat = "";
    foreach ($messages as $msg) {
        $role = $msg["role"];
        $content = $msg["content"];
        if ($role == "system" || $role == "assistant") {
            $chat .= "$character_name: $content\n";
        } elseif ($role == "user") {
            $chat .= "user: $content\n";
        }
    }
    $url = "https://api.openai.com/v1/engines/$model/completions";
    $params = json_encode([
        "prompt" => "The following is a conversation between $character_name and user: \n\n$chat",
        "temperature" => $temperature,
        "max_tokens" => 1024,
        "frequency_penalty" => 0,
        "presence_penalty" => 0,
        "stream" => true
    ]);
}

// Store params in a local file
$file = fopen("catch_params.txt", "a");
fwrite($file, $params . "\n----------------".date("d m Y H:i:s")."----------------\n");
fclose($file);

$curl = curl_init($url);
$options = [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_POSTFIELDS => $params,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        //echo $curl;
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
           $r = json_decode($data);
           echo 'data: {"error": "[ERROR]","message":"'.$r->error->code."  ".$r->error->message.'"}' . PHP_EOL;
        }else{
            //echo 'data: {"id":"chatcmpl-8eSHU56POYYDijfb5qcQcXja9BlCj","object":"chat.completion.chunk","created":1704652524,"model":"gpt-4-0613","system_fingerprint":null,"choices":[{"index":0,"delta":{"content":" details"},"logprobs":null,"finish_reason":null}]}' . PHP_EOL;
            echo $data;
            ob_flush();
            flush();
            return strlen($data);
        }
    },
];

curl_setopt_array($curl, $options);
$response = curl_exec($curl);

