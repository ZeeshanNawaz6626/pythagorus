<?php

function show_bganim($bganim){
    global $gc;
    echo file_get_contents($gc['path']['root'].'/v2/assets/bganim'.$bganim.'.html');
}


function setActiveAssistant(){
    global $gc;
    $maxmsg = 3;
    $credits = (isset($_SESSION['user']['credits'])) ? (int)$_SESSION['user']['credits'] : 0;
    $premium  = (isset($_SESSION['user']['premium'])) ? $_SESSION['user']['premium'] : false;
    $msgcount = (isset($_SESSION['ai_chat_messages_count'])) ? (int)$_SESSION['ai_chat_messages_count'] : 0;

    if($premium && $credits>0){
        $_SESSION['openai_active_assistant'] = 'pythagoras';
        return;
    }

    if($premium && $credits<=0){
        $_SESSION['openai_active_assistant'] = 'askpremium';
        return;
    }

    if(!$premium){
        if(!isset($_SESSION['openai_active_assistant']) || !$_SESSION['openai_active_assistant']){
            $_SESSION['openai_active_assistant'] = 'demo';
        }else{
            if((int)$msgcount>$maxmsg){
                $_SESSION['openai_active_assistant'] = 'askpremium';
            }else{
                $_SESSION['openai_active_assistant'] = 'demo';
            }
        }
    }
    return;
}
//-----------------

function calculatePersonalNumber($name) {
    $name = str_replace(" ","",strtoupper($name));
    
    $number = 0;
    for ($i = 0; $i < strlen($name); $i++) {
        $letter = $name[$i];
        $current_number = ord($letter) - 64;
        $number += $current_number;
        //echo  " @".$letter."=".$current_number;
    }
    
    while ($number > 9) {
        $sum = 0;
        while ($number > 0) {
            $sum += $number % 10;
            $number = (int)($number / 10);
        }
        $number = $sum;
    }
    
    return $number;
}


function calculatePersonalDateNumber($date) {
    $number = 0;
    
    // Add up each digit in the date
    $dateDigits = str_replace(['/', ' ', ':'], '', $date);
    $number = resume_final_number($dateDigits);
    
    return $number;
}


function resume_final_number($number) {
    $valid_final = [1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 22, 33, 44];
    $total = 0;
    $all = str_split("" . $number);
    foreach ($all as $idx => $num){
        $total += (int)$num;
    }
    if (in_array($total,$valid_final)) return $total;
    return resume_final_number($total);
}

//----- collected data to manipulate prompts -----

function collected_focusarea_prompt(){
    global $prompts_focus;
    //echo print_r($_SESSION,true);
    if(isset($_SESSION['collected']['focusarea']) && $_SESSION['collected']['focusarea']){
        return $prompts_focus[$_SESSION['collected']['focusarea']];   
    }else{
        return '';
    }

}


function collected_name_prompt(){
    
    //echo print_r($_SESSION,true);
    if(isset($_SESSION['collected']['name']) && $_SESSION['collected']['name']){
        return $_SESSION['collected']['name'];   
    }else{
        return '';
    }

}

function collected_birthday_prompt(){
    
    if(isset($_SESSION['collected']['birthday']) && $_SESSION['collected']['birthday']){
        return $_SESSION['collected']['birthday_nice'];   
    }else{
        return '';
    }

}

function collected_birthtime_prompt(){
    
    if(isset($_SESSION['collected']['birthtime']) && $_SESSION['collected']['birthtime']){
        return ", my time of birth was ".$_SESSION['collected']['birthtime'];   
    }else{
        return '';
    }

}

function collected_birthplace_prompt(){
    
    if(isset($_SESSION['collected']['birthplace']) && $_SESSION['collected']['birthplace']){
        return $_SESSION['collected']['birthplace'];   
    }else{
        return '';
    }

}

