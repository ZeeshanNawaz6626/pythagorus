<?php
//new version 3.7Pythagoras-1.0.0
//reads text and returns a file url
require_once('../../../config.php'); 
require_once($gc['path']['root'].'/config/config.openai_assistants.php'); 
//require_once($gc['path']['root'].'/core/aichat/func.openai_assistant_functions.php');

if($gc['openai_chat_in_admin']){
    check_auth();
}

//===================================================================================================

header('Content-Type: application/json');

$json = array();


$request = file_get_contents("php://input"); // gets the raw data
$params = json_decode($request,true); // true for return as array

//assistant indicator
if(!isset($params['text']) || !trim($params['text'])){
    $json['success'] = false;
    $json['error'] = "Missing text to read";
    $json['status'] = "TTS error #1";
    echo json_encode($json);
    die();
}

$voice = 'alloy';
if(isset($params['voice']) && trim($params['voice'])){
    $voice = $params['voice'];
}

$hd = false;
if(isset($params['hd']) && $params['hd']){
    $hd = $params['hd'];
}



$title = sha1($params['text']);

//cache mechanism, path must be the same as the one in openai_text_to_speach()
$file_url = $gc['path']['web_root'] . "/storage/audio/" . $title . ".aac";
$file_path = $gc['path']['root'] . "/storage/audio/" . $title . ".aac";

if(file_exists($file_path)){
    $json['success'] = true;
    $json['status'] = "Reading...";
    $json['file_url'] = $file_url;
    $json['cache'] = true;
    echo json_encode($json);
    die();
}

//no errors, continue
if(!isset($json['error'])){
    $writefile = openai_text_to_speach($params['text'],$title,$voice,$hd);
    if(isset($writefile['success']) && $writefile['success']){
        $json['success'] = true;
        $json['status'] = "Reading...";
        $json['file_url'] = stripslashes($writefile['file_url']);
    }else{
        $json['success'] = false;
        $json['status'] = "Unable to save file";
        $json['error'] = $writefile['error'];
    }
}


echo json_encode($json);

?>