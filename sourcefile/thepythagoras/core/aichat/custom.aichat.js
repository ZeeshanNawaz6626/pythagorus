var default_initial_message = "Tell me my numerology reading";


function command_redirect_human(){
    alert('redirecting to a human operator');
    return 'Redirecting you to a human operator...';
}


function first_intro_message(initial_message, silent){
    send_user_message(initial_message, silent);
    
}

//ai might reply with a [command_...] message
function detect_command_response(reply){
    var command_reply = false;
    if(reply.includes('[command_')){
        
        if(reply.includes('[command_redirect_human]')){
            command_reply = command_redirect_human();
        
        }else if(reply.includes('[command_premium]')){
            command_reply = "To continue please purchase more credits and unlock the more powerful version of Pythagoras AI Premium <br>"+create_chat_menulink("Get more credits", "Javascript:window.location.replace('/get_premium');")+"<br>"+create_chat_menulink("Reset", "Javascript:window.location.replace('/?reset=1');");
        
        }else if(reply.includes('[command_waiting]')){
            command_reply = '[waiting]';
        }else{
            alert('chat command unrecognized');
        }
    }
    return command_reply;
}

function typethis(selector){
    
    var $el = $(selector),  
    html = $el.html(),
    txt = $el.text(),
    txtLen = html.length,
    timeOut,
    char = 0;
    //alert('trying to animate: '+html);
    

    //if($el.hasClass("typing")){
        $el.text('|');

        (function typeIt() {   
            var humanize = Math.round(Math.random() * (100 - 20)) + 20;
            timeOut = setTimeout(function() {
                char++;
                var type = html.substring(0, char);
                $el.html(type + '|');
                typeIt();

                if (char == txtLen) {
                    $el.html($el.html().slice(0, -1)); // remove the '|'
                    clearTimeout(timeOut);
                    //$el.removeClass('typing');
                }

            }, humanize);
        }());
    //}
}

$(document).ready(function() {

    //typethis('.response_msg'); //#ai_chat_response .response_msg
});