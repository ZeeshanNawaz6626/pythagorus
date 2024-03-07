<?php

require_once("../config.php");

//require_once($gc['path']['root']. '/core/premium/paypal.inc.php');

$notify_to = 'mmoprg.tech@gmail.com';   //$notify_to = 'rendril@ymail.com';

$data=array();
parse_str(file_get_contents('php://input'), $data);
$data['cmd'] = '_notify-validate';
$req = http_build_query($data,'','&');


// Step 2: POST IPN data back to PayPal to validate
$ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
if (!($res = curl_exec($ch))) {
    // error_log("Got " . curl_error($ch) . " when processing IPN data");
    curl_close($ch);
    exit;
}
curl_close($ch);

//--- end ---

//https://developer.paypal.com/api/nvp-soap/ipn/IPNandPDTVariables/

$txn_type_allow = array('masspay','merch_pmt','pro_hosted','send_money','virtual_terminal','express_checkout','cart','','recurring_payment','subscr_payment','web_accept');

// assign posted variables to local variables
$item_name = $_POST['item_name'];
$item_number = $_POST['item_number'];
$payment_status = $_POST['payment_status'];
$payment_amount = $_POST['mc_gross'];
$payment_currency = $_POST['mc_currency'];
$txn_id = $_POST['txn_id'];
$txn_type = $_POST['txn_type']; //subscr_payment, subscr_signup, subscr_cancel, subscr_modify, subscr_eot
$receiver_email = $_POST['receiver_email'];
$payer_email = $_POST['payer_email'];

$verif = false;
if (strcmp($res, "VERIFIED") == 0) {
    $verif = true;
} //else if (strcmp($res, "INVALID") == 0) { }



$err = array();

//https://www.mixedwaves.com/2010/11/paypal-subscriptions-ipn-demystified/
if(in_array($txn_type,$txn_type_allow)){
    if($verif) {
        if(strtolower($payment_status) == 'completed') {


            //$txn = $db->select_single_to_array('paypal_reports', 'count(*) as nr', "WHERE txn_id = '" . $txn_id . "' group by txn_id");
            //if ($txn['nr'] > 0) {
            //    $err[] = 'duplicate transaction for ' . $txn_id; //sadly there are too many recs with same txt right now, cant check this
            //}

            //out here only the general error valid for both kind of payments, aicredits and premium

            if($payment_currency!='USD') {
                $err[] = 'Payment currency is '.$payment_currency;
            }
            if($payment_amount <= 0) {
                $err[] = 'Payment amount is negative ('.$payment_amount.')';
            }


            if($item_number == "A100K"){

                $uid = (int)($_POST['option_selection1']);

                if(!$uid) {
                    $err[] = 'Missing user id';
                }
                    

                if(!$err) {
                    
                    $userinfo = $db->select_single_to_array('users', 'id,username,email,aicredits', "WHERE id = '{$uid}' LIMIT 1");
                    $user_update = array (
                        'aicredits' => $userinfo['aicredits']+100000
                    );
                    $verif = $db->update('users', $user_update, "WHERE id = '{$uid}' LIMIT 1");

                }


            }elseif($item_number == "A10K"){

                $uid = (int)($_POST['option_selection1']);

                if(!$uid) {
                    $err[] = 'Missing user id';
                }
                    

                if(!$err) {
                    
                    $userinfo = $db->select_single_to_array('users', 'id,username,email,aicredits', "WHERE id = '{$uid}' LIMIT 1");
                    $user_update = array (
                        'aicredits' => $userinfo['aicredits']+10000
                        );
                    $verif = $db->update('users', $user_update, "WHERE id = '{$uid}' LIMIT 1");

                }


            }else{


                //DO NOT REVERSE THESE
                $gid = (int)($_POST['option_selection1']);
                $uid = (int)($_POST['option_selection2']);

                if(!$uid) {
                    $err[] = 'Missing user id';
                }           
                if(!$gid) {
                    $err[] = 'Missing game id';
                }  


                $premium_type = false;
                switch($item_number) {
                    case $gc['prem_m2m_item_number'] :
                        $premium_type = 'prem_m2m';
                        break;
                    case $gc['prem_sub_item_number'] :
                        $premium_type = 'prem_sub';
                        break;
                }

                if(!$premium_type) {
                    $err[] = 'Invalid premium type requested';
                }

                //if(in_array($payment_amount,array($gc[$premium_type . '_price'],13,14,20,30))) {
                    //totul ok, inclusiv subsrieri mai vechi
                //}else{
                //    $err[] = 'Payment amount does not match item price';
                //}

                if($gid) {
                    $game = $db->select_single_to_array('games', '*', "WHERE id = '{$gid}' LIMIT 1");
                    if(!$game){
                        $err[] = 'Game does not exist';
                    }
                }

                if(!$err) {
                    
                    
                    $userinfo = $db->select_single_to_array('users', 'id,username,email,aicredits', "WHERE id = '{$uid}' LIMIT 1");
        
                    give_stats($game, 'pos', $gc['monthly_vote_increase'], 1);

                    

                    //all ok

                        $game_update = array (
                        'premium' => $game['premium'] + $gc['prem_duration']
                        );
                        $verif = $db->update('games', $game_update, "WHERE id = '{$gid}' LIMIT 1");

                        $newcredits =  $userinfo['aicredits']+2500;
                        $db->query("update users set aicredits='{$newcredits}' where id='{$userinfo['id']}' ");


                }
            } 
        } else {
            
            //just not completed
            $err[] = 'Payment status is '.$payment_status;
            

        }
        


    }else{
        $err[] = 'Invalid Premium Payment at '.$gc['site_name'];
    }
}else{
    mailto($notify_to, "DIFFERENT TRX_TYPE RECEIVED", "This might mean that the paypal ipn is abused, or most likely that it is not correctly configured and is skipping payments. It can also mean that you reeived money on this address in other ways. <br><br><pre>".print_r($_POST,1)."</pre>", prepare_email_name($gc['site_name']),false,false,true);
}

