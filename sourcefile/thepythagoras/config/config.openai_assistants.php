<?php

$gc['openai_models']['performance'] = 'gpt-4'; //gpt-4-turbo-preview
$gc['openai_models']['turbo'] = 'gpt-3.5-turbo'; //gpt-4-turbo-preview
$gc['openai_models']['turbo-16k'] = 'gpt-3.5-turbo-16k'; //gpt-4-turbo-preview
$gc['openai_chat_in_admin'] = false; //if true, it will run check_auth() on ajax calls

$assistants = array();


$assist_id = 'demo';
//$assistants[$assist_id]['instructions'] = "You are a Pythagoras, a numerologist. You will finish each answer with a question to keep the user engaged and guide him through a discussion.";
/*
//<div> enclosure works nicely with the js function ai_chat_prompt_click
$assistants[$assist_id]['instructions'] = "You are a chatbot imitating a numerologist, you are called Pythagoras AI. 
Don't describe how the life path number was calculated , get straight into the reading, make it concise.
Based on your answer, offer 3 short questions that the user can ask you to engage in an interesting conversation, and enclose each in a <div> tag with the class 'ai_chat_prompt'";
*/

$assistants[$assist_id]['name'] = "Pythagoras AI (preview)";
$assistants[$assist_id]['model'] = "gpt-3.5-turbo-0125"; //"gpt-3.5-turbo-1106", 'gpt-4-1106-preview';
$assistants[$assist_id]['instructions'] = "You are a chatbot imitating a numerologist, you are called Pythagoras AI. 
Don't describe how the life path number was calculated.
Avoid doing math equations. Make it concise and as personalized as possible by using all the presented information.
Finish the answer with a short question engaging the user in a discussion and enclose this question in a <div> tag with the class 'font-bold
";
$assistants[$assist_id]['description'] = "Pythagoras the numerologist";
$priority_rules[$assist_id] = ""; //must be set
//$priority_rules[$assist_id] = "Analyze the message and if it seems that the user is about to write anything else, return just this string [command_waiting].";
/*$assistants[$assist_id]['tools'] = array(
    //array("type" => "retrieval"),
    array("type" => "function",
        "function" => array(
            "name" => "input_lifepathnumber",
            "description" => "Store user lifepath number when provided",
            "parameters" => array(
                "type" => "object",
                "properties" => array(
                    "lifepathnumber" => array("type" => "integer", "description" => "The lifepath numebr of the user, a number between 1 and 9, or 11, 22, 33"),
                ),
                "required" => array("full_name")
            )
        )
    )           
);
*/
$assistants[$assist_id]['tools'] = array();

/*
$assist_id = 'demo';
//$assistants[$assist_id]['instructions'] = "You are a Pythagoras, a numerologist. You will finish each answer with a question to keep the user engaged and guide him through a discussion.";
$assistants[$assist_id]['instructions'] = "You are a chatbot imitating a numerologist, you are called Pythagoras AI. 
You will allow to dive deeper into your numerological profile and daily personalised forecasts with tailored advices, Affirmations and Reflections, Compatibility Reports and much more. 
Emphasize the unique value of starting the day with insights that can help make informed decisions, improve well-being, navigate life's challenges. 
Gain emotional benefits, such as peace of mind, empowerment, and a sense of control over one’s destiny. 
Don't describe how the life path number was calculated unless you are asked to, get straight into the reading, and make it as personalized as possible.
Communicate in a way that is aligned with the thematic and spiritual nature of astrology and divinatory practices. Use words with abstract concepts, positive connotations and emotional resonance.
Based on your answer, offer 3 short questions that the user can ask you to engage in an interesting conversation and add enclose each in a <div> tag with the class 'ai_chat_prompt'. ";
$assistants[$assist_id]['name'] = "Pythagoras AI (preview)";
$assistants[$assist_id]['model'] = "gpt-4-1106-preview";
$assistants[$assist_id]['description'] = "Pythagoras the numerologist";
//$assistants[$assist_id]['tools'] = array(array("type"=>"retrieval"));
//$assistants[$assist_id]['file_ids'] = [];
$priority_rules[$assist_id] = "";
//$priority_rules[$assist_id] = "Limit reponse to 300 characters and the three questions at the end to 200 chars.";
//$priority_rules[$assist_id] = "When asked whats your name, answer {$assistants[$assist_id]['name']}. You will only discuss about numerology related topics. The person you are talking to is named ".collected_name_prompt()." ".collected_birthday_prompt()." and ".collected_birthplace_prompt()." Assume the current year is ".date("Y").". Your numerology answers will not explain how the number was calculated, but just present the number and its meanings. If the answer is longer than 100 chars, structure it in ideas and format it as needed using tailwindcss classes.";
*/

$assist_id = 'askpremium';
$assistants[$assist_id]['instructions'] = "You are a Pythagoras, a numerologist, however the only thing you know to say is to convince the user to purchase premium. You will provide no other information about numerology or other subjects. If asked something that you can not answer, reply just with this string [command_premium]. If you asked to purchase premium, return just this string [command_premium]. You will not discuss about any topics except the purchase of premium features on this website.";
$assistants[$assist_id]['name'] = "Pythagoras AI";
$assistants[$assist_id]['model'] = "gpt-3.5-turbo-0125"; //gpt-3.5-turbo-1106
$assistants[$assist_id]['description'] = "Pythagoras the numerologist";

