
<?php

if(!$gc) die('no config');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require $gc['path']['root'].'/lib/PHPMailer-master/src/Exception.php';
require $gc['path']['root'].'/lib/PHPMailer-master/src/PHPMailer.php';
require $gc['path']['root'].'/lib/PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer();
$mail->IsSMTP();
$mail->Mailer = "smtp";

$mail->SMTPDebug  = 1;  
$mail->SMTPAuth   = TRUE;
$mail->SMTPSecure = "tls";
$mail->Port       = 587;
$mail->Host       = "smtp.gmail.com";
$mail->Username   = "mmorpg.tech@gmail.com";
$mail->Password   = "...";

$mail->IsHTML(true);
$mail->AddAddress("someone@destination.bla", "recipient-name");
$mail->SetFrom("mmorpg.tech@gmail.com", "from-name");
//$mail->AddReplyTo("reply-to-email@domain", "reply-to-name");
//$mail->AddCC("cc-recipient-email@domain", "cc-recipient-name");
$mail->Subject = "Test is Test Email sent via Gmail SMTP Server using PHP Mailer";
$content = "<b>This is a Test Email sent via Gmail SMTP Server using PHP mailer class.</b>";

$mail->MsgHTML($content); 
if(!$mail->Send()) {
  echo "Error while sending Email.";
  var_dump($mail);
} else {
  echo "Email sent successfully";
}


?>