if ($err) {

    $new_paypal_report = array(
        'txn' => $txn_id,
        'game' => $gid,
        'user' => $uid,
        'payer_email' => $payer_email,
        'receiver_email' => $receiver_email,
        'item_nr' => $item_number,
        'item_name' => $item_name,
        'amount' => $payment_amount,
        'status' => 0,
        'post' => print_r($_POST, true),
        'timestamp' => time(),
        'error' => @implode("\n#", $err)
    );

    $report_id = $db->insert('paypal_reports', $new_paypal_report);

    $subject = "(report:{$report_id}) FAILED Premium Payment {$gc['site_name']} {$payment_amount} for {$item_name}";
    ob_start();
    include($gc['path']['root'] . '/pages/emails/debug_premium_payment_error.php');
    $msg = ob_get_clean();
    mailto($notify_to, $subject, $msg, prepare_email_name($gc['site_name']), 'php');

} else {

    $new_paypal_report = array(
        'txn' => $txn_id,
        'game' => $gid,
        'user' => $uid,
        'payer_email' => $payer_email,
        'receiver_email' => $receiver_email,
        'item_nr' => $item_number,
        'item_name' => $item_name,
        'amount' => $payment_amount,
        'status' => 1,
        'post' => '',
        'timestamp' => time(),
        'error' => ''
    );

    $report_id = $db->insert('paypal_reports', $new_paypal_report);

    $subject = "(report:{$report_id}) SUCCESS Premium Payment {$gc['site_name']} {$payment_amount} for {$item_name}";
    ob_start();
    include($gc['path']['root'] . '/pages/emails/debug_premium_payment_noerr.php');
    $msg = ob_get_clean();
    mailto($notify_to, $subject, $msg, prepare_email_name($gc['site_name']), 'php');
    
}
