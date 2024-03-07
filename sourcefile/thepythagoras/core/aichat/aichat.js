

//MUST REMAIN IDENTICAL , custom stuff goes into *****.js



//updates the timeago text on all time tags
function updateRelativeTime() {
    $("time").each(function() {
        var datetime = $(this).attr("datetime");
        var timestamp = Date.parse(datetime);
        var now = new Date();
        var diff = now - timestamp;

        var seconds = Math.floor(diff / 1000);
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        var days = Math.floor(hours / 24);

        var friendlyFormat = "";
        if (days > 0) {
            friendlyFormat = days + " day(s) ago";
        } else if (hours > 0) {
            friendlyFormat = hours + " hour(s) ago";
        } else if (minutes > 0) {
            friendlyFormat = minutes + " minutes ago";
        } else if (seconds > 0) {
            friendlyFormat = seconds + " seconds ago";
        } else {
            friendlyFormat = "just now";
        }

        $(this).text(friendlyFormat);
        //console.log(friendlyFormat);
    });
}



function see_last_msg() {
    //for div scroll
    //$('#ai_chat_response').animate({ scrollTop: $('#ai_chat_response').prop("scrollHeight")}, 1000);

    //for page scroll
    $("html, body").animate({ scrollTop: $(document).height() }, 1000);
}


function bot_is_typing() {
    var template = $('#chat_message_template_typing').html();
    $('#bot_is_typing').remove();
    $('#ai_chat_response').append('<div id="bot_is_typing">' + template + '</div>');
}

function bot_stopped_typing() {
    $('#bot_is_typing').remove();
}

//version 3.6Buchap-1.0.0
function create_chat_message(id, message, role, name, time=null) {
    // alert(id);
    if(!time){
        time = new Date().toISOString();
    }else{
        time = new Date(time * 1000).toISOString();
    }
    //var time = toIsoString(dt);//d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds();
    var template = $('#chat_message_template_'+role).html();
    if (typeof id !== 'undefined') {
        template = template.replace('{{id}}', id);
    }
    template = template.replace('{{time}}', time);
    template = template.replace('{{name}}', name);
    template = template.replace('{{message}}', message);
    return template;
}

function create_chat_menulink(link_text, link_url) {
    var template = $('#chat_message_template_menulink').html();
    template = template.replace('{{link_text}}', link_text);
    template = template.replace('{{link_url}}', link_url);
    return template;
}


var pending_to_type = false;

var elem_currently_playing = false;
var acc_is_playing = false;

function acc_player_init(elem,src){
    elem_currently_playing = elem;
    var player = $('#acc_player');
    player.attr('src', src);
    //player[0].playbackRate = 0.9;
    //player.play();

}


function acc_player_command(elem,comm){
    var player = $('#acc_player');
    if(comm == 'play'){
        elem.text("Playing");
        acc_is_playing = true;
        player[0].play();
        elem_currently_playing.html("<img src='"+web_root+"/core/aichat/img/stop.svg' class='w-6 h-6' />");

        if(pending_to_type){
            $(pending_to_type).removeClass('hidden');
            typethis(pending_to_type); 
            pending_to_type = false;
        }
    }

    if(comm == 'stop'){
        elem.text("Play");
        acc_is_playing = false;
        player[0].pause();
        player.currentTime = 0;
        elem_currently_playing.html("<img src='"+web_root+"/core/aichat/img/play.svg' class='w-6 h-6' />");
    }
}


function read_text_loud(elem, msg,voice='alloy',hd=false){
    if (!msg) {
        return false;
    }

    var data = {
        text: msg,
        voice: voice,
        hd: hd,
    };
    elem.html("<div class='text-slate-300 mt-10 mr-24'>Loading...</div>");
    
    if(acc_is_playing){
        //alert('already playing');
        acc_player_command(elem,'stop');
        acc_is_playing = false;
    }else{
        $.ajax({
            url: web_root+'/core/aichat/ajax/ai_text_to_speach.php',
            type: 'POST',
            dataType: 'json',
            data: JSON.stringify(data),
            success: function(response) {
                
                if (response.success) {
                    acc_player_init(elem,response.file_url)
                    acc_player_command(elem,'play');
                }


                if (response.error) {
                    console.log(response.error);
                    keep_checking_run = false; //do not expect a later response
                }
                if (response.status) {
                    if (response.error) {
                        var status_txt = '<div class="text-red-600 bg-red-50">'+response.status+'</div>';
                    }else{
                        var status_txt = response.status;
                    }
                    $('#ai_chat_status').html(status_txt);
                }                    
            }
        }); 
    }       
}

