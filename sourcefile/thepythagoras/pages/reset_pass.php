<?php 

require_once('../config.php'); 
only_by_permalink();

require_once($gc['path']['root_partials'].'/header.php'); 

//$page_title = "BrowserMMORPG :: Reset Password";
//$page_description = "Reset your password and access all the features of your account in just a few clicks."

?>
 






           <?php

            $show_form = true;

            if($_GET['key'] != '') {
                $show_form = false;
                $key = clean($_GET['key']);
                $new_key = generate_key();
                $new_pass = generate_password(16,10,true,true,false);
                if($gc['users']['hash_password']) {
                    $update_pass = md5($new_pass);
                } else {
                    $update_pass = $new_pass;
                }
                
                $user_update = array(
                        'activation_key' => $new_key,
                        'password' => $update_pass
                );
                if($db->update('users', $user_update, "WHERE activation_key = '{$key}' LIMIT 1")) {


                    $user = $db->select_single_to_array('users', 'username,email', "WHERE activation_key = '{$new_key}' LIMIT 1");
                    if($user) {
                        
                        ob_start();
                            include($gc['path']['root'].'/pages/emails/login_details.php');
                        $msg = ob_get_clean();
                        //ob_end_clean();

                        mailto($user['email'], "Login details", $msg, prepare_email_name($user['username']));

                        $pop_notification = "An email has been sent to {$user['email']} with your new login details.";
                    }else {
                        $pop_notification = "Unknown user";
                    }

                }else {
                    $pop_notification = '<div class="general_header">Invalid verification key</div>';
                }

            }elseif(isset($_POST['reset_password'])) {

                $errors = array();
                $page_title = "Reset password";
                $email = clean($_POST['email']);
                if(!$email){
                    $errors[] = "Missing email";
                }
                if($_POST['terms']!=1){
                    $errors[] = "You must agree to our terms and condition";
                }
                if (!cloudflare_verifyTurnstile($_POST['cf-turnstile-response'],$gc['api']['cfturnstile_secret_key_login'])) {
                    $errors[] = "The security check is invalid";
                    //$captcha_error .= "<center>Invalid verification</center>";   //>Click the checkbox to verify you are not a robot. 
                }
                
                if(!$errors){
                    $user = $db->select_single_to_array('users', 'id, username, activation_key', "WHERE email = '{$email}' AND status = 1 LIMIT 1");
                    if($user) {
                        $key = generate_key();
                        $db->update('users', array('activation_key' => $key), "WHERE id = '{$user['id']}' LIMIT 1");
                        $msg = "";
                        
                        ob_start();
                            include($gc['path']['root'].'/pages/emails/password_reset.php');
                        $msg = ob_get_clean();
                        //ob_end_clean();

                        mailto($email, "Password reset", $msg, prepare_email_name($user['username']));

                        $pop_notification = "An email has been sent to {$email} with the confimation link to reset your password.";

                        $show_form = false;
                    }else {
                        $pop_notification =  'ERROR:<div class="general_header">Email not found</div>';
                        $show_form = true;
                    }
                }else{
                    $show_form = true;
                }
            } ?>


  
<?php if($show_form){ ?>
            <section class=" dark:bg-gray-900">
            <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
                <a href="<?php echo $gc['path']['web_root']; ?>" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
                            <img src="<?php echo $gc['path']['web_root']; ?>/img/logo.svg" class="mr-3 h-8" alt="<?php echo $gc['site_name']; ?> Logo" />
                            <?php echo $gc['site_name']; ?>   
                </a>
                <div class="w-full p-6 bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md dark:bg-gray-800 dark:border-gray-700 sm:p-8">
                    <h1 class="mb-1 text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                        Forgot your password?
                    </h1>
                    <p class="font-light text-gray-500 dark:text-gray-400">Don't fret! Just type in your email and we will send you a code to reset your password!</p>
                    <form class="mt-4 space-y-4 lg:mt-5 md:space-y-5" action="" method="post">
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
                            <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="name@company.com" required="yes">
                        </div>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="terms" name="terms" value="1" aria-describedby="terms" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800" required="">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="terms" class="font-light text-gray-500 dark:text-gray-300">I accept the <a class="font-medium text-primary-600 hover:underline dark:text-primary-500" href="<?php echo $gc['path']['web_root']; ?>/info_terms">Terms and Conditions</a></label>
                            </div>
                        </div>

                        <?php if($errors){ ?>
                                    <div class="text-red-600 bg-red-50 p-4 ml-3 text-sm">
                                        <?php echo "-".implode("<br>-",$errors); ?>
                                    </div>
                        <?php } ?>

                        <button type="submit" name="reset_password" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Reset password</button>

                    
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                        <div class="cf-turnstile" data-sitekey="<?php echo $gc['api']['cfturnstile_site_key_login']; ?>" data-callback="javascriptCallback" data-theme="light" data-size="compact"></div>

                    </form>
                </div>
            </div>
            </section>

<?php } ?>



    <br>
    <br>
    <br>
    <br>
    <br>
    

<?php 
require_once($gc['path']['root_partials'].'/footer.php'); 
require_once($gc['path']['root'].'/output.php'); 
?>