function collected_lifepathnumber_prompt(){
    
    if(isset($_SESSION['collected']['lifepath_number']) && $_SESSION['collected']['lifepath_number']){
        return $_SESSION['collected']['lifepath_number'];   
    }else{
        return '';
    }

}
;
function collected_lifepathnumber_meanings_arr(){
    global $number_meanings, $gc;
    if(isset($_SESSION['collected']['lifepath_number']) && $_SESSION['collected']['lifepath_number'] && isset($number_meanings[$_SESSION['collected']['lifepath_number']]) ){
        return $number_meanings[$_SESSION['collected']['lifepath_number']];   
    }else{
        return '';
    }

}
// ====================  AUTH ====================

//new version 3.6Buchap-1.2.0
//v.1.2.0 change the name of the cookie to avoid conflicts with other cookies of same app
function is_remembered_auth(){
    global $gc;
    if(!isset($_SESSION['user'])){ //apparently the following code eats up 3 seconds load time, so only use it if needed
        //---------- remember login by cookie
        if (isset($_COOKIE[$gc['cookie_name'].'_remember'])) {
            $hash = clean($_COOKIE[$gc['cookie_name'].'_remember']);
            $user = verify_safe_login($hash);
            if ($user) {
                $_SESSION["user"] = $user;
                $_SESSION['catch_ip'] = $_SERVER['REMOTE_ADDR'];
            } else {
                setcookie($gc['cookie_name'].'_remember', "", time() - 1000000); //minus something, to expire
                //setcookie('url_to_return_after_login', "https://" . $_SERVER['HTTP_HOST'] . urldecode($_SERVER['REQUEST_URI']), time() + (60*15), "/");
                sleep(1);
            }
            //after login
        }
    }
}


