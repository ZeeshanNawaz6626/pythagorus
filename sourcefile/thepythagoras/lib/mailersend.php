<?php
use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Helpers\Builder\EmailParams;

if($gc && count($email_recipients)>0 && $email_subject && $email_body_html && $email_body_text){

    require_once $gc['path']['root'].'/lib/mailersend/vendor/autoload.php';

    $mailersend = new MailerSend(['api_key' => $gc['api']['mailersend_api_key']]);

    $recipients = array();
    foreach($email_recipients as $recip){ 
        $recipients[] =  new Recipient($recip['email'],$recip['name']);
    }

    $emailParams = (new EmailParams())
        ->setFrom($gc['email_send'])
        ->setFromName($gc['site_name'])
        ->setRecipients($recipients)
        ->setSubject($email_subject)
        ->setHtml($email_body_html)
        ->setText($email_body_text);

        $result = $mailersend->email->send($emailParams);

    $result_status = 'SENT';
    $result_data = json_encode($result);

    if($result){
        $status['status'] = 'success';
        $status['info'] = 'sent_by_'.$send_method;
    }else{
        $status['error'] = 'unable to send by '.$send_method;
    }

}else{
    $result_status = 'FAILED';
    $result_data = "ERROR: missing email data";
    $status['error'] = 'unable to send by '.$send_method. ", missing email data";
    
}

?>