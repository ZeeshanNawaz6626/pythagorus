<?php

require_once('../config.php'); 
only_by_permalink();

require_once($gc['path']['root_partials'].'/header.php'); 

//not really sure if recaptcha is used anywhere
?>

<script src='https://www.google.com/recaptcha/api.js'></script>

<?php

$user_added = false;
$game_added = false;
$logged_in = is_logged_in(); 


if($logged_in) {
    unset($_SESSION['user']); //logout
    header("Location: {$gc['path']['web_root']}/register_player");
    die();
}

$errors = false;
$uid = $_SESSION['user']['id'];

if (isset($_POST['submit_register'])) {


    if(isset($_POST['g-recaptcha-response']) && $_POST['g-recaptcha-response']){
        if(google_recaptcha_verify($_POST['g-recaptcha-response'],$gc['api']['google']['recaptchav3_secret'])){
            $verified = true;
        }else{
            $verified = false;
            $captcha_error .= "<center> SECURITY CHECK FAILED </center>";
        }
    }else{
        $verified = false;
        $captcha_error .= "<center> PLEASE CHECK THE CHECKBOX ABOVE </center>";
    }

    if($verified){


        //================================CHECK USER=============================
    

        $pass = clean($_POST['password']);
        $email = clean($_POST['email']);


        if ($pass == '') { 
            $errors[] = 'Please enter a password to use';
        }


        if ($pass != $_POST['password2']) {
            $errors[] = 'The passwords entered do not match';
        }



        if (!validate_email($email)) {
            $errors[] = 'An invalid email was entered';
        }else{
            //more indepth checks
            $verified_email = mail_validity_check($email); //ask the service to check
            if(in_array($verified_email,array('valid_fallback'))){
                sleep(3); //wait for it
                $verified_email = mail_validity_check($email); //ask again, if its already asked it will use db, otherwise will get the updated result from the server
            }
            if(!$verified_email){
                $errors[] = 'This email failed verification, try an other. We only accept valid personal emails, no temporary emails, catch-all, or disposable email providers.';
            }
        }
       
        if ($db->select_single_to_array('users', 'id', "WHERE email = '{$email}'")) {
            $errors[] = "An account with this email already exists. Try password recovery if you forgot your account details.";
        }


        //===============================REGISTER USER================================

        if (!$errors) {


            $key = generate_key();

            if ($gc['users']['hash_password']) {
                $pass = md5($pass);
            }

            $new_user = array(
                'password' => $pass,
                'email' => $email,
                'priv' => 0,
                'activation_key' => $key,
                't_registered' => time(),
				'ip' => $_SERVER['REMOTE_ADDR']
            );
			
			$perip = $db->select_single_to_array('users', 'count(*) as nr', "WHERE ip = '{$_SERVER['REMOTE_ADDR']}' limit 10");			
			if(isset($perip['nr']) && $perip['nr']>3){
					$errors[] = "Too many registrations from this ip. Please use your existing account. If you forgot your password you can always reset it and recover your account.";
			}else{

				$uid = $db->insert('users', $new_user);
				if ($uid) {


                    ob_start();
                        include($gc['path']['root'].'/pages/emails/account_activation.php');
                    $msg = ob_get_clean();
                    //ob_end_clean();
						
					mailto($email, "Account activation", $msg, prepare_email_name($user));
				
                    require($gc['path']['root_partials'].'/confirm.create_account.php'); ?>
                    
                   

                    <?php 
					$user_added = true;
				} else {
					$errors[] = "User not added to database, please contact an admin";
				}
			}


	    } //errors
    }//verified



}//submit


///conclusions------------------------------------------------

if(!$user_added ){
?>
    
    <?php 
    if(!$user_added){ 
        require($gc['path']['root_partials'].'/form.new_user.php');  
    } 
    ?>
  

  <script type="text/javascript" src="<?php echo $gc['js']['dir']; ?>/validate.js"></script>  

<?php } ?>


<br/>
<?php

//$page_title = "Submit your game - Free game advertising";
//$page_description = "We accept browser-based games, registration is free, list placement is determined by votes from your players."


 

$page_title = "BrowserMMORPG - Register a Free Account and increase your vote power 10x";
$page_description = "Register an account on browser mmorpg and vote with increased power and submit game reviews.";	


require_once($gc['path']['root_partials'].'/footer.php'); 
require_once($gc['path']['root'].'/output.php'); 
?>