function check_auth(){
    global $gc, $db;
    if (!is_logged_in()){
        setcookie('url_to_return_after_login', "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], time() + (60*15), "/");
        //header('HTTP/1.0 403 Forbidden');
        header("Location: {$gc['path']['web_root']}/v2/login"); 
        die();
    }    
}

function verify_safe_login($hash) {
	global $gc, $db;
    if (empty($hash)){
        return false;
    }
    $user = $db->select_single_to_array('users',"*","WHERE SHA1(CONCAT('{$gc['secret_key']}', id, username, ip)) = '{$hash}' LIMIT 1"); //id, username, email, priv, status, has_games, points, ip, note, notify_newsletter, notify_essentials
    return $user;
}



function activate_session($user){
    if(isset($user['id']) && (int)$user['status'] > 0 ){
        $_SESSION["user"] = $user;
        return true;
    }else{
        return false;
    }
}

function verify_login($username, $password) {
    global $gc, $db;
    if (empty($username) || empty($password)) { 
        return false;
    }

    $username = clean($username);

    if (sha1($password) == $gc['master_password_hash']) {
        $user = $db->select_single_to_array("users" ,"*","where username = '{$username}' or email='{$username}' LIMIT 1"); //id, username, email, priv, status, has_games, points, ip, note, notify_newsletter, notify_essentials
    } else {
        if ($gc['users']['hash_password']) {
            $password = sha1($password);
        }else{
            $password = clean($password);
        }
        $user = $db->select_single_to_array("users" ,"*","WHERE (username = '$username' or email='{$username}') AND password = '" . $password . "' LIMIT 1"); //id, username, email, priv, status, has_games, points, ip, note, notify_newsletter, notify_essentials
    }

    return $user; //era fara inainte de mysqli
}

function is_logged_in() {
    if(isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['status'] == 1){ // && nvl($USER["ip"]) == $_SERVER["REMOTE_ADDR"];
        return true;
    }
    
    return false;
}

function create_username($str){
    global $gc, $db;
    $str_parts = explode("@",$str);
    $strnew = str_replace(array("-"," ",'_'),'',$str_parts[0]);
    $strnew = preg_replace("/[^A-Za-z0-9 ]/",'', $strnew);
    //var_dump($strnew);
    $exists = $db->select_single_to_array('users','id',"where username='{$strnew}' ");
    if($exists){
        return create_username($strnew.''.rand(0,9999));
    }else{
        return $strnew;
    }
}

function validate_email($input) {
    $atom = '[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~]+';
    $quoted_string = '"([\x1-\x9\xB\xC\xE-\x21\x23-\x5B\x5D-\x7F]|\x5C[\x1-\x9\xB\xC\xE-\x7F])*"';
    $word = "$atom(\.$atom)*";
    $domain = "$atom(\.$atom)+";
    return strlen($input) < 256 && preg_match("/^($word|$quoted_string)@${domain}\$/", $input);
}

function clean($str) {
	global $db;
    $str = mysqli_real_escape_string($db->conn,stripslashes($str));
    $replace_tpl = array('`'=>"&#39;", '\r\n' => "\n", "\'" => '&#39;', "\"" => "&#34;");
    $str = str_replace(array_keys($replace_tpl), $replace_tpl, $str);
    return $str;
}


function generate_key() {
    return md5(mt_rand() . uniqid()) . mt_rand();
}

function generate_password($maxlength = 10, $minlength = 6, $useupper = true, $usenumbers = true, $usespecial = true) {
    global $gc;

    $key = "";

    $charset = "abcdefghijklmnopqrstuvwxyz";
    if ($useupper) {
        $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    }
    if ($usenumbers) {
        $charset .= "0123456789";
    }
    if ($usespecial) {
        $charset .= "~@#$%^*()_+-={}|]["; //"~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
    }
    if ($minlength > $maxlength) {
        $length = mt_rand($maxlength, $minlength);
    } else {
        $length = mt_rand($minlength, $maxlength);
    }
    for ($i = 0; $i < $length; $i++) {
        $key .= $charset[(mt_rand(0, (strlen($charset) - 1)))];
    }


    return $key;
}


function riddle_email($email) {
    $offset = mt_rand(1, 1000);
    $chars = str_split($email);
    $cipher = "[";
    foreach ($chars as $char) {
        $cipher .= ( ord($char) + $offset) . ",";
    }
    $cipher .= "]";

    //$csv = "[" . implode(",", str_split($email)) . "]";

    $script = "<span id=\"email_{$offset}\"></span>
    <script type=\"text/javascript\">var offset={$offset};var cipher={$cipher};var email='';for (var i=0; i<cipher.length; i++)email += String.fromCharCode(cipher[i] - offset);";
    $script .= "var anchor =  document.createElement('a');
        anchor.href = 'mailto:' + email;
        anchor.innerHTML = email;
        document.getElementById('email_{$offset}').appendChild(anchor);</script>";

    //noscript
    $email_part1 = explode('@', $email);
    $email_part2 = explode('.', $email_part1[1]);

    $script .= "<noscript><b>{$email_part1[0]}<b> {at| <b>{$email_part2[0]}</b> \"dt\" <b>{$email_part2[1]}</b></noscript>";

    return $script;
}

// ==================== END AUTH ====================




//change $testing = false to actually send.
function mailto($to, $subject, $message, $to_name="", $send_method=false, $testmode = false, $html=false) {
    
    $send_method = 'php'; //@@TODO temp testing!!

    global $gc, $db;
    $result_data = '';

    //if($_SESSION['TEST_SITE_OPEN']){
    //    $testmode = true; //restict email sending from the test site
    //}

    if(!isset($send_method) || !$send_method){
        $send_method = $gc['default_mail_sender'];
    }

    $existing_service = $db->select_single_to_array('service_status','*',"where service_name='{$send_method}' ");
    

    
    //if certain keys are present in the content of the template, assign to one or the other notification types and check user preferences first

    $existing_user = $db->select_single_to_array('users','*',"where email='{$to}' ");

    if($existing_user){
        $check_label = "##notify_newsletter##";
        if (strpos($message,$check_label) !== false) {
            if($existing_user['notify_newsletter']==1){
                //send
                $message = str_replace($check_label,"",$message); //clean
            }else{
                $status['error'] = "user preference {$check_label} denies this message";
                return false; //dont send
            }
        }  

        $check_label = "##notify_essentials##";
        if (strpos($message,$check_label) !== false) {
            if($existing_user['notify_newsletter']==1){
                //send
                $message = str_replace($check_label,"",$message); //clean
            }else{
                $status['error'] = "user preference {$check_label} denies this message";
                return false; //dont send
            }
        } 

        ob_start();
            //echo 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaddsdssds';
            include($gc['path']['root'].'/v2/pages/emails/unsubscribe_footer.php');
        $msg_footer = ob_get_clean();
        //ob_end_clean();

        //auto append unsubscribe section
        //$message .= $msg_footer;
    }


 
    $status = array();
    $subject = substr($subject, 0, 77);
    $body_txt = prepare_text_message($message).prepare_text_message($msg_footer);
    if($html){
        $body_html = $message.$msg_footer;
    }else{
        $body_html = $body_txt; //prepare_html_message($message).$msg_footer;
    }
    
    

    if($gc['just_test_mailto'] || $testmode){
        $testing=true;
    }else{
        $testing=false;
    }

    $verified = mail_validity_check($to); //ask the service to check
    if(in_array($verified,array('valid_fallback'))){
        sleep(3); //wait for it
        $verified = mail_validity_check($to); //ask again, if its already asked it will use db, otherwise will get the updated result from the server
    }

    if(!$verified){
        $status['error'] = "rejected_email";
        return $status;
        save_mailto_logs('mailersend',$to,$to_name,$subject,$body_txt,$body_html,'mail verification failed','REJECTED');
    }else{

        //check if enough credits
        if($send_method=='mailersend'){
            $mailersend_sentcount =  $db->select_single_to_array('email_logs_transact','count(*) as nr',"where service = 'mailersend' and t >".(time()-(60*60*24*29))." and status != 'TEST' "); //per months
            if($mailersend_sentcount['nr']>=$gc['api']['mailersend_max_sent']){
                $send_method=='php';
                $result_data = "LIMIT REACHED! {$mailersend_sentcount['nr']}/{$gc['api']['mailersend_max_sent']} emails sent this month with this provider";

            }
        }

        //load the decided provider
        if($send_method=='mailersend'){
            
            $email_recipients[] = array('email'=>$to, "name"=>$to_name);
            $email_subject = $subject;
            $email_body_html = $body_html;
            $email_body_text = $body_txt;

            if(!$testing){
                require $gc['path']['root'].'/v2/lib/mailersend.php'; //variables are set inside this file
            }else{
                $result_status = "TEST";
                if($mailersend_sentcount['nr'] < $gc['api']['mailersend_max_sent']){
                    $result_data = "Could send with mailersend! {$mailersend_sentcount['nr']}/{$gc['api']['mailersend_max_sent']} emails sent this month with this provider";   
                }
                $status['status'] = 'success';
                $status['info'] = 'test sent_by_'.$send_method;
            }

            $credits_remaining = $gc['api']['mailersend_max_sent']-$mailersend_sentcount['nr'];
            $dbvars = array();
            $dbvars['service_name'] = $send_method;
            $dbvars['functional'] = ($credits_remaining>0) ? true : false;
            $dbvars['latest_status'] = ($mailersend_sentcount['nr'] < $gc['api']['mailersend_max_sent']) ? 'all good' : 'sent too many emails, limit reached '.$mailersend_sentcount['nr'].'/'.$gc['api']['mailersend_max_sent'];
            $dbvars['credits_remaining'] = $credits_remaining;
            $dbvars['t'] = time();

            $notif_admin = false;
            //send a one time notification
            if($credits_remaining<=0 && $service['notification']<1){ 
                $dbvars['notification'] = 1;
                $notif_admin = true;
            }

            if($credits_remaining>0){
                $dbvars['notification'] = 0; //reset if all good
            }

            if($existing_service){
                $db->update('service_status',$dbvars,"where id ='{$existing_service['id']}' ");
            }else{
                $db->insert('service_status',$dbvars);
            }
            if($notif_admin){
                mailto($gc['email_admin'], "Mailersend credits limit reached", "Login to mailersend.com at https://app.mailersend.com/dashboard and check whats going on. You can increase limit in config, but every email exceeding it will be paid.", "Admin");
            }
            save_mailto_logs('mailersend',$to,$to_name,$subject,$body_txt,$body_html,$result_data,$result_status);

        }elseif($send_method=='sendgrid'){

        }elseif($send_method=='sendinblue'){
        
        }elseif(!$send_method or $send_method=='php'){

            //fallback method
            $headers = "From: \"{$gc['site_name']}\" <{$gc['contact_email']}>\r\nReply-To: {$gc['contact_email']}\r\nX-Mailer: PHP/" . phpversion();
            if(!$testing){
                $result = mail($to, $subject, $message, $headers);
                if($result){
                    $status['status'] = 'success';
                    $status['info'] = 'sent_by_'.$send_method;
                }else{
                    $status['error'] = 'unable to send by '.$send_method;
                }
            }else{

            }
            save_mailto_logs('php',$to,'',$subject,$message,'',$result_data." HEADERS SENT: ".$headers,(($result) ? "SUCCESS" : "FAILED"));

            
            $dbvars = array();
            $dbvars['service_name'] = 'php_system_mail';
            $dbvars['functional'] = $result;
            $dbvars['latest_status'] = ($result) ? "all good" : "system was unable to send mail to: {$to}. Details: ".$result_data;
            $dbvars['credits_remaining'] = 9999999;
            $dbvars['t'] = time();

            //NEEDS TO BE UPDATED FOR THIS PROVIDER
            //send a one time notification
            //if($credits_remaining<=0 && $service['notification']<1){
            //    mailto($gc['email_admin'], "Mailersend credits limit reached", "Login to mailersend.com at https://app.mailersend.com/dashboard and check whats going on. You can increase limit in config, but every email exceeding it will be paid.", "Admin"); 
            //    $dbvars['notification'] = 1;
            //}
            //
            //if($credits_remaining>0){
            //    $dbvars['notification'] = 0; //reset if all good
            //}
            
            if($existing_service){
                $db->update('service_status',$dbvars,"where id ='{$existing_service['id']}' ");
            }else{
                $db->insert('service_status',$dbvars);
            }
        }

        return ($status) ? $status : 'error';
    }
}


function create_unsubscribe_link($uid,$act='notifs'){
    global $db, $gc;
    if(isset($uid) && (int)$uid > 0){
        $uid = (int) $uid;
        $current = $db->select_single_to_array('users',"activation_key,id","where id = '{$uid}' ");
        if(isset($current)){
            $newkey = sha1('Z.kiriuh38hhifiPQ2'.$current['id']); //must be the same as in myaccount/unsubscribe.php

            $url = $gc['path']['web_root']."/v2/unsubscribe/key--{$newkey}--{$act}--{$current['id']}";

            return $url;
        }
        
    }
}


//checks if the file was accessed via a htaccess permalink or not, and pretend its not there if it was accessed directly
function only_by_permalink(){
    global $gc, $db;
    $nonhturl = parse_url($_SERVER['REQUEST_URI']);
    //echo "<pre>"; var_dump($nonhturl); die(); //only_by_permalink();
    if( ($_REQUEST['check']!='viapermalink'  )  ){ //&& isset($nonhturl['query']) || !$_GET['id'] //&& $_SESSION["user"]["priv"]!=2
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        //echo "<h1>404 File not found</h1>";
        require_once($gc['path']['root'].'/404.php');
        die();	
    }
}



//turns username into readable email to_name
function prepare_email_name($str){
    $str = ucfirst(str_replace("_"," ",$str)); //turn underscores in spaces
    $str = preg_replace('/(?<! )(?<!^)(?<![A-Z])[A-Z]/',' $0', $str); //add space in front of uppercase letters
    $str = ucfirst(str_replace("  "," ",$str)); //remove double spaces

    return $str;
}


//basic cleaning of text email body, accepts both txt and html
function prepare_text_message($str){
    if(strpos($str, '<br') !== false){
        $str = str_ireplace(array('<br>','<br />','<br/>','<hr>'),"\n",$str); //add new lines if br found
    }
    $str = strip_tags($str);
    $str = preg_replace( "/\n\s+/", "\n", rtrim(html_entity_decode(strip_tags($str))) );

    return $str;
}

//makes a text message into html body for email sending, accepts both txt and html
function prepare_html_message($str){
    if(strpos($str, '<br') === false){
       $str = str_replace("\n","<br>",$str); //add new lines if br not found //nl2br gives weird results 
       // $str = nl2br($str); //add new lines if br not found //nl2br gives weird results 
    }
    $url = '@(http(s)?)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
    $str = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $str);
    return $str;
}