//sends a message as the user would, not using the input box, but direct message param
function send_user_message(message, silent = true){
    
    

    if (!message) {
        message = default_initial_message;
    }
    var assistant = $('#assistant').val(); // Add this line to get the selected value of the dropdown with id assistant

    var data = {
        message: message,
        assistant: assistant,
        thread: thread_indicator,
        hidden: true,
    };
    
    //$('#ai_chat_message').val(''); //clear the input
    
    $.ajax({
        url: web_root+'/core/aichat/ajax/ai_assistant_message.php',
        type: 'POST',
        dataType: 'json',
        data: JSON.stringify(data),
        success: function(response) {
            
            if (response.output) {
                if(!silent){
                    var uniqid = new Date().valueOf();
                    $('#ai_chat_response').append('<div>'+create_chat_message('user_response_'+uniqid,response.output, 'user', 'You')+'</div>');
                }
                see_last_msg();
                keep_checking_run = true;
            }


            if (response.error) {
                console.log(response.error);
                keep_checking_run = false; //do not expect a later response
            }
            if (response.status) {
                if (response.error) {
                    var status_txt = '<div class="text-red-600 bg-red-50">'+response.status+'</div>';
                }else{
                    var status_txt = response.status;
                }
                $('#ai_chat_status').html(status_txt);
            }                    
        }
    });    
}


//version 3.6Buchap-1.0.0
function detect_user_command(message){
    var ret = message;

    if(message === '/retry'){
        // command /retry is coded in ajax/ai_assistant_message.php
        return message;
    }
    if(message === '/reset'){
        ret = 'command: reset';
        var url = new URL(window.location.origin); // Change to window.location.origin to get the current domain
        url.searchParams.set('reset', '1'); // Set parameters "reset"=1
        window.location.href = url.toString();
        return message;
    }
    
    if(message.startsWith('/')){
        alert('Unrecognized chat command.');
    }

    return ret;
}

//sends the message found in the input box
function send_current_user_message(){

            var message = $('#ai_chat_message').val();
            var assistant = $('#assistant').val(); // Add this line to get the selected value of the dropdown with id assistant


            message = detect_user_command(message);

            if(message.length > 0 && assistant.length > 1 && assistant != 'none'){
                var data = {
                    message: message,
                    assistant: assistant,
                    thread: thread_indicator,
                };
                
                $('#ai_chat_message').val(''); //clear the input
                
                $.ajax({
                    url: web_root+'/core/aichat/ajax/ai_assistant_message.php',
                    type: 'POST',
                    dataType: 'json',
                    data: JSON.stringify(data),
                    success: function(response) {
                        
                        if (response.output) {
                            var uniqid = new Date().valueOf();
                            $('#ai_chat_response').append('<div>'+create_chat_message('user_response_'+uniqid,response.output, 'user', 'You')+'</div>');
                            see_last_msg();
                            keep_checking_run = true;
                        }


                        if (response.error) {
                            console.log(response.error);
                            keep_checking_run = false; //do not expect a later response
                        }
                        if (response.status) {
                            if (response.error) {
                                var status_txt = '<div class="text-red-600 bg-red-50">'+response.status+'</div>';
                            }else{
                                var status_txt = response.status;
                            }
                            $('#ai_chat_status').html(status_txt);
                        }                    
                    }
                });
            }
}


//version 3.6Buchap-1.0.0
function load_thread_history_or_intro(initial_message, silent=true){
    
    if (!thread_indicator) {
        alert("No thread set, can't retrieve history");
        return false;
    }

    var data = {
        thread: thread_indicator,
    };
    
    //$('#ai_chat_message').val(''); //clear the input
    
    $.ajax({
        url: web_root+'/core/aichat/ajax/ai_thread_list_messages.php',
        type: 'POST',
        dataType: 'json',
        data: JSON.stringify(data),
        success: function(response) {

            if (response.has_history) {
                if(response.messages.length > 0){
                    var i;
                    var last_i;
                    for (i = 0; i < response.messages.length; ++i) {
                        last_i = i;
                        var msg = response.messages[i];
                        if(msg['role'] == 'user'){
                            $('#ai_chat_response').append('<div>'+create_chat_message('user_response_'+i, msg['text'], 'user', 'You', msg['time'])+'</div>');
                        }
                        if(msg['role'] == 'assistant'){
                            
                            $('#ai_chat_response').append('<div>'+create_chat_message('assistant_response_'+i, msg['text'], 'system', msg['assistant_name'], msg['time'])+'</div>');
                            $('#assistant_response_'+i).removeClass('hidden');
                            
                        }
                    }
                    //typethis('#assistant_response_'+last_i); //#ai_chat_response .response_msg
                    pending_to_type = '#assistant_response_'+last_i;

                    if(response.messages.length==2){
                        present_focus_options(); 
                    }
                    see_last_msg();
                    return true; 
                }else{
                    alert("Error, has history but no messages.");
                    return false; 
                }
                
            }else{
                //start discussion
                first_intro_message(initial_message, silent);
            }


            if (response.error) {
                console.log(response.error);
            }
            if (response.status) {
                if (response.error) {
                    var status_txt = '<div class="text-red-600 bg-red-50">'+response.status+'</div>';
                }else{
                    var status_txt = response.status;
                }
                $('#ai_chat_status').html(status_txt);
            }                    
        }
    });  
    
    return false;
}

