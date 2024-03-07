<?php
require_once '../config.php';


if (isset($_REQUEST['purge_deaths'])) {
    $_SESSION['failed_admin_logins'] = false;
    die('Purged');
}

if (isset($_SESSION['failed_admin_logins']) && is_array($_SESSION['failed_admin_logins']) && count($_SESSION['failed_admin_logins']) > 4) { 
    die('We are experiencing some technical difficulties, please come back later.');
}

if (!is_logged_in()) {
    $failed = false;
    if (isset($_POST['submit_login'])) {
        if (!$_POST["username"] || !$_POST["password"]) {
            $error = "You need to provide a username and password.";
        } else {
            
            $captcha_ok = false;
            //alternate captcha if turnistile failes, switch to google
            if(isset($_SESSION['switch_login_captcha']) && $_SESSION['switch_login_captcha']==true){
                if(google_recaptcha_verify($_POST['g-recaptcha-response'],$gc['api']['google']['recaptchav3_secret'])){
                    $captcha_ok = true;
                }else{
                    $error = "The security check did not pass. Please try closing current open pages and try again, or login via google/facebook buttons.";
                }
            }else{
                if (cloudflare_verifyTurnstile($_POST['cf-turnstile-response'],$gc['api']['cfturnstile_secret_key_login'])) {
                    $captcha_ok = true;
                }else{
                    $_SESSION['switch_login_captcha'] = true;
                    $error = "You might have been confused for a robot, please try again and this time check the security checkbox.";
                    //$captcha_error .= "<center>Invalid verification</center>";   //>Click the checkbox to verify you are not a robot. 
                }
            }
            
            if($captcha_ok){
                $user = verify_login($_POST["username"], $_POST["password"]);
                if ($user) {
                    
                    $login = activate_session($user); //$_SESSION["user"] = $user;

                    if($login){

                        $db->query("UPDATE users SET ip = '{$_SERVER['REMOTE_ADDR']}', last_login='".(time())."' WHERE id = '{$user['id']}' LIMIT 1");

                        if (isset($_POST["remember"]) && $_POST["remember"]) {
                            $hash = sha1($gc['secret_key'] . $_SESSION['user']['id'] . $_SESSION['user']['username'] . $_SERVER["REMOTE_ADDR"]);
                            setcookie($gc['cookie_name'], clean($hash), time() + $gc['cookie_lifespan'],"/",$_SERVER['HTTP_HOST'],true,false);
                        }
                    }

                } else {
                    $failed = true;
                    if (strtolower($_POST["username"]) == 'admin') {
                        $_SESSION['failed_admin_logins'][] = $_POST["password"];
                        if (count($_SESSION['failed_admin_logins']) == 5) {
                        // mailto('rendril@ymail.com', 'Failed admin login', "5 Failed attempts:\n" . print_r($_SESSION['failed_admin_logins'], true));

                            ob_start();
                                include($gc['path']['root'].'/pages/emails/failed_admin_login.php');
                            $msg = ob_get_clean();
                            //ob_end_clean();

                            mailto($gc['contact_email'], 'Failed admin login', $msg, "admin", 'php');
                            sleep(120);
                        }
                        sleep(8);
                    } else {
                        sleep(3);
                    }
                }
            } //turnistile
        }
        //die("@@@1");
        update_gameowner_status();

    } elseif (isset($_COOKIE['tom'])) {
        $hash = clean($_COOKIE['tom']);
        $user = verify_safe_login($hash);
        if ($user) {
            $_SESSION["user"] = $user;
            $db->query("UPDATE users SET ip = '{$_SERVER['REMOTE_ADDR']}' WHERE id = {$_SESSION['user']['id']} LIMIT 1");
        } else {
            setcookie("tom", "", time() - (60 * 60 * 24 * 100));
            sleep(3);
        }

    }

}


if (is_logged_in()) {
    
    //header('HTTP/1.0 403 Forbidden');
    
    if(isset($_COOKIE['url_to_return_after_login'])){
        $redirurl = $_COOKIE['url_to_return_after_login'];
        unset($_COOKIE['url_to_return_after_login']); 
        header("location: {$redirurl}"); 
    }else{
        header("location: {$gc['path']['web_root']}"); //maybe create a user dashboard later
    }
    echo "Redirecting...";

    die();
}


?>

