<?php

//https://theonetechnologies.com/blog/post/how-to-get-facebook-application-id-and-secret-key
//https://developers.facebook.com/docs/facebook-login/web
require_once("../config.php");
require_once( $gc['path']['root'].'/lib/facebook/autoload.php' );
 
$fb = new Facebook\Facebook([
  'app_id' => $gc['api']['facebook']['api_id'],
  'app_secret' => $gc['api']['facebook']['app_secret'],
  'default_graph_version' => $gc['api']['facebook']['default_graph_version']
]);
 
$helper = $fb->getRedirectLoginHelper();
 
$permissions = ['email']; // Optional permissions for more permission you need to send your application for review
$loginUrl = $helper->getLoginUrl($gc['path']['web_root'].'/lib/facebook_callback.php', $permissions);
header("location: ".$loginUrl);
 
?>