<?php


//didnt used this one but looks interesting: https://codeshack.io/implement-google-login-php/


session_start(); //activated also in config but needed here before it
unset($_SESSION['user']); //reset current session
$_SESSION['TEST'] = time(); //test

require_once("../config.php");

//echo "<pre>"; 


//medium security
//minimalistic implementation of google login. do not use for mission critical login systems
//although difficult, it can be bypassed by cookie manipulations and sending fake g_csrf_token 
$jwt = json_decode(@base64_decode(@str_replace('_', '/', @str_replace('-','+',explode('.', @$_POST['credential'])[1]))),true);
if(!isset($jwt['email']) || !isset($_COOKIE['g_csrf_token']) || !$_COOKIE['g_csrf_token'] || !isset($_POST['g_csrf_token']) || !$_POST['g_csrf_token'] || $_COOKIE['g_csrf_token'] != $_POST['g_csrf_token']){
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	die();
}

//IF NOT DEAD ABOVE, GO ON WITH AUTHENTICATION

$email = $jwt['email'];
$name =  $jwt['name'];
$pictureUrl =  $jwt['picture'];

$gguser = $db->select_single_to_array('users','*',"where  email='{$email}' and status=1 "); //ggid = '{$id}' and


$new_profile_pic = false;



if($gguser){ //get exact fb login user
		//echo "<br>User found."; //{$email} 
		//if picture not set but privided, download it
		$sql_profile_pic = '';
		if(!$gguser['profile_pic'] && $pictureUrl){
					
			$userfolder = '/content/users/'.$gguser['id']; // Replace '1' with the user ID or any unique identifier

			if (!file_exists($gc['path']['root'].$userfolder)) {
				mkdir($gc['path']['root'].$userfolder, 0777, true);
			}
			$new_profile_pic = $userfolder.'/profile.jpg'; //force name for security reasons
			file_put_contents($gc['path']['root'].$new_profile_pic, file_get_contents($pictureUrl));
			$sql_profile_pic = ", profile_pic = '{$new_profile_pic}' ";
		}


		$db->query("UPDATE users SET ip = '{$_SERVER['REMOTE_ADDR']}', t_login='".(time())."' {$sql_profile_pic} WHERE id = '{$gguser['id']}' LIMIT 1");
		
		//var_dump($gguser);
		activate_session($gguser);
		
}else{

		//echo "<br>New user."; //{$email}
		
		$newpass = generate_password(16, 10, true, true, false);
		if($gc['hash_password']){
			$newpass_fordb = sha1($newpass);
		}else{
			$newpass_fordb = $newpass;
		}

		$dbvars = array();
		$dbvars['password'] = $newpass_fordb;
		$dbvars['email'] = $email;
		$dbvars['priv'] = 0;
		$dbvars['credits'] = 0;
		$dbvars['status'] = 1;
		$dbvars['t_registered'] = time();
		$dbvars['t_lastemail'] = time();
		$dbvars['t_login'] = time();
		$dbvars['ip'] = $_SERVER['REMOTE_ADDR'];

		$newid = $db->insert('users',$dbvars);
			
        if($newid){

			$newuser = $dbvars;
			//upload picture and update, after creating record
			$sql_profile_pic = '';
			if($pictureUrl){
						
				$userfolder = '/content/users/'.$newid; // Replace '1' with the user ID or any unique identifier
				if (!file_exists($gc['path']['root'].$userfolder)) {
					mkdir($gc['path']['root'].$userfolder, 0777, true);
				}
				$new_profile_pic = $userfolder.'/profile.jpg'; //force name for security reasons
				file_put_contents($gc['path']['root'].$new_profile_pic, file_get_contents($pictureUrl));
				
				$db->query("UPDATE users SET profile_pic = '{$new_profile_pic}' WHERE id = '{$newid}' LIMIT 1");

			}
				
			ob_start();
               include($gc['path']['root'].'/pages/emails/account_activation_ggcallback.php');
            $msg = ob_get_clean();
            
			//update active session
            $newuser['id'] = $newid;
			$newuser['profile_pic'] = $new_profile_pic;

			//var_dump($newuser);
            activate_session($newuser);  

            mailto($email, "Automatic account activation", $msg, ""); //last param is the email


        }

}
	

$_SESSION['catch_ip'] = true; //catch it on the next page not now

if(isset($_COOKIE['url_to_return_after_login'])){
	$redirurl = $_COOKIE['url_to_return_after_login']; //redirect to the page where the user was before login
	unset($_COOKIE['url_to_return_after_login']); 
}else{
	$redirurl = $gc['path']['web_root'];
}

header("location: {$redirurl}"); 
//var_dump($_SESSION);
echo "<br>Redirecting to ... ".$redirurl;


?>