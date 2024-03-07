<?php
session_start(); //activated also in config but needed here before it
unset($_SESSION['user']); //reset current session
$_SESSION['TEST_SITE_OPEN'] = true; //disable lock if reached this page, otherwise login callback is unable to open it

require_once("../config.php");



require_once( $gc['path']['root'].'/lib/facebook/autoload.php' );
 
$fb = new Facebook\Facebook([
  'app_id' => $gc['api']['facebook']['api_id'],
  'app_secret' => $gc['api']['facebook']['app_secret'],
  'default_graph_version' => $gc['api']['facebook']['default_graph_version']
]);  
  
$helper = $fb->getRedirectLoginHelper();  
  
try 
{  
  $accessToken = $helper->getAccessToken();  
} catch(Facebook\Exceptions\FacebookResponseException $e) {  
  // When Graph returns an error  
  
	echo 'Facebool login returned a session error, please completely close this browser page and open '.$gc['path']['web_root'].' new to try again. err#1 <br> ' . $e->getMessage();  
	//echo " <br><a href='https://www.warventure.com/'>Go back</a>";
  exit;  

} catch(Facebook\Exceptions\FacebookSDKException $e) {  
  // When validation fails or other local issues  
 
	echo 'Facebook login is experiencing issues. Close the page and try again, or try [<a href="'.$gc['path']['web_root'].'/login" target="_top" >Google login</a>] instead ' ; // $e->getMessage(); 
	//echo " <br><a href='https://www.warventure.com/'>Go back</a>";    
  exit;  
}  
 
 
try 
{
  // Get the Facebook\GraphNodes\GraphUser object for the current user.
  $response = $fb->get('/me?fields=id,name,email,first_name,last_name,picture', $accessToken->getValue());
 
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
	echo  'Facebool login returned a session error, please completely close this browser page and open '.$gc['path']['web_root'].' new to try again. #err3 <br> ' . $e->getMessage();
	//echo " <br><a href='https://www.warventure.com/'>Go back</a>";
 exit;

} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
	echo 'Facebool login returned a session error, please completely close this browser page and open '.$gc['path']['web_root'].' new to try again. <br> #err4 ' . $e->getMessage();
	//echo " <br><a href='https://warventure.com/'>Go back</a>";
  exit;
}
 
$me = $response->getGraphUser();
$email = $me->getProperty('email');
$picture = $me->getProperty('picture');
$name = $me->getProperty('name');
//echo "Full Name: ".$me->getProperty('name')."<br>";
//echo "Email: ".$me->getProperty('email')."<br>";
//echo "Facebook ID: <a href='https://www.facebook.com/".$me->getProperty('id')."' target='_blank'>".$me->getProperty('id')."</a>";

$profile_pic_json = json_decode($picture,true);
$url = $profile_pic_json['url'];
$w = $profile_pic_json['width'];
$h = $profile_pic_json['height'];


//var_dump($picture);
//die($profile_pic_url);

 
$fbuser = $db->select_single_to_array('users','*',"where email='{$email}' "); //fbid = '{$me->getProperty('id')}' and
 
if($fbuser){ //get exact fb login user

  if(!$fbuser['profile_pic'] && $url){
    $result = cfimg_upload_via_url(null, $url, array('img_type'=>'profile'));
    if(isset($result['result']['id'])){
      $newkey = $result['result']['id'];
      cfimg_save_new_img(null,null,$newkey,'profile');
      $profile_pic_url = get_img_url($newkey,$w,$h);
      $db->query("update users set profile_pic='{$profile_pic_url}' where id='{$fbuser['id']}' limit 1");
      $fbuser['profile_pic'] = $profile_pic_url; //to update session too
    }
  }

  $db->query("UPDATE users SET ip = '{$_SERVER['REMOTE_ADDR']}', t_login='".(time())."' WHERE id = '{$fbuser['id']}' LIMIT 1");


	activate_session($fbuser);
  update_gameowner_status();
		
}else{

        $result = cfimg_upload_via_url(null, $url, array('img_type'=>'profile'));
        if(isset($result['result']['id'])){
          $newkey = $result['result']['id'];
          cfimg_save_new_img(null,null,$newkey,'profile');
          $profile_pic_url = get_img_url($newkey,$w,$h);
        }
        
        $newpass = generate_password(16, 10, true, true, false);
        if($gc['hash_password']){
          $newpass = md5($newpass);
        }
        
        $dbvars = array();
        //$dbvars['fbid'] = $me->getProperty('id');
        $dbvars['password'] = $newpass;
        $dbvars['email'] = $email;
        $dbvars['username'] = create_username($name);
        $dbvars['priv'] = 0;
        $dbvars['status'] = 1;
        $dbvars['profile_pic'] = $profile_pic_url;
        $dbvars['t_registered'] = time();
        $dbvars['t_lastemail'] = time();
        $dbvars['t_login'] = time();
        $dbvars['ip'] = $_SERVER['REMOTE_ADDR'];
        $newuser = $dbvars;
        
        $newid = $db->insert('users',$dbvars);

        if($newid){
            include($gc['path']['root'].'/pages/emails/account_activation_fbcallback.php');
            $msg = ob_get_clean();
            //ob_end_clean();
            
            mailto($email, "Automatic account activation", $msg, prepare_email_name($name));

            $newuser['id'] = $newid;
            activate_session($newuser);
            update_gameowner_status();
        }

}


$_SESSION['catch_ip'] = true; //catch it on the next page not now


if(isset($_COOKIE['url_to_return_after_login'])){
	$redirurl = $_COOKIE['url_to_return_after_login'];
	unset($_COOKIE['url_to_return_after_login']); 
	header("location: {$redirurl}"); 
}else{
	header("location: {$gc['path']['web_root']}"); //maybe create a user dashboard later
}
echo "Redirecting...";


?>