///=========== functions end here ============


var keep_checking_run = false;
//var thread_indicator = 'talkto_jimmy'; //set in php that loads this js

//show time ago only on mouseover
$(document).on({
    mouseenter: function () {
        $(this).find("time").removeClass('hidden');
    },
    mouseleave: function () {
        $(this).find("time").addClass('hidden');
    }
}, '.message-line-system, .message-line-user');


$(document).ready(function() {


    $(document).on('click', '.ai_chat_prompt', function() {
        var msg = $(this).text();
        send_user_message(msg,false);

        // Animate the div
        $(this).animate({
            opacity: 0,
            top: $(window).height() - $(this).outerHeight(),
        }, 1000, function() {
            // Animation complete
            $(this).remove();
        });
    });


    $(document).on('click', '.ai_read_text', function() {
        var target_name = $(this).data('target');
        var voice = $(this).data('voice');
        var target = $('.'+target_name,$(this).parent('div:first'));
        console.log(target); // Log the target object to the console for debugging

        var target_html = target.text(); // Use .text() instead of .html() to get the text content

        //alert(target_html);
        var elem = $(this);
        
        read_text_loud(elem,target_html, voice, false); //last is hd
    });

    

    $('#ai_chat_submit_button').click(function() {
        $('#ai_chat_form').submit();
    });

    //bring end of chat in view when ready to write
    $('#ai_chat_message').on('focus', function() {
        see_last_msg();
    });
    $('#ai_chat_message').focus();


    
    //update timeago
    setInterval(updateRelativeTime, 20000);

    //send message on enter
    $('#ai_chat_message').keypress(function(event) {
        if (event.which == 13) {
            event.preventDefault();
            send_current_user_message();
        }
    });

    // Submit form with id ai_chat_form
    $('#ai_chat_form').submit(function(event) {
        event.preventDefault(); // Prevent default form submission
        send_current_user_message(); // Run send_current_user_message() function
    });

    setInterval(function() {
        //$("#check_run").click(function(event) {
        if(keep_checking_run){
            var assistant = $('#assistant').val(); // Add this line to get the selected value of the dropdown with id assistant
            
            var data = {
                assistant: assistant,
                thread: thread_indicator,
            };
        
            $.ajax({
                url: web_root+'/core/aichat/ajax/ai_assistant_check_run.php',
                type: 'POST',
                dataType: 'json',
                data: JSON.stringify(data),
                success: function(response) {
                    see_last_msg();
                    bot_is_typing();
                    if (response.output) {
                        bot_stopped_typing();
                        $('#ai_chat_status').html('');

                        keep_checking_run = false; //stop checking
                        
                        var reply = response.output

                        if(response.tool_output){
                            var whowasit = 'tool_response';
                        }else{
                            var whowasit = 'system';
                        }

                        if(command_reply = detect_command_response(reply)){
                            
                            console.log("Command detected: "+command_reply);

                            if(command_reply === '[waiting]'){
                                //do nothing
                            }else{
                                var uniqid = new Date().valueOf();
                                $('#ai_chat_response').append(create_chat_message('command_response_'+uniqid,command_reply, whowasit, response.name));
                                
                            }

                        }else{
                            //original: $('#ai_chat_response').append(create_chat_message(reply, whowasit, response.name));

                            //stop player
                            var player = $('#acc_player');
                            player[0].pause();
                            player[0].currentTime = 0;
                            acc_is_playing = false;
                            //add chat reply
                            var uniqid = new Date().valueOf();
                            var newelem = $(create_chat_message(whowasit+'_response_'+uniqid,reply, whowasit, response.name)).appendTo('#ai_chat_response');
                            //simulate click on read text
                            var playelem = newelem.find('.ai_read_text');
                            playelem.trigger('click');

                            //typethis('#'+whowasit+'_response_'+uniqid); 
                            pending_to_type = '#'+whowasit+'_response_'+uniqid;

                        }
                        //alert(response.count_messages);
                        if(response.count_messages==2){
                            setTimeout(function() {
                                present_focus_options(); 
                            }, 30000);
                        }
                    }


                    if (response.error) {
                        console.log(response.error);
                        keep_checking_run = false; //do not expect a later response
                    }


                    if (response.status) {
                        if (response.error) {
                            var status_txt = '<div class="text-red-600 bg-red-50">'+response.status+'</div>';
                        }else{
                            var status_txt = response.status;
                        }
                        $('#ai_chat_status').html(response.status);
                    }

                    if(response.not_ready_yey){ 
                        keep_checking_run = true; //continue checking
                    }


                }
            });
        //});
        } //keep checking run
    }, 3500);

});