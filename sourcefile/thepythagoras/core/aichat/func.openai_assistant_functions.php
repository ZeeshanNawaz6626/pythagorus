<?php

//new version 3.7Pythagoras-1.0.0
//This func must match the assistant expected functions and arguments 
function openai_run_assistant_function($function_name, $arguments){
    $ret = array();
    $func_exists = false;
    if($function_name=='input_lifepathnumber'){
        $func_exists = true;
        if(isset($arguments['lifepathnumber']) && (int)$arguments['lifepathnumber']){
            $lifepathnumber_valid = false;
            if(((int)$arguments['lifepathnumber']>=1 && (int)$arguments['lifepathnumber']<=9) || (int)$arguments['lifepathnumber']==11 || (int)$arguments['lifepathnumber']==22 || (int)$arguments['lifepathnumber']==33){
                $lifepathnumber_valid = true;
            }
            if($lifepathnumber_valid){
                $_SESSION['collected']['lifepath_number'] = $arguments['lifepathnumber'];
                $ret['success'] = true;
                $ret['output'][] = "Acknowledged life path number {$arguments['lifepathnumber']}";
            }else{
                $ret['success'] = false;
                $ret['errors'][] = "Invalid life path number {$arguments['lifepathnumber']}";
            }
        }

    }

    //-----------------

    if($func_exists){
        if(isset($ret['errors']) && $ret['errors']){
            return implode("\n",$ret['errors']);
        }
        if(isset($ret['success']) && $ret['success']){
            return implode("\n",$ret['output']);
        }
    }else{
        return "Function {$function_name} does not exist";
    } 
    
}


?>