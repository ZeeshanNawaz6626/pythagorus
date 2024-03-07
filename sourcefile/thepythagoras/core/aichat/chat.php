<?php 



/*

🆃🅴🅲🅷, [19/01/2024 20:29]

integrate mailersend
integrate facebook 
integrate paypal
make customer payment page for paypal
make customer chat history page
make customer account page

*/

require_once '../../config.php';











//preloading assistants and loading threads must be done before any output or cookies wont save
require_once($gc['path']['root'].'/core/aichat/func.openai_assistant_functions.php'); //new 
require_once($gc['path']['root'].'/config/config.openai_assistants.php'); 
openai_reset_handle();
$default_thread = 'talkto_jimmy';
//openai_update_threads_cookie(); //ran internally by openai_init_thread()
$load_cookie = openai_load_threads_from_cookie();
$thread_init = openai_init_thread($default_thread);
openai_preload_assistants(array_keys($assistants), $assistants);
//echo "<pre>"; var_dump($load_cookie,$thread_init); echo "</pre>";



$_SESSION['openai_thread_'.$default_thread]['count_messages'] = false; //marking if the moment to show focus options has passed

//$assistants = openai_list_assistants();
//var_debug($assistants);

$default_assistant = 'demo';

//no need to delete unless you want to reset the thread
//openai_delete_thread($default_thread); 

setActiveAssistant(); //sets session depending on number of messages sent

?>


<?php require_once $gc['path']['root_partials'].'/header.php'; ?>


<script>
    var web_root = '<?php echo $gc['path']['web_root']; ?>';
    var thread_indicator = '<?php echo $default_thread; ?>';
    var presented_focus_options = false;
</script>

<style>
/*    #ai_chat_response {
        height: 400px;
    }
    */

   .ai_chat_prompt {
    margin-bottom: 15px;
    margin-top: 15px;
    margin-left: 20px;
    margin-right: 20px;
    background-color: #fff361;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #f9d834;
    color: #000;
    cursor: pointer;
   } 
</style>    

<div class="container">
<form method="post" action="" id='ai_chat_form'>

    <div class="flex justify-center gap-4">
  
    <!-- div class="mb-3 xl:w-96">
        <select name='assistant' id='assistant' class="form-select appearance-none block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding bg-no-repeat border border-solid border-gray-300 rounded
        transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none" aria-label="Default select example">
            <option value="none" >None</option>
            <?php foreach($assistants as $assist_id => $assist_data){ ?>
                <option value="<?php echo $assist_id; ?>" <?php echo (isset($default_assistant) && $default_assistant==$assist_id) ? 'selected' : ''; ?> ><?php echo $assist_data['name']; ?></option>
            <?php } ?>
        </select>
    </div -->

        <input type="hidden" name="assistant" id="assistant" value="<?php echo $default_assistant; ?>" />    

    </div>



    <div id="ai_chat_response" class="w-full md:max-w-3xl md:w-4/5 mx-auto h-fit overflow-auto block p-2.5 text-xs text-gray-900 rounded-lg  focus:ring-blue-500 focus:border-blue-500   mb-24"></div>
    
    <div id="ai_chat_input_area" class="flex justify-center w-full mx-auto mb-20">
        
        <input type="text" id='ai_chat_message' name="ai_chat_message" class="ml-2 focus mt-2 h-14 pl-4 w-3/4 text-base text-purple-900 rounded-lg border border-2 border-yellow-500" placeholder="Write something..." />
        
        <a href="#" id="ai_chat_submit_button" class="bg-white mt-2 w-20 h-14 -ml-20 relative inline-flex items-center justify-center p-2 px-3 py-2 overflow-hidden font-medium text-yellow-600 transition duration-300 ease-out border-2 border-orange-300 rounded-xl shadow-md group">
        <span class="absolute inset-0 flex items-center justify-center w-full h-full text-white duration-300 -translate-x-full bg-purple-500 group-hover:translate-x-0 ease">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </span>
        <span class="absolute flex items-center justify-center w-full h-full text-purple-500 transition-all duration-300 transform group-hover:translate-x-full ease text-sm">Send</span>
        <span class="relative invisible">Send</span>
        </a>       

    </div>
    
</form>

<div id='ai_chat_status' class="mx-auto text-xs p-1 z-30 w-32 mb-1 rounded-lg bg-purple-100 text-purple-300 text-center"></div>

<div class="hidden typing">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap</div>

