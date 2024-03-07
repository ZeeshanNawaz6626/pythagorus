<?php



require_once '../config.php';

require_once($gc['path']['root'].'/config/config.openai_assistants.php'); 


openai_reset_handle();

openai_preload_assistants(array_keys($assistants), $assistants, false);




//echo "<pre>"; var_dump($_POST); echo "</pre>";

if(!isset($_SESSION['collected'])){
    $_SESSION['collected'] = array();
}

$step = 1;

//process step 1
if (isset($_POST['action']) && $_POST['action'] == 'collect_data_1') {

    $_SESSION['collected']['name'] = $_POST['yourName'];
    $_SESSION['collected']['birthday'] = $_POST['birthday'];
    $_SESSION['collected']['birthday_nice'] = date('F j, Y', strtotime($_POST['birthday']));
    $_SESSION['collected']['birthtime'] = (isset($_POST['birthtime'])) ? $_POST['birthtime'] : "00:00";

    $step = 2;
}


//process step 2
if (isset($_POST['action']) && $_POST['action'] == 'collect_data_2') {

    $_SESSION['collected']['birthplace'] = $_POST['birthplace'];
    $_SESSION['collected']['focusarea'] = $_POST['focusarea'];

    //echo "<pre>"; var_dump($_POST); die();
    header('Location: https://' . $_SERVER['HTTP_HOST'] . '/core/aichat/chat.php');
}


if($step == 2 && isset($_SESSION['collected'])){
    $personalnumber = calculatePersonalDateNumber($_SESSION['collected']['birthday']); //." ".$_SESSION['collected']['birthtime']
    $_SESSION['collected']['lifepath_number'] = $personalnumber;
}

?>



<?php require_once $gc['path']['root_partials'].'/header.php'; ?>
    
    

    <?php if($step==1){ ?>
        <div class="w-full">


            <img src="<?php echo $gc['path']['web_root']; ?>/images/logo.svg" class="w-64 h-64 mx-auto" alt="Pythagoras AI the Numerologist Assistant">
        
            <h1 class="text-2xl font-bold text-center mt-8">Lets find out more about you</h1>
            <form class="mx-auto max-w-sm mt-8" method="post" action="">
                <div class="flex flex-col mb-4">
                    <label for="yourName" class="mb-2">Your Name:</label>
                    <input type="text" id="yourName" name="yourName" required value="<?php echo @$_SESSION['collected']['name']; ?>" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                </div>
                <!-- div class="flex flex-col mb-4">
                    <label for="lastName" class="mb-2">Bla bla:</label>
                    <input type="text" id="lastName" name="lastName" required class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                </div -->
                <div class="flex flex-col mb-4">
                    <label for="birthday" class="mb-2">Birth Date:</label>
                    <input type="date" id="birthday" name="birthday" required value="<?php echo @$_SESSION['collected']['birthday']; ?>" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                </div>
                <!-- div class="flex flex-col mb-4">
                    <label for="birthday" class="mb-2">Birth Time (optional):</label>
                    <input type="time" id="birthtime" name="birthtime" value="<?php echo @$_SESSION['collected']['birthtime']; ?>" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                </div -->        
                <input type="hidden" name="action" value="collect_data_1" />
                <div class="flow-root">
                    <!-- !!Currently broken do not test!! -->
                    <button type="submit" class="float-left mt-8 px-4 py-2 bg-purple-950 border border-2 border-purple-800 hover:bg-purple-400 hover:border hover:border-2 hover:border-yellow-500 text-white rounded-md">Next</button>
                    <?php if(!is_logged_in()){ ?><a href="/login" class="float-right mt-8 px-4 py-2 xxbg-purple-950 hover:text-sky-400 text-white xxrounded-md underline">Login</a><?php } ?>
                </div>
            </form>
        </div>



        
    <?php }elseif($step==2){ ?>

        <div class="w-full">
            <h1 class="text-2xl font-bold text-center mt-8">Your lifepath number is</h1>
            <div class="p-4 text-5xl font-bold text-center mt-8" style="font-size:12em;"><?php echo $personalnumber; ?></div>
            <div class="p-4 text-4xl text-center mt-8"><?php echo $number_meanings[$_SESSION['collected']['lifepath_number']]["title"]; ?></div>
            <div class="p-4 pt-0 text-base font-italic text-center mt-1"><?php echo $number_meanings[$_SESSION['collected']['lifepath_number']]["values"]; ?></div>
            <form class="mx-auto max-w-sm mt-8" method="post" action="">

                <div class="flex flex-col mb-4">
                    <label for="birthplace" class="mb-2">Place of Birth:</label>
                    <input type="text" id="birthplace" name="birthplace" value="<?php echo @$_SESSION['collected']['birthplace']; ?>" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                </div>

                <div class="flex flex-col mb-4">
                    <label for="focusarea" class="mb-2">Select a topic:</label>
                    <select id="focusarea" name="focusarea" class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-purple-900 text-xl">
                        <?php foreach($prompts_focus as $selid => $seltext) { ?>
                            <option value="<?php echo $selid; ?>" <?php echo (@$_SESSION['collected']['focusarea']==$selid) ? 'selected' : ''; ?>><?php echo $seltext; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                <input type="hidden" name="action" value="collect_data_2" />
                <div class="flow-root">
                    <button type="submit" class="float-left mt-8 px-4 py-2 bg-purple-950 border border-2 border-purple-800 hover:bg-purple-400 hover:border hover:border-2 hover:border-yellow-500 text-white rounded-md">Chat with Pythagoras AI</button>
                    <?php if(!is_logged_in()){ ?><a href="/login" class="float-right mt-8 px-4 py-2 xxbg-purple-950 hover:text-sky-400 text-white xxrounded-md underline">Login</a><?php } ?>
                </div>
            </form>
        </div>

    <?php } ?> 



</div>
       

<?php 
require_once $gc['path']['root_partials'].'/footer.php';
require_once($gc['path']['root'] . '/output.php');
?>

