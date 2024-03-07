<?php
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

header('Content-Type: text/html; charset=utf-8');

$gc = array();

$gc['site_name'] = "The Pythagoras Oracle";

$gc['path']['root'] = '/home/thepythagoras/public_html/v2/'; // /cron scripts need to have this path too for config.php
$gc['path']['root_partials'] = $gc['path']['root'].'/pages/partials'; // /cron scripts need to have this path too for config.php
$gc['path']['web_root'] = "https://thepythagoras.com/v2/";

$gc['contact_email'] = 'oracle@thepythagoras.com'; //public

$gc['email_admin'] = 'max@lumnar.com'; //pt notificari si debug, not public

//openai settings (some might still be present in chat engine)
$gc['api']['openai_api_key'] = "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";
$gc['api']['openai_default_brain'] = 'gpt-4'; //3.5-turbo
//$gc['api']['assistants_thread'] = createAssistantsSession('oracle'); //unique identifier, the rest is session assigned

//MUST CHANGE THIS
$gc['api']['google']['clientID'] = "283912281743-sf1mge1p8b7amumr48nubqeh1lkeoa8c.apps.googleusercontent.com"; //for login button
//$gc['api']['google']['clientSecret'] = "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"; //not used, callback verified by custom code

//https://melapress.com/support/kb/captcha-4wp-get-google-recaptcha-keys/
//https://console.cloud.google.com/security/recaptcha?authuser=0&project=browsermmo-1673967966607
//https://console.cloud.google.com/security/recaptcha?project=browsermmo-1673967966607&authuser=0
//https://www.2captcha.com/p/recaptcha_v3


$gc['api']['google']['recaptchav3_id'] = "6Le4b04pAAAAAIh1oY9dcPXymE95aasRycw9KXYX"; //site key?
$gc['api']['google']['recaptchav3_secret'] = '6Le4b04pAAAAAB0eFV9OYfsOijUNlPZo_BpnQEIh'; //secret key

//https://theonetechnologies.com/blog/post/how-to-get-facebook-application-id-and-secret-key
$gc['api']['facebook']['api_id'] = '1294055474598041';
$gc['api']['facebook']['app_secret'] = '8c6e5780699cbf7c9024b0bb3bf1cb8f';
$gc['api']['facebook']['default_graph_version'] = 'v2.5';

$gc['api']['mailersend_max_sent'] = 3000; //max emails sent per month, after it reverts to php
$gc['just_test_mailto'] = false; //disable actual email sending

$gc['master_password_hash'] = 'must be sha1 of password';
$gc['hash_password'] = true;

$gc['cookie_name'] = "pythagora";
$gc['cookie_lifespan'] = 60 * 60 * 24 * 100;//in seconds

$gc['free_responses']  = 3; //how many reponses to give before it asks for login

//==================================================================================================

$gc['db']['name'] = 'thepythagoras_oracle';
$gc['db']['server'] = 'localhost';
$gc['db']['user'] = 'thepythagoras_oracle';
$gc['db']['password'] = 'inunb84^2jhb!jidiubSSffqQW'; //'8I9ij^h2o8UYH.LJ11';
$gc['db']['class'] = 'class.mariadb.php'; //class.mysqldb.php

require_once($gc['path']['root'] . '/core/' . $gc['db']['class']);
$db = new mysqldb($gc['db']['name'], $gc['db']['server'], $gc['db']['user'], $gc['db']['password']);

if(!$db->conn) {  
    die("Error: Unable to connect to db."); 
}

//==================================================================================================

require_once($gc['path']['root'] . '/core/func.core.php');
require_once($gc['path']['root'] . '/core/func.openai.php');




if(isset($_SESSION['catch_ip']) && isset($_SESSION['user']['id']) && isset($_SERVER['REMOTE_ADDR'])){
    $db->query("update users set ip='{$_SERVER['REMOTE_ADDR']}' where id='{$_SESSION['user']['id']}' limit 1");
    unset($_SESSION['catch_ip']);
}


is_remembered_auth();


$prompts_focus = array(
    "0" => "General discussion",
    "1" => "Personal Growth and Spirituality",
    "2" => "Career and Finances",
    "3" => "Health and Well-being",
    "4" => "Relationships and Social Life",
);

$prompts_suggestions = array(
    "0" => array( //General Engaging Questions:
        "How Can Numerology Improve My Life?",
        "How Will My Birth Date Influence My Future?",
        "How Can Numerology Guide My Decisions?"),
    "1" => array( //For Personal Growth and Spirituality:
        "What Talents are Hidden in My Numbers?",
        "How Does My Path Influence Spirituality?",
        "What Growth Awaits Me?"),
    "2" => array( //For Career and Finances:
        "Which Career Aligns With My Numbers?",
        "What Financial Insights Awaits Me?",
        "Am I Ready to Discover Opportunities?"),
    "3" => array( //For Health and Well-being:
        "What Do Your Numbers Say About My Health?",
        "How Can Numerology Enhance My Well-being?",
        "Best Times for Self-Care?"),
    "4" => array( //For Relationships and Social Life:
        "What’s in Store for My Relationships?",
        "How to Deepen My Connections?",
        "Insights for Love and Family?"),
    
);


$number_meanings = array(
    "1" => array(
        "title" => "The Leader",
        "values" => "Leadership, Independence",
        "challenges" => "To quell overthinking and find your inner confidence"
    ),
    "2" => array(
        "title" => "The Peacemaker",
        "values" => "Compassion, Determination",
        "challenges" => "To open your heart and learn to trust others"
    ),
    "3" => array(
        "title" => "The Creative",
        "values" => "Magnetism, extroversion, communication skills",
        "challenges" => "To relate to others on a deeper level"
    ),
    "4" => array(
        "title" => "The Manager",
        "values" => "Stability, logic, loyalty",
        "challenges" => "To open your mind and be more flexible with change"
    ),
    "5" => array(
        "title" => "The Adventurer",
        "values" => "Free-spirited, adaptability",
        "challenges" => "To overcome any selfishness and find a life's purpose"
    ),
    "6" => array(
        "title" => "The Nurturer",
        "values" => "Protective, selflessness",
        "challenges" => "To find a level of comfort for yourself, not just others"
    ),
    "7" => array(
        "title" => "The Seeker",
        "values" => "Curiosity, thoroughness",
        "challenges" => "To seek more meaningful relationships without overanalyzing"
    ),
    "8" => array(
        "title" => "The Powerhouse",
        "values" => "Realism, unity",
        "challenges" => "To accept the things you cannot control"
    ),
    "9" => array(
        "title" => "The Humanitarian",
        "values" => "Integrity, acceptance",
        "challenges" => " To enjoy a life that doesn't always cater to others"
    ),
    "11" => array(
        "title" => "The Inspired Healer",
        "values" => "Spirituality, balance, positivity",
        "challenges" => "To find the strength to finish what you start"
    ),
    "22" => array(
        "title" => "The Master Teacher",
        "values" => "Organization, creativity, practicality",
        "challenges" => "To overcome your fear of failure"
    ),
    "33" => array(
        "title" => "The Illuminated Guide",
        "values" => "Compassion, creativity, responsibility",
        "challenges" => "To use creative talents effectively to help others"
    )
);

?>