<div id="chat_message_template_system" class="hidden">
    <div class="message-line-system flow-root mb-4 w-full ">
        <div class="float-left max-w-prose p-6 border border-0 border-purple-600 bg-orange-50 text-sm text-purple-900 rounded-tr-xl rounded-br-xl rounded-bl-xl shadow-md">
        <div class="w-24"><time datetime="{{time}}" class="timeago hidden -mt-5 -ml-4 float-left text-xs text-gray-400"></time></div>
        <div class="text-sm text-purple-400 xxhidden float-right -mt-5 mr-4">{{name}}</div>
        <div class="ai_read_text text-sm text-white p-1 float-right -mt-5 -mr-4 cursor-pointer" data-target="response_msg"  data-voice="onyx"><img src='img/play.svg' class='w-6 h-6' /></div>
        <div class='response_msg hidden' id='{{id}}'>{{message}}</div>
        </div>
    </div>
</div>
<div id="chat_message_template_tool_response" class="hidden">
    <div class="message-line-system flow-root mb-4 w-full cursor-pointer">
        <div class="float-left max-w-prose p-6 border border-1 border-purple-700 bg-purple-900 text-sm text-slate-400  rounded-lg shadow-md">
        <div class="w-24"><time datetime="{{time}}" class="timeago hidden -mt-5 -ml-4 float-left text-xs text-gray-400"></time></div>
        <div class="text-sm text-red-600 xxhidden float-right -mt-5 -mr-4">System<!-- {{name}} --></div>
        <div class='response_msg'>{{message}}</div>
        </div>
    </div>
</div>
<div id="chat_message_template_user" class="hidden">
    <div class="message-line-user flow-root mb-4 w-full ">
        <div class="float-right max-w-prose p-6 border border-0 border-yellow-500 bg-purple-200 text-sm text-purple-900 rounded-tl-xl rounded-br-xl rounded-bl-xl shadow-md">
        <div class="w-24"><time datetime="{{time}}" class="timeago hidden -mt-5 -mr-4 float-right text-xs text-gray-400"></time></div>
        <div class="text-sm text-purple-400 font-bold xxhidden float-left -mt-5 -ml-4">{{name}}</div>
        <div class='response_msg' id='{{id}}'>{{message}}</div>
        </div>
    </div>
</div>
<div id="chat_message_template_typing" class="hidden">
    <div class='flex w-20 h-8 space-x-2 p-2 pt-3 justify-center border border-1 border-purple-600 bg-purple-50 rounded-tr-xl rounded-br-xl rounded-bl-xl '>
        <span class='sr-only'>Loading...</span>
        <div class='h-2 w-2 bg-purple-400 rounded-full animate-bounce [animation-delay:-0.3s]'></div>
        <div class='h-2 w-2 bg-purple-400 rounded-full animate-bounce [animation-delay:-0.15s]'></div>
        <div class='h-2 w-2 bg-purple-400 rounded-full animate-bounce'></div>
    </div>
</div>

<div id="chat_message_template_menulink" class="hidden">
    <a href="{{link_url}}" class="block mt-4 w-fit  py-2 px-3 rounded-xl text-xs bg-yellow-100 text-slate-800 border border-1 border-yellow-400 hover:bg-yellow-200">{{link_text}}</a>
</div>


<audio id="acc_player" xxcontrols></audio>

<script>

function present_focus_options(){
    
    if(!presented_focus_options){
        presented_focus_options = true;
        var message = "Here are some suggestions to help you, or you can start asking whatever questions you want about numbers and their meaning in your life.";
        message += "<br />";
        <?php foreach($prompts_suggestions[$_SESSION['collected']['focusarea']] as $prmsg){ ?>
        message += "<div class='ai_chat_prompt'><?php echo $prmsg; ?></div>";
        <?php } ?>
        $('#ai_chat_response').append('<div>'+create_chat_message('user_focus_options',message, 'user', 'Suggestions')+'</div>'); //tool_response
    }else{
        console.log('already presented focus options');
    }
}

</script>    

<script src="<?php echo $gc['path']['web_root']; ?>/core/aichat/aichat.js?<?php echo time(); ?>"></script>
<script src="<?php echo $gc['path']['web_root']; ?>/core/aichat/custom.aichat.js?<?php echo time(); ?>"></script>

<script>
$(document).ready(function() {    
    
    var focusarea = '.';
    <?php if(collected_focusarea_prompt()){ ?>
        var focusarea = ', Focus on <?php echo collected_focusarea_prompt(); ?>';
    <?php } ?>    


    <?php 
    $meanings = collected_lifepathnumber_meanings_arr();
    ?>

    //last param false, makes this message show, otherwise it will be hidden
    load_thread_history_or_intro("<?php 
    echo "My name is ".collected_name_prompt()." , born on ".
    collected_birthday_prompt()." in ".collected_birthplace_prompt().
    " , my life path number is ".collected_lifepathnumber_prompt().
    ", representing ".$meanings['title']." (".$meanings['values']."). Assume the current year is ".
    date("Y").". Do me a numerology reading based on this info, discuss your challenges ".$meanings['challenges']; ?>"+focusarea, false); //
});
</script>


</div>


<?php 
require_once $gc['path']['root_partials'].'/footer.php';
require_once($gc['path']['root'] . '/output.php');
?>