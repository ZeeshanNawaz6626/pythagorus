<?php

require_once('../../config.php');
only_by_permalink();
check_auth();


require_once($gc['path']['root_partials'] . '/header.php');
require_once($gc['path']['root_partials'] . '/menu_profile.php');

$page_title = "Unsubscribe";

$uid = (int) $_GET['uid'];

if(!$uid){
    $pop_notification ='missing id. Bad url, please copy paste the url in the browser address bar, or visit your settings page to make the required changes';
}else{
    $user = $db->select_single_to_array('users', 'id,email,username,notify_newsletter,notify_essentials', "WHERE id='{$uid}' LIMIT 1");

    if($user){
        if($_GET['key'] == sha1('Z.kiriuh38hhifiPQ2'.$user['id'])){ //must be the same as in func create_unsubscribe_link()
            if($_GET['act']=='notifs'){
                if($user['notify_newsletter'] == 0 && $dbvars['notify_essentials'] == 0){
                    $pop_notification = "<b class='test-lg'>Already unsubscribed</b><br>your notifications are already disabled. If you still received unwanted email from us, please reply to it or forward it to us, and we will investigate the situation.";
                }else{
                    $dbvars = array();
                    $dbvars['notify_newsletter'] = '0';
                    $dbvars['notify_essentials'] = '0';
                    $r = $db->update('users', $dbvars,"WHERE id = '{$user['id']}' LIMIT 1");
                    $pop_notification = "<b class='test-lg'>Unsubscribe successful</b><br>All your notification settings disabled. You can enable them back via your settings page, after you login.";
                }

                //$db->query("UPDATE users SET `notify_newsletter` = '2', `notify_essentials` = '2' WHERE id = '{$user['id']}' LIMIT 1");
                //echo "UPDATE users SET `notify_newsletter` = '0', `notify_essentials` = '0' WHERE id = '{$user['id']}' LIMIT 1";
                //var_dump($db);
                
            }else{
                $pop_notification = "undefined action, please edit these settings from your profile page, appologies for the inconvenience.";
            }
        }else{
            $pop_notification = "invalid key, probaly proken link, try copy pasting it or go to settings in your account, appologies for the inconvenience.";
        }
    }else{
        $pop_notification = 'Unable to find this user. Bad url, or removed account. Please copy paste the url in the browser address bar, or visit your settings page to make the required changes';
    }
}
?>

<div style="height:500px" class="text-lg p-24">
<?php echo $pop_notification; ?>
</div>


<?php
require_once($gc['path']['root_partials'] . '/footer.php');
require_once($gc['path']['root'] . '/output.php');
?>