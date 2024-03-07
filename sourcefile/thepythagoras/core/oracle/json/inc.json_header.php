<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function collected_name_prompt(){
    
    //echo print_r($_SESSION,true);
    if($_SESSION['collected']['name']){
        return "I know your name is ".$_SESSION['collected']['name'];   
    }else{
        return 'Please privide your full name';
    }

}

function collected_birthday_prompt(){
    
    if($_SESSION['collected']['birthday']){
        return "you where born on ".$_SESSION['collected']['birthday_nice'];   
    }else{
        return 'please specify the date of birth';
    }

}

function collected_birthtime_prompt(){
    
    if($_SESSION['collected']['birthtime']){
        return "your time of birth was ".$_SESSION['collected']['birthtime'];   
    }else{
        return 'please specify the time of birth';
    }

}

function collected_birthplace_prompt(){
    
    if($_SESSION['collected']['birthplace']){
        return "your place of birth is ".$_SESSION['collected']['birthplace'];   
    }else{
        return 'please specify the place of birth';
    }

}

?>