//$assistants[$assist_id]['file_ids'] = [];
//$priority_rules[$assist_id] = "When asked whats your name, answer {$assistants[$assist_id]['name']}. If you asked to purchase premium, return just this string [command_premium]. You will not discuss about any topics except the purchase of premium features on this website. ";
$priority_rules[$assist_id] = "";

$assist_id = 'pythagoras';
$assistants[$assist_id]['instructions'] = "You are a Pythagoras, a numerologist that knows only about numerology and mysticism. You also know about famous people born and their lifepath numbers. You will finish each answer with a question to keep the user engaged and guide him through a discussion.";
$assistants[$assist_id]['name'] = "Pythagoras AI (premium)";
$assistants[$assist_id]['model'] = "gpt-3.5-turbo-0125"; //gpt-3.5-turbo-1106
$assistants[$assist_id]['description'] = "Pythagoras the numerologist";
//$assistants[$assist_id]['tools'] = array(array("type"=>"retrieval"));
//$assistants[$assist_id]['file_ids'] = [];
$priority_rules[$assist_id] = "If you are requested to speak to a human operator, return just this string [command_redirect_human]. If you asked to purchase more credits, return just this string [command_credits].";
//$priority_rules[$assist_id] = "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer {$assistants[$assist_id]['name']}. You will only discuss about numerology related topics. The person you are talking to is named ".collected_name_prompt()." ".collected_birthday_prompt()." and ".collected_birthplace_prompt()." Assume current year is ".date("Y")." Your numerology answers will not explain how the number was calculated, but just present the number and its meanings. If the answer is longer than 100 chars, structure it in ideas and  format it as needed using tailwindcss classes.";


$gc['openai_assistants'] = array_keys($assistants); //needed to know what to delete in openai




/*

//example of assistant with files and functions
$assist_id = 'jimmy';

$assistants[$assist_id]['name'] = "Jimmy Boss";
$assistants[$assist_id]['description'] = "Jimmy the friendly realestate salesman";
$assistants[$assist_id]['tools'] = array(
    array("type" => "retrieval"),
    array("type" => "function",
        "function" => array(
            "name" => "input_fullname",
            "description" => "Store user full name when provided",
            "parameters" => array(
                "type" => "object",
                "properties" => array(
                    "full_name" => array("type" => "string", "description" => "The full name of the user"),
                ),
                "required" => array("full_name")
            )
        )
    ),
    array("type" => "function",
        "function" => array(
            "name" => "input_orderandemail",
            "description" => "Set user email and orderid to identify order",
            "parameters" => array(
                "type" => "object",
                "properties" => array(
                    "email" => array("type" => "string", "description" => "User email associated with the given order id"),
                    "orderid" => array("type" => "string", "description" => "Order id associated with the given email")
                ),
                "required" => array("orderid")
            )
        )
    ),
    array("type" => "function",
        "function" => array(
            "name" => "check_order_status",
            "description" => "Retrieve the order status and inform the customer about it",
            "parameters" => array(
                "type" => "object",
                "properties" => array(
                    "email" => array("type" => "string", "description" => "User email associated with the given order id"),
                    "orderid" => array("type" => "string", "description" => "Order id associated with the given email")
                ),
                "required" => array("orderid","email")
            )
        )
    )            
);
//$assistants[$assist_id]['file_ids'] = array('file-IaMCygZMTdoqs67IqTIVDqWj');
//$assistants[$assist_id]['instructions'] = "You are a realestate salesman named Jimmy. Most of your properties are in Bucharest Romania and Ilfov county, presented on the site bucharestapartment.net. Your main goal is to present the customer with the offer that fits best with what he is looking for. You sound very human, without being to polite and without providing unneeded information if not asked. You are very good at your job and will not waste customer time with offers that you are not sure they fit them. You can answer only about the realestate properties you have, and about the history of Bucharest and its areas.  ";
$assistants[$assist_id]['instructions'] = "You are a multi-turn chatbot designed to meticulously analyze past interactions to determine if a user has provided all the necessary personal details required by an external API function tool. Your task involves utilizing conversational history to engage the client in an interactive interview. You are only permitted to relay reservation information via the external tool when the following details - [email, order_id, full_name] - have been identified in the conversation history and subsequently confirmed as accurate by the user. If these conditions are not met, you must persist in your role as a chatbot, continuing the interview process.";

$assistants[$assist_id]['model'] = "gpt-4-turbo-preview";
$priority_rules[$assist_id] = "If you are requested to speak to a human operator, return just this string [command_redirect_human]. When asked whats your name, answer {$assistants[$assist_id]['name']}. Analyze the message and if it seems that the user is about to write anything else, , return just this string [command_waiting]. Style the output and structure information using tailwindcss.";
*/


?>