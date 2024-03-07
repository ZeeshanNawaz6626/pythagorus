<?php

require_once('../../config.php');
only_by_permalink();
check_auth();


require_once($gc['path']['root_partials'] . '/header.php');
require_once($gc['path']['root_partials'] . '/menu_profile.php');

$page_title = "Your settings";

?>

            <?php

            
            if (isset($_POST['action']) && $_POST['action'] == 'save') { 
                $_SESSION['user']['notify_on_missing'] = $_POST['notify_missing'] ? 1 : 0;
                $_SESSION['user']['notify_on_approval'] = $_POST['notify_approval'] ? 1 : 0;
                
                $dbvars = array();
                $dbvars['notify_on_missing'] = $_SESSION['user']['notify_on_missing'];
                $dbvars['notify_on_approval'] = $_SESSION['user']['notify_on_approval'];
                
                $db->update('users', $dbvars,"WHERE id = '{$_SESSION['user']['id']}' LIMIT 1");

                //var_dump($_POST,$dbvars);
                $user = $db->select_single_to_array('users', 'id,email,password', "WHERE id={$_SESSION['user']['id']} LIMIT 1");
                if ($user) {
                    $pop_notification = "Settings saved";
                    //$_POST['email'] != "" ||
                    if (isset($_POST['current_password']) && isset($_POST['new_password']) && $_POST['new_password'] != "") {
                        $current_password = $_POST['current_password'];
                        if ($gc['users']['hash_password']) {
                            $current_password = md5($current_password);
                        }

                        if ($current_password == $user['password']) {

                            //                    if($_POST['email'] == ""){
                            //                        $email = $_SESSION['user']['email'];
                            //                    }else{
                            //                        $email = $_POST['email'];
                            //                    }

                            if ($_POST['new_password'] == "") {
                                $pass =  $user['password'];
                            } elseif ($_POST['new_password'] == $_POST['cofirm_password']) {
                                $pass =  $_POST['new_password'];
                                if ($gc['users']['hash_password']) {
                                    $pass = md5($pass);
                                }
                            } else {
                                $pass =  false;
                                $pop_notification = "<b style='color:red;'>The passwords do not match</b><br />";
                            }

                            if ($pass) {
                                $user = array(
                                    //'email' => $email,
                                    'password' => $pass
                                );

                                $updated = $db->update('users', $user, "WHERE id = {$_SESSION['user']['id']} LIMIT 1");
                                if ($updated) {
                                    $pop_notification = "<b style='color:green;'>Settings updated</b><br />";
                                } else {
                                    $pop_notification = "Error: #2<br />There was an error updating your profile.<br />Please contact the administrator.<br />";
                                }
                            }
                        } else {
                            $pop_notification = "<b style='color:red;'>Incorrect password entered</b><br />";
                        }
                    }
                }

            }
            ?>




<?php 
//$game = $db->select_single_to_array('games', '*', "WHERE id = '{$gid}' LIMIT 1");
require_once($gc['path']['root_partials'] . '/menu_mygame.php'); ?>
<div class="shadow rounded-sm p-14 mt-4 bg-white border-gray-200 dark:border-gray-600 dark:bg-gray-900">


<form action="" method="post">
<section class="rounded-md bg-white dark:bg-gray-900 my-24">
  <div class="px-4 py-8 mx-auto md:max-w-6xl lg:py-16">
      <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Account Settings</h2>
     
          <div class="gap-4 sm:grid sm:grid-cols-2 xl:grid-cols-3 sm:gap-6 sm:mb-2">
                <div class="mb-4 space-y-4 xl:col-span-2">
                    <div>
                        <label for="current_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter your current password  (leave empty to ignore):</label>
                        <input type="password" name="current_password" id="current_password" placeholder="••••••••"  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" xxvalue="" xxrequired="">
                    </div>
                
                    <div class="items-center space-y-4 md:flex md:space-y-0">
                        <div class="relative w-full">
                                <label for="location" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">New password:</label>
                                <div class="items-center space-y-4 md:flex md:space-y-0">
                                    <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"></label>
                                    <input type="password" name="new_password" id="new_password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" xxrequired="">
                                </div>
                        </div>
                        <span class="hidden text-gray-500 md:mx-4 md:flex">to</span>
                          <div class="relative w-full">

                            <label for="cofirm_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm new password:</label>
                            <input type="confirm-password" name="confirm_password" id="cofirm_password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" xxrequired="">

                        </div>
                    </div>
                </div> 
             
              <div class="mb-4 space-y-4">

  
                  <div>
                      <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="guest-permission-checkbox">Notifications</label>
                      <div class="space-y-3">
                          <div class="flex items-center mr-4">
                              <input id="notify_missing" name="notify_missing" type="checkbox" <?php echo ($_SESSION['user']['notify_on_missing']) ? 'checked' : ''; ?> value="1" class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                              <label for="notify_missing" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Get notified when one of listing is delisted because its missing something</label>
                          </div>
                          <div class="flex items-center mr-4">
                              <input id="notify_approval" name="notify_approval" type="checkbox" <?php echo ($_SESSION['user']['notify_on_approval']) ? 'checked' : ''; ?> value="1"  class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                              <label for="notify_approval" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Get notified when something you posted gets approved</label>
                          </div>
                          <div class="flex items-center mr-4">

                                <b>Username: </b> <?php echo $_SESSION['user']['username']; ?>
                            <br>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
          <div class="flex items-center space-x-4">

            <input type="hidden" name="action" value="save" />
            <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                  Save settings
            </button>

          </div>
      
  </div>
</section>
</form>



<?php 
if($_GET['remove_account']=='notif'){ 
    $pop_notification = "Deleting your account implies deleting also your game records, and all your activity. Due to this, we kidnly ask you to contact us by email, from your account email, and request account removal.<br><br>If you bothered to click this, it means its serious, so please talk to us and lets see if we could help you change your mind.";    
}
?>




              <a href="?remove_account=notif" class="text-red-600 inline-flex items-center hover:text-white border border-red-600 hover:bg-red-600 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:border-red-500 dark:text-red-500 dark:hover:text-white dark:hover:bg-red-600 dark:focus:ring-red-900">
                  <svg class="w-5 h-5 mr-1 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                  Delete Account
                </a>




</div>




<?php
require_once($gc['path']['root_partials'] . '/footer.php');
require_once($gc['path']['root'] . '/output.php');
?>