<?php require_once $gc['path']['root_partials'].'/header.php'; ?>

    <form method="post" action="" class="fiorm">
    <div class="flex justify-center items-center h-screen">
        <div class="xxbg-white p-2 xxrounded xxshadow-md w-96">
            <h2 class="text-2xl font-bold mb-4">Login</h2>
            <form>
                <div class="mb-4">
                    <label for="email" class="block text-gray-300 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="email" name="email" class="text-purple-800 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-indigo-500" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-300 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="password" name="password" class="text-purple-800 w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-indigo-500" required>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <label for="remember" class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="mr-2">
                        <span class="text-sm text-gray-300">Remember me</span>
                    </label>
                    <a href="/reset_password" class="text-sm text-indigo-300 hover:text-indigo-700">Forgot password?</a>
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-500 text-white py-2 px-4 rounded hover:bg-indigo-700">Sign In</button>
                </div>
            </form>
            <div class="mt-4">
                <p class="text-center text-gray-400">Or sign in with</p>
                <div class="flex justify-center mt-2">




                  <!-- a href="<?php echo $gc['path']['web_root']; ?>/lib/google_login.php" class="w-full inline-flex items-center justify-center py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                      <svg class="w-5 h-5 mr-2" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <g clip-path="url(#clip0_13183_10121)"><path d="M20.3081 10.2303C20.3081 9.55056 20.253 8.86711 20.1354 8.19836H10.7031V12.0492H16.1046C15.8804 13.2911 15.1602 14.3898 14.1057 15.0879V17.5866H17.3282C19.2205 15.8449 20.3081 13.2728 20.3081 10.2303Z" fill="#3F83F8"/><path d="M10.7019 20.0006C13.3989 20.0006 15.6734 19.1151 17.3306 17.5865L14.1081 15.0879C13.2115 15.6979 12.0541 16.0433 10.7056 16.0433C8.09669 16.0433 5.88468 14.2832 5.091 11.9169H1.76562V14.4927C3.46322 17.8695 6.92087 20.0006 10.7019 20.0006V20.0006Z" fill="#34A853"/><path d="M5.08857 11.9169C4.66969 10.6749 4.66969 9.33008 5.08857 8.08811V5.51233H1.76688C0.348541 8.33798 0.348541 11.667 1.76688 14.4927L5.08857 11.9169V11.9169Z" fill="#FBBC04"/><path d="M10.7019 3.95805C12.1276 3.936 13.5055 4.47247 14.538 5.45722L17.393 2.60218C15.5852 0.904587 13.1858 -0.0287217 10.7019 0.000673888C6.92087 0.000673888 3.46322 2.13185 1.76562 5.51234L5.08732 8.08813C5.87733 5.71811 8.09302 3.95805 10.7019 3.95805V3.95805Z" fill="#EA4335"/></g><defs><clipPath id="clip0_13183_10121"><rect width="20" height="20" fill="white" transform="translate(0.5)"/></clipPath></defs>
                      </svg>                            
                      Log in with Google
                  </a -->

                <!-- 
                    https://developers.google.com/identity/gsi/web/reference/html-reference#server-side 
                    https://developers.google.com/identity/gsi/web/guides/verify-google-id-token
                -->

                <script src="https://accounts.google.com/gsi/client" async defer></script>
                <div id="g_id_onload"
                    data-client_id="<?php echo $gc['api']['google']['clientID']; ?>"
                    data-context="signin"
                    data-ux_mode="redirect"
                    data-login_uri="<?php echo $gc['path']['web_root']; ?>/lib/google_callback.php"
                    data-itp_support="true"
                    data-auto_prompt="false"
                    style="margin-left: 0px;"
                    >
                </div>

                <div class="g_id_signin"
                    data-type="standard"
                    data-shape="rectangular"
                    data-theme="outline"
                    data-text="signin_with" /* continue_with signin_with */
                    data-size="large"
                    data-logo_alignment="left">
                </div>



                  <a href="<?php echo $gc['path']['web_root']; ?>/lib/facebook_login.php" class="ml-4 w-full inline-flex items-center justify-center py-2 px-2 text-xs font-medium text-gray-900 focus:outline-none bg-white rounded border border-gray-200 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" class="w-5 h-5 mr-2" style="color: #1877f2;"><path fill="currentColor" d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>                            
                      Log in with Facebook
                  </a>



                </div>
            </div>
        </div>
    </div>
    </form>

<?php 
require_once($gc['path']['root_partials'].'/footer.php'); 
require_once($gc['path']['root'].'/output.php'); 
?>