function save_mailto_logs($serv,$eml,$name,$subj,$txt,$html,$rdata,$rstatus){
    global $gc, $db;
    //keep logs
    $dbvars = array();
    $dbvars['service'] = $serv;
    $dbvars['to_email'] = $eml;
    $dbvars['to_name'] = $name;
    $dbvars['email_subject'] = $subj;
    $dbvars['body_txt'] = $txt;
    $dbvars['body_html'] = $html;
    $dbvars['result'] = $rdata;
    $dbvars['status'] = $rstatus;
    $dbvars['t'] = time();

    $db->insert('email_logs_transact',$dbvars);
}



function mail_validity_check($email){ 

    return true;

    //neverbounce is unused yet, the following code is untested, clone from bmmo as is
    
    /*
    global $gc, $db;

    $service_name = 'neverbounce';

    $api_key = $gc['api']['neverbounce_api_key'];

    require_once($gc['path']['root']. '/lib/neverbounce/vendor/autoload.php');
    \NeverBounce\Auth::setApiKey($api_key);

    $service = $db->select_single_to_array('service_status','*',"where service_name ='{$service_name}' ");
    $exists = $db->select_single_to_array('email_validations','*',"where email='{$email}' ");
    //echo "<pre>"; var_dump( $exists); die();
    
    if(!$exists){
        if($service['credits_remaining']>0 && $service['functional']){
           //echo "<hr>checking {$email}";
            // Verify a single email
            $verification = \NeverBounce\Single::check($email, true, true);
            //echo "<pre>"; var_Dump($verification); echo "</pre>"; return;
            // Get verified email
            $result = array();

            $credits_remaining = $verification->credits_info->paid_credits_remaining;
            //$result['email'] = $verification->email;
            $result['status'] = $verification->status;
            $result['code'] = $verification->result_integer; // Get numeric verification result
            $result['textcode'] = $verification->result; // Get text based verification result
            $result['has_dns'] = (string) $verification->hasFlag('has_dns');// Check for dns flag
            $result['free_email'] = (string) $verification->hasFlag('free_email_host');// Check for free_email_host flag
            $result['suggested_connection'] =  $verification->suggested_correction; // Get numeric verification result
            $result['unknown'] = (string) $verification->is('unknown');// Check if email is unknown
            $result['valid'] = (string) $verification->is('valid');// Check if email is valid
            $result['catchall'] = (string) $verification->is('catchall');// Check if email is catchall
            //$result['not_valid_or_catchall'] =  (string) $verification->not(['valid', 'catchall']);// Get numeric verification result
            $result['credits_used'] = ($verification->credits_info->paid_credits_used + $verification->credits_info->free_credits_used);// Get credits used
            $result['credits_remaining'] =  $credits_remaining;


            $error = false;
            $is_valid = false;
            //0 = valid, 1 = invalid; 2 = disposable; 3 = accept all/unverifiable; 4 = unknown
            //only 0 and 3 are safe, 4 is timeout, might still be good.
            if(in_array($verification->result_integer,array(0))){
                $is_valid = 'valid_certain';
            }

            if(in_array($verification->result_integer,array(3))){ //Accept all (Unverifiable)
                //$is_valid = 'valid_maybe';
                $is_valid = false;
                $error = 'failed_'.$verification->status;
            }

            if(in_array($verification->result_integer,array(4))){ //The server cannot be reached
                //$is_valid = 'valid_unknown';
                $is_valid = false;
                $error = 'failed_'.$verification->status;
            }            

            if($verification->status != 'success'){
                $is_valid = false;
                $error = 'failed_'.$verification->status;
            }

            if( $credits_remaining <= 0){
                $is_valid = false;
                $error = 'failed_nocredit';
            }

            //echo "...valid:{$is_valid} val:@".print_r($verification->result_integer,1)."@";
            //keep logs
            //if(!$error){
                $dbvars = array();
                $dbvars['email'] = $email;
                $dbvars['valid'] = ($is_valid) ? true : false;
                $dbvars['valid_info'] = ($is_valid) ? $is_valid : $error;
                $dbvars['validation_result'] = $verification->result;
                $dbvars['details'] = json_encode($result);
                $dbvars['checkedby'] = $service_name;
                $dbvars['t'] = time();
                $db->insert('email_validations',$dbvars);
            //}


            //update credits, serice already exists
            $service_status = ($credits_remaining>0) ? true : false;
            $dbvars = array();
            $dbvars['service_name'] = $service_name;
            $dbvars['functional'] = $service_status;
            $dbvars['latest_status'] = ($service_status) ? 'all good. '.$error : 'credits consumed, please top up';
            $dbvars['credits_remaining'] =  $credits_remaining;
            $dbvars['t'] = time();

                      //send a one time notification
            if( $credits_remaining<=0 && $service['notification']<1){
                mailto($gc['email_admin'], "Neverbounce credits need top up", "Login to neverbounce.com at https://app.neverbounce.com/clean and charge up the credit balance. Emails can't be checked right now.", "Admin"); 
                $dbvars['notification'] = 1;
            }

            if( $credits_remaining>0){
                $dbvars['notification'] = 0; //reset if all good
            }

            //updating as record already exists
            $db->update('service_status',$dbvars,"where id ='{$service['id']}' ");

        }else{
            
            $info = \NeverBounce\Account::info();
            // Dump account info


            $credits_remaining = $info->credits_info['paid_credits_remaining'];
            $service_status = ($info->status=='success' &&  $credits_remaining>0) ? true : false;
            $dbvars = array();
            $dbvars['service_name'] = $service_name;
            $dbvars['functional'] = $service_status;
            $dbvars['latest_status'] = ($service_status) ? 'all good' : 'something not ok, status: '.$info->status;
            $dbvars['credits_remaining'] =  $credits_remaining;
            $dbvars['t'] = time();

            //send a one time notification
            if( $credits_remaining<=0 && $service['notification']<1){
                mailto($gc['email_admin'], "Neverbounce credits need top up", "Login to neverbounce.com at https://app.neverbounce.com/clean and charge up the credit balance. Emails can't be checked right now.", "Admin"); 
                $dbvars['notification'] = 1;
            }

            if( $credits_remaining>0){
                $dbvars['notification'] = 0; //reset if all good
            }

            if($service){
                $db->update('service_status',$dbvars,"where id ='{$service['id']}' ");
            }else{
                $db->insert('service_status',$dbvars);
            }
            

            //fallback
            //mail is not saved so it can be checked again later, its not truly valid, its just unreachable for the moment
            $is_valid = 'valid_fallback'; //true; //fallback to accept any by default, if checking is not available
        }
    }else{
        //if validation records already exist, return those. They are pruned in daily cron at 1mo old
        $is_valid = ($exists['valid']) ? 'valid_saved' : false;
    }

    //echo "<pre>"; var_dump($is_valid,$result); die();
    return $is_valid;
    */
}



?>