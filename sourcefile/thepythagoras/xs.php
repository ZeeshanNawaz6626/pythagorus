<?php

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');
ini_set('log_errors', '0');
error_reporting(E_ALL ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL);

$sessionCookieExpireTime=0; //72*60*60;
ini_set('session.gc_maxlifetime',$sessionCookieExpireTime);
ini_set('session.gc_probability',1);
ini_set('session.gc_divisor',10000);
ini_set('session.cache_expire',$sessionCookieExpireTime);
ini_set('session.cookie_lifetime',$sessionCookieExpireTime);
//session_start();
session_set_cookie_params($sessionCookieExpireTime);

/*
require_once('config.php'); 

if(!has_priv("admin")) {
    die('admin only');
}
*/

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

/* ==== devsearch v2.0 tool by Mxl ====
 * this is not a hack tool. talk to the developer for details. 
 * Secure but don't remove
 * use for virus search periodically via ms.php?runspecial=yes&api=yes  //it will run $special_command
 */

$version = '2.7 - recursive permissions, no confirm, sigupdate, masswhitelist, preview src, strlen, preset extentions  - Macho 26.jan.21';

//ip access for regular use
$allow =  array('95.76.31.203');

//https://...../xs.php?runspecial=yes&api=yes

$apiuser ='pyth';
$apipass = 'rfki58u7^ik3nioHHGui230927';


$alternate_auth = true; //if set, it will atepmt to configure basic auth for cgi php differently.


/*
//if alternate_auth, add this in .htaccess
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
</IfModule>
*/
//var_dump($_SERVER);
//die();
///------------




$excludes_list = 'blog|cache|oldstuff|_template_cache|themdlogs|userfiles|diverse';
$extentions_list = 'php|phtml|txt|html|htm|tpl|xml|csv|js|css|less|htaccess|ini|sh|py|inc|json';

//patterns for multisearch
$special_command = "cmd❚suspicious-code"; //ms.php?runspecial=yes&api=yes
//passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink
//"system",
$patterns['cmd❚suspicious-code'] = array('){global','installatron.com','PHPJiaMi','0);@',');$','\';$','|php5|suspected','$O_','$O0',"Ž","Ì","À","¦","Ÿ","‡","±",'ð','0[','1[','3[','4[','5[','6[','7[','8[','9[',"'}'.","passthru","eval(eval","strrev(gzinflate","hex2bin","fsockopen","base64_decode","shell_exec","eval(","error_reporting(0);","anonymousfox","%75%",'2\x6','\x45','eval\(','create_function');
$patterns['cmd❚filewrite-code'] = array("unlink","readfile","file_get_contents","fwrite");
$patterns['cmd❚email-code'] = array("mail(");
$patterns['cmd❚todo'] = array("@@Todo:","// todo","* Todo:", "#Todo:","<!-- Todo","//Todo:");
$patterns['cmd❚mxl'] = array("mxl ","lumna");
$patterns['cmd❚resume'] = array("@@Resume","Resume@@","//resume ","//resume: ");


//preset extention checks based on command
$f_ext['cmd❚suspicious-code'] = 'php|phtml|txt|tpl|htaccess|ini|sh|py|inc';



//pattern array keys for multisearch
$multisearch = array_keys($patterns);

if(isset($_GET['runspecial']) && $_GET['runspecial']=='yes'){
	$q_get = $special_command;
}else{
	$q_get = (isset($_REQUEST['q'])) ? $_REQUEST['q'] : '' ; 
}


//additional context filter
$c_get = (isset($_REQUEST['c'])) ? $_REQUEST['c'] : false ;
$exclude_context = (isset($_REQUEST['exclude_context'])) ? $_REQUEST['exclude_context'] : false ;

if(isset($q_get) && isset($patterns[$q_get])){
	$q_force = $patterns[$q_get]; //load array, php_grep will handle as array
	if(isset($f_ext[$q_get])){
		$forced_extensions_list = $f_ext[$q_get];
	}
	$sigfile = basename(__FILE__, '.php').'-'.$q_get.'.sig';
	$sigmode = $q_get;
}else{
	$sigfile = basename(__FILE__, '.php').'.sig';
	$sigmode = false;
}

$q = isset($q_force) ? $q_force : ((isset($q_get)) ? $q_get : '' ) ;
$c = $c_get;

/*
if(isset($_GET['googleredir']) && $_GET['googleredir']){
	header("location: https://google.com/search?igu=1&ei=&q={$_GET['googleredir']}");
	die();
}
*/

if(isset($_GET['whitelist']) && $_GET['whitelist']){
	header('Content-Type: application/json');
	$ret = array();
	$sig = $_GET['whitelist'];
	$ret['sig'] = $sig."\n";
	file_put_contents($sigfile, $sig.PHP_EOL , FILE_APPEND | LOCK_EX);
	$ret['status'] = 'signature whitelisted';
	echo json_encode($ret);
	die();
}


if(isset($_GET['whitelistall']) && $_GET['whitelistall']){
	header('Content-Type: application/json');
	$ret = array();
	$sigs = explode("|",$_GET['whitelistall']);
	$ret['sig'] = implode("\n",$sigs);
	file_put_contents($sigfile, $ret['sig'].PHP_EOL , FILE_APPEND | LOCK_EX);
	$ret['status'] = 'all signatures whitelisted';
	echo json_encode($ret);
	die();
	
}



if(isset($_GET['resetsig']) && $_GET['resetsig']=='yes'){
	$fh = fopen( $sigfile , 'w' );
	fclose($fh);	
	//header("Location:".basename(__FILE__, '.php').'.php');
	$q_param = $_GET['q'];
	unset ($_POST);
	header('location: ' . $_SERVER['SCRIPT_NAME']."?q={$q_param}");
}

if(is_file($sigfile)){
	$sigs = file($sigfile);
}else{
	//echo "No sig file";
	$sigs = array();
}
if(count($sigs)>0){
$html_sigstatus =  "Using <b>{$sigmode}</b> sig file with ".count($sigs)." records hidden <a class='resetsigbtn' href='?resetsig=yes&q=".$q_get."'>reset</a>";
}
//echo " <br>NSES BEFORE:".session_id();
//$sessname = session_name("LUMNAR");
session_start();
$sessid = session_id();
//echo  "<br>TEST RESULT:".$_SESSION['test'] .$_SESSION['test2'].$_SESSION['test3'];
//$_SESSION['test'] = "ABC";
//echo " <br>NSES AFTER:".session_id();
//echo " <br>".$sessname;

//include "conf.php";

// Check login


//works, but now uses admin session
/*
if(isset($allow) and count($allow) > 0 and (!isset($_REQUEST['api']) || $_REQUEST['api']!='yes')){
	if(!in_array($_SERVER['REMOTE_ADDR'],$allow ) ){ //,'88.80.16.177'
	//debugLog(0,'General Access','Tried to access devsearch from non dev IP',array("Server"=>$_SERVER,"Request"=>$_REQUEST),'Security Alert');
		echo "<br>{$_SERVER['REMOTE_ADDR']} <br> ";
		//if($_SERVER['REMOTE_ADDR']=='88.80.16.177'){
		//	echo 'OK';
		//}else{
		//	echo "rem addr:".$_SERVER['REMOTE_ADDR'].' check: 88.80.16.177  -- NOT THE SAME';
		//}
		die('Dev ONLY!! ip:'.$_SERVER['REMOTE_ADDR']).' allow: '.print_r($allow,1);
	}
}
*/




//phpinfo();die();
//if(isset($_REQUEST['api']) && $_REQUEST['api'] =='yes'){
if(isset($apipass) && isset($apiuser)){

	if($alternate_auth && isset($_SERVER['HTTP_AUTHORIZATION']) && isset($_SERVER['PHP_AUTH_USER']) &&  isset($_SERVER['PHP_AUTH_PW']) ){
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
	}
	
	if( ( isset($_SERVER['PHP_AUTH_USER'] ) && ( $_SERVER['PHP_AUTH_USER'] == $apiuser ) ) AND ( isset($_SERVER['PHP_AUTH_PW'] ) && ( $_SERVER['PHP_AUTH_PW'] == $apipass )) ) {
			//continue
		} else  {
			//sleep(5);
			header("WWW-Authenticate: " ."Basic realm=\"Leon's Protected Area\"");
			header("HTTP/1.0 401 Unauthorized");
			print("This page is protected by HTTP " . "Authentication.<br>\n");
			die('Restricted Access, try with $alternate_auth');
	}
}


if(
	 (!isset($_GET['login_to_wp']) || !$_GET['login_to_wp']) 
&& (!isset($_GET['set_permissions']) || !$_GET['set_permissions']) 
){


if(isset($_GET['showsource']) && $_GET['showsource']){
	$source = show_source($_GET['showsource'], true);
	print $source;
	die();
}




@ob_start();
@ob_implicit_flush(0);




$path = isset($_POST['path']) ? $_POST['path'] : rtrim(getcwd(), DIRECTORY_SEPARATOR);

$extensions = (isset($forced_extensions_list)) ? $forced_extensions_list : (isset($_REQUEST['extensions']) && ! empty($_REQUEST['extensions']) ? implode('|', $_REQUEST['extensions']) : $extentions_list);
$excludes = isset($_REQUEST['excludes']) && ! empty($_REQUEST['excludes']) ?  $_REQUEST['excludes'] : $excludes_list;

$div_file_index = 0;
$div_line_index = 0;
$anyresult = 0;

function php_grep($q, $c, $exclude_context, $path) {
    global $extensions, $excludes, $sigs, $div_file_index, $div_line_index,$anyresult;
	
	//$c = context
	$c_arr = explode(",",str_replace(", ",",",$c));
	
    if (is_dir($path)) {
        $ret = '';
        $fp = opendir($path);
        while ($f = readdir($fp)) {
            if (preg_match("#^\.+$#", $f) 
				//|| preg_match('/cache$/', $f)  
				//|| preg_match('/blog$/', $f) 
				//|| preg_match('/oldstuff$/', $f) 
				//|| preg_match('/retete$/', $f) 
				//|| preg_match('/taiepretul.ro$/', $f) 
				|| preg_match("/(" . $excludes . ")$/i", $f)
				
				){
                continue; // ignore symbolic links
			}
                $file_full_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $f;
				$div_file_index++;
				
                if (is_dir($file_full_path) && ! preg_match('/^\.svn/i', $f)) {
                    $ret .= php_grep($q, $c, $exclude_context,  $file_full_path);
                } else
                    if (file_exists($file_full_path) && preg_match("/\.(" . $extensions . ")$/i", $f)) {
                        
						$file_contents = @file_get_contents($file_full_path);
						$found = false;
						if(is_array($q)){
							foreach($q as $qv){
								//var_dump($q);
								if(stristr($file_contents, $qv)){
									$found 	= true; 
									continue;
								}
							}
						}else{
							if(stristr($file_contents, $q)){
								$found = true;
							}
						}
						
						//if(stristr($file_contents, $c) || !$c){
						//		$continue_context = true;
						//}
							

						
						//if($continue_context){
							if ($found) {
								//$ret .= "<div class='fullpath' >{$file_full_path}</div>\n";
								$ret2 = ""; //reset
								$siglist = array(); //reset
								$lines = file($file_full_path);
								$atleastonenothidden = false;
								
								foreach ($lines as $line_num => $line) {
									
									$found2 = false;
									if(is_array($q)){
										foreach($q as $qv){
											//var_dump($q);
											if(stristr(strtoupper($line), strtoupper($qv))){
												$found2 = true; 
												continue;
											}
										}
									}else{
										if(stristr(strtoupper($line), strtoupper($q))){
											$found2 = true;
										}
									}
									
									if($exclude_context){ 
									//reversed context, exclude
										if(trim($c)){ //look for context in filename and line
											foreach($c_arr  as $c_v){
												if(!stristr(strtoupper($line), strtoupper($c_v))  && !stristr(strtoupper($file_full_path), strtoupper($c_v))  ){
													$continue_context = true;
												}else{
													$continue_context = false;
													continue; //one negative match is enough
												}
											}											
										}else{
											$continue_context = true;
										}
									}else{
									//normal context, include	
										if(trim($c)){ //look for context in filename and line
											foreach($c_arr  as $c_v){ 
												if(stristr(strtoupper($line), strtoupper($c_v))  || stristr(strtoupper($file_full_path), strtoupper($c_v))  ){
													$continue_context = true;
													continue; //one positive match is enough
												}else{
													$continue_context = false;
												}	
											}											
										}else{
											$continue_context = true;
										}										
									}
									
									
									if ($found2 && $continue_context) {
										$xcerpt = htmlspecialchars(str_replace("\t", ' &nbsp;&nbsp;', trim($line))) ;
										$xcerpt_len = strlen($xcerpt);
										$xcerpt = substr($xcerpt,0,1500);
										
										$ext = pathinfo($file_full_path, PATHINFO_EXTENSION);
										
										if($ext=='php' && $xcerpt_len > 1000 ){ ////$xcerpt
											$mark = 'dangerline';
										}else{
											$mark = ' ';
										}
										
										$xcerpt_sig = base64_encode($file_full_path."|||".md5($xcerpt));
										$div_line_index++;
										if(!in_array($xcerpt_sig.PHP_EOL,$sigs)){
											$ret2 .= "<li id='{$div_file_index}-{$div_line_index}'><a href='javascript:void(0)' data-sig='{$xcerpt_sig}' data-liid='{$div_file_index}-{$div_line_index}' class='whitelistbtn isOK'>🛇</a> Line #<strong>" . ($line_num + 1) . "</strong>: <span class='{$mark}'>" . $xcerpt. "</span> <span class='linelen'>{$xcerpt_len} chars</span></li>\n";
											$siglist[] = $xcerpt_sig;
											$atleastonenothidden = true;
											$anyresult++;
										}else{
											$ret2 .= "<li class='isokline'>Line #<strong>" . ($line_num + 1) . "</strong>: " . $xcerpt. " <span class='linelen'>{$xcerpt_len} chars</span> </li>\n";
										}
									}
								}
								$ret2 .= "\n";
								 
								$lastdirq = urlencode(basename( dirname($file_full_path) ).'/'.basename($file_full_path)); 
								
								if($atleastonenothidden){
									
									
									
									$siglist_concat= implode('|',$siglist);
									$ulid = md5($file_full_path);
									$ret .= "<div class='fullpath'  id='{$ulid}_title' >{$file_full_path} [<a href='https://google.com/search?igu=1&ei=&q={$lastdirq}' target='preview' class='showsrc' >G</a>]  [<a href='?showsource={$file_full_path}' target='preview' class='showsrc' >src</a>] <a href='javascript:void(0)' data-siglist='{$siglist_concat}' data-ulid='{$ulid}' class='whitelistallbtn isOKall'>🛇</a> </div>\n";
									$ret .= "<ul  id='{$ulid}_ul'  class='results'>".$ret2."</ul><br/>\n";
								}else{ 
									$ret .= "<div class='fullpath inactivepath' >{$file_full_path}  [<a href='https://google.com/search?igu=1&ei=&q={$lastdirq}' target='preview' class='showsrc' >G</a>] [<a href='?showsource={$file_full_path}' target='preview' class='showsrc' >src</a>] </div>\n";
									//$ret .= "<ul id='file{$div_file_index}' class='results ' style='display: none'>".$ret2."</ul>\n";
								}
								
							} //found in file
						//}
                    }
        }
        return $ret;
    }
}

if(!isset($c)){
		$c = false;
}
	
if (! empty($q)) {
    $results = php_grep($q, $c, $exclude_context, $path);
}

if(isset($_GET['api']) && $_GET['api']=='yes'){
	$apiret = array();
	if($anyresult<=0){
		$apiret['status'] = 'nothing_found';
	}else{
		$apiret['status'] = 'new_results';
		$apiret['count'] = $anyresult;
	}
	header('Content-Type: application/json');
	echo json_encode($apiret);
	die();
}
?>




<html>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<style>

.inlarge { 
	width: 100%;
	margin: 3px;
	padding: 3px;
}
.rightshortcuts {
	font-size: 11px;
    font-family: monospace;
    background-color: #e4e3e3;
    padding: 5px;
    margin: 3px;
	color: #2e403f;
	border-radius: 6px;
}
.searchin{
	font-family: monospace;
    font-size: 20px;
    letter-spacing: 0px;
    word-spacing: 2px;
    color: #1a292d;
    font-weight: normal;
    padding: 9px;
    text-decoration: none;
    font-style: normal;
    font-variant: normal;
    text-transform: none;
    background-color: #f4ffe8;
    border: 1px solid #6e7976;
    border-radius: 4px;
    margin: 9px;
	width: 55%;
}


.contextin{
	font-family: monospace;
    font-size: 20px;
    letter-spacing: 0px;
    word-spacing: 2px;
    color: #96445a;
    font-weight: normal;
    padding: 9px;
    text-decoration: none;
    font-style: normal;
    font-variant: normal;
    text-transform: none;
    background-color: #f4ffe8;
    border: 1px solid #6e7976;
    border-radius: 4px;
    margin: 9px;
	width: 20%;
}



.searchbtn {
	box-shadow: 0px 10px 14px -7px #3e7327;
	background:linear-gradient(to bottom, #77b55a 5%, #72b352 100%);
	background-color:#77b55a;
	border-radius:4px;
	border:1px solid #4b8f29;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:13px;
	font-weight:bold;
	padding:6px 12px;
	text-decoration:none;
	text-shadow:0px 1px 0px #5b8a3c;
}
.searchbtn:hover {
	background:linear-gradient(to bottom, #72b352 5%, #77b55a 100%);
	background-color:#72b352;
}
.searchbtn:active {
	position:relative;
	top:1px;
}

.rshortcut {
		cursor: pointer;
}
.rshortcut {
	box-shadow:inset 0px 1px 0px 0px #f5978e;
	background:linear-gradient(to bottom, #f24537 5%, #c62d1f 100%);
	background-color:#f24537;
	border-radius:4px;
	border:1px solid #d02718;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:10px;
	padding:2px 10px;
	text-decoration:none;
	text-shadow:0px 0px 0px #810e05;
}
.rshortcut:hover {
	background:linear-gradient(to bottom, #c62d1f 5%, #f24537 100%);
	background-color:#c62d1f;
}
.rshortcut:active {
	position:relative;
	top:1px;
}
.rightshortcuts{
    float: right;
}	

ul {
	list-style: none;
}	
li {
	font-family: monospace;
	xxdisplay:inline-block;
	font-size:12px;
	margin:2px;
}
li:hover{
	background-color: #fcead9;
	font-size:13px;
	text-decoration: none;
	margin:2px;
	color: #17517a;
}


.fullpath {
	font-family: monospace;
	letter-spacing: 1px;
	font-weight: bold;
	border-left: 4px solid #70ec56;
	background-color: #e8f5e1;
	padding: 2px;
}

.inactivepath {
    font-family: monospace;
    letter-spacing: 1px;
    font-weight: bold;
    border-left: 4px solid #9ca99a;
    background-color: #fbfbfb;
    padding: 0px;
	color: #d4d8dc;
	    font-size: 10px;
}

body { 
	padding: 20px;
}

.whitelistbtn {
	box-shadow:inset 0px 1px 0px 0px #ffffff;
	background:linear-gradient(to bottom, #ffffff 5%, #f6f6f6 100%);
	background-color:#ffffff;
	border-radius:6px;
	border:1px solid #dcdcdc;
	display:inline-block;
	cursor:pointer;
	color:#666666;
	font-family:Arial;
	font-size:10px;
	font-weight:bold;
	padding:4px 9px;
	text-decoration:none;
	text-shadow:0px 1px 0px #ffffff;
}
.whitelistbtn:hover {
	background:linear-gradient(to bottom, #e03434 5%, #d40000 100%);
	background-color:#e03434;
	color: #fff;
}
.whitelistbtn:active {
	position:relative;
	top:1px;
}


.whitelistallbtn {
	box-shadow:inset 0px 1px 0px 0px #ffffff;
	background:linear-gradient(to bottom, #ffffff 5%, #f6f6f6 100%);
	background-color:#ffffff;
	border-radius:6px;
	border:1px solid #dcdcdc;
	display:inline-block;
	cursor:pointer;
	color:#666666;
	font-family:Arial;
	font-size:9px;
	font-weight:bold;
	padding:1px 3px;
	text-decoration:none;
	text-shadow:0px 1px 0px #ffffff;
}
.whitelistallbtn:hover {
	background:linear-gradient(to bottom, #e03434 5%, #d40000 100%);
	background-color:#e03434;
	color: #fff;
}
.whitelistallbtn:active {
	position:relative;
	top:1px;
}

.isokline{
	color: #d7dade;
	xxtext-decoration: line-through;
	font-size: 9px;
	margin: 0px;
}
      
.resetsigbtn {
	box-shadow:inset 0px 1px 0px 0px #cf866c;
	background:linear-gradient(to bottom, #d0451b 5%, #bc3315 100%);
	background-color:#d0451b;
	border-radius:4px;
	border:1px solid #942911;
	display:inline-block;
	cursor:pointer;
	color:#ffffff;
	font-family:Arial;
	font-size:10px;
	padding:2px 5px;
	text-decoration:none;
	text-shadow:0px 1px 0px #854629;
}
.resetsigbtn:hover {
	background:linear-gradient(to bottom, #bc3315 5%, #d0451b 100%);
	background-color:#bc3315;
}
.resetsigbtn:active {
	position:relative;
	top:1px;
}
.xpre {
	
}

.showsrc{
	text-decoration:none;
	color: #4e8563;
}
.showsrc:hover{
	text-decoration: none;
	color: #089ebf;
}
.showsrc:active{
	text-decoration: underline;
	color: #c740aa;
}
.previewwrapper {
    background-color: ivory;
    margin: 0 auto;
    position: fixed;
    bottom: 10px;
    right: 20px;
}

.previewwrapper iframe{
	width: 40vw;
    height: 27vw;
	resize: both;
	overflow: auto;
}
                
.dangerline {
	background-color: #f02e2e;
	color: #fff;
	padding: 2px;
}	

.linelen {
	color: #cfcfcf;
}

</style>

<script>


//list of malware keywords

$(document).ready(function() {

//@@Todo: file{$div_file_index} on ul click to open hidden details list

	$("li a.isOK").click(function(){
		var sig = $(this).data("sig");
		var liid = $(this).data("liid");
		var q = '<?php echo (isset($q_get)) ? $q_get : ''; ?>';
		//alert(sig);
		//if(confirm("Are you sure you want to hide this record from future results?")){
			$.ajax({
				url: "?whitelist=" + sig + "&q=" + q, //pass q to catch sigmode commands for multisearch
				contentType: "application/json",
				dataType: 'json',
				success: function(result){
					//alert(liid);
					//$('#'+liid).removeClass("blue");
					$('#'+liid+" a").hide();
					$('#'+liid).addClass("isokline");
				}
			});
		//} //confirm
	});

	$("a.isOKall").click(function(){
		var siglist = $(this).data("siglist");
		var ulid = $(this).data("ulid");
		var q = '<?php echo (isset($q_get)) ? $q_get : ''; ?>';
		//alert(siglist);
		//alert(ulid);
		//if(confirm("Are you sure you want to hide all current results from future searches?")){
			$.ajax({
				url: "?whitelistall=" + siglist + "&q=" + q, //pass q to catch sigmode commands for multisearch
				contentType: "application/json",
				dataType: 'json',
				success: function(result){
					//alert("result:"+result);
					//$('#'+liid).removeClass("blue");
					$('#'+ulid+"_title a").hide();
					$('#'+ulid+"_ul").hide(); //li a
					$('#'+ulid+"_ul li").addClass("isokline");
				}
			});
		//} //confirm
	});
	
	<?php foreach ($multisearch as $k => $v){ ?>
			
			$("#btnrightshortcuts_<?php echo $k; ?>").click(function(){
				$('input[name="q"]').val("<?php echo $v; ?>");
				$('form#searchform').submit();
			}); 
			
	<?php } ?>
});
		
	
</script>

</head>
<body>



<?php
$extensions = explode("|",$extentions_list);


?>
    <form method="post" style="margin:50px auto; width:1000px" id="searchform">
	<?php foreach($extensions as $extension){ ?>
		<div style="display:inline; margin-right:3px;">
			<input type="checkbox" name="extensions[]" value="<?php echo $extension; ?>" <?php if(!isset($_REQUEST['extensions']) || (isset($_REQUEST['extensions']) && is_array($_REQUEST['extensions']) && in_array($extension,$_REQUEST['extensions']))) echo ' checked="checked"'?> id="extensions_<?php echo $extension; ?>"/><label for="extensions_<?php echo $extension; ?>"><?php echo $extension; ?></label>
		</div>
	<?php } ?>
		<div style="clear:both; margin-top:5px;">
			 Ignore Folders: <input name="excludes"   value="<?php echo $excludes_list; ?>" class="inlarge"/>
		</div>
		<div style="clear:both; margin-top:5px;">
			Path: <input name="path"  value="<?php echo $path; ?>" class="inlarge"/> 
		</div>
		<div style="clear:both;">
			Search Query: 
			<input name="q"  value="<?php echo htmlentities(($sigmode) ? $sigmode : $q); ?>" placeholder="Search Query" class='searchin inlarge' /> 
			<input name="c"  value="<?php echo htmlentities($c); ?>" placeholder="Line Context" class='contextin inlarge' /> 
			<input type="checkbox" name="exclude_context" value="test" <?php echo ($_REQUEST['exclude_context']) ? ' checked="checked" ' : " "; ?> />Neg Context
		</div>
		<div style="clear:both;">
			<input type="submit" value="Search" class="searchbtn" />
		</div>
		
		<div class="rightshortcuts">
		<?php if(isset($html_sigstatus)){ echo $html_sigstatus."<hr>"; } ?>
		
		<?php if($sigmode){ ?>
			<b>Multisearch command <?php echo $sigmode; ?></b>: <br><?php echo implode(", ",array_map('htmlspecialchars',$patterns[$sigmode])); ?>
		<?php } ?>
		<br>
		<?php foreach ($multisearch as $k => $v){ ?>
			<div class="rshortcut"  id="btnrightshortcuts_<?php echo $k; ?>" ><?php echo str_replace('cmd❚','',$v); ?></div>
		<?php } ?>

		</div>
	</form>
	<p><br><br></p>
<?php if(!empty($results)):
 echo <<<HRD
	<div class="xpre">
		$results
	</div>
HRD;
?>

<hr><hr>
<?php 
echo "<br>SessID:".session_id();
endif;

?>

<script>
function loadIframe(iframeName, url) {
    var $iframe = $('#' + iframeName);
    if ( $iframe.length ) {
        $iframe.attr('src',url);   
        return false;
    }
    return true;
}
</script>

<div class="previewwrapper"><iframe allowfullscreen srcdoc="You can preview file sources or google searches here. This window is resizable. Look for abnormally formatted code or google results that point to malware information.<br><br>Abnormally long first or last lines in soruce view, are strong sign of malware infection." xheight="400" xwidth="400" name="preview" id="preview" src="about:blank" title="Preview file" style="border:2px solid green;"></iframe></div>

<b>Tools:</b> 
<br>https://www.unphp.net/
<br>https://beautifytools.com/php-beautifier.php
<br>https://www.diffchecker.com/
<br>Api use for malware detection: <?php $thislnk = explode("?",basename($_SERVER["REQUEST_URI"], ".php")); echo $thislnk[0]; ?>?runspecial=yes&api=yes
<br><br>
.:Dev by MXL 2000-2021:. 
<br>Version: <?php echo $version; ?> 
<br>IP: <?php echo $_SERVER['REMOTE_ADDR']; ?>
<br>Time: <?php echo time(); ?>
<br><a href="?login_to_wp=yes" >Login to WP</a> 

| <a href="Javascript:if(confirm('Are you sure you want to set all dir and file permissions? This will also remove any 777')){ location.href='?set_permissions=yes';}" >Set permissions</a>

</body>
</html>

<?php }elseif($_GET['login_to_wp']){ 

/* TOOL FOR LOGGING INTO WP-ADMIN DIRECTLY */

require_once( dirname( __FILE__ ) . '/wp-load.php' );

function allow_programmatic_login( $user, $username, $password ) {
    return get_user_by( 'login', $username );
 }
 
    function programmatic_login( $username ) {
        if ( is_user_logged_in() ) {
            wp_logout();
        }

    add_filter( 'authenticate', 'allow_programmatic_login', 10, 3 );    // hook in earlier than other callbacks to short-circuit them
    $user = wp_signon( array( 'user_login' => $username ) );
    remove_filter( 'authenticate', 'allow_programmatic_login', 10, 3 );

    if ( is_a( $user, 'WP_User' ) ) {
        wp_set_current_user( $user->ID, $user->user_login );

        if ( is_user_logged_in() ) {
            return true;
        }
    }

    return false;
 }
    
	
function fb_list_authors($userlevel = 'all', $show_fullname = true) {
	global $wpdb;
	
/*
 all = Display all user
 1 = subscriber
 2 = editor
 3 = author
 7 = publisher
10 = administrator
*/

	if ($userlevel == 1) {
		$authors = $wpdb->get_results("SELECT * from $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value = 'a:1:{s:10:\"subscriber\";b:1;}'");
	} else {
		$authors = $wpdb->get_results("SELECT * from $wpdb->usermeta WHERE meta_value = '$userlevel'");
	}
	foreach ( (array) $authors as $author ) {
		$author = get_userdata( $author->user_id );
		$userlevel = $author->wp2_user_level;
		$name = $author->nickname;
		if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') ) {
			$name = "$author->first_name $author->last_name";
		}
		//$link  = '<li><b>' . $userlevelname[$userlevel] . '</b></li>';
		$link = "<li>{$name} ({$author->user_id})</li>";
		echo $link;

			if($author->user_login){
				$loguser = $author->user_login;
				var_dump($author->user_login);
				if(programmatic_login($loguser)){
					    $redirect_to = user_admin_url();
						wp_safe_redirect( $redirect_to );
				}else{
					echo "unable to login";
				}
				die();
			}
	}
}
//fb_list_authors('all', false);
fb_list_authors('10', false);
var_dump($aid);

	



}elseif($_GET['set_permissions']){

 $c_dir = 0;
 $c_fils = 0;
  function chmod_r($dir, $dirPermissions, $filePermissions) {
	  global $c_dir, $c_fils;
      $dp = opendir($dir);
       while($file = readdir($dp)) {
         if (($file == ".") || ($file == ".."))
            continue;

        $fullPath = $dir."/".$file;

         if(is_dir($fullPath)) {
			 $c_dir++;
            echo('DIR:' . $fullPath . "\n");
            chmod($fullPath, $dirPermissions);
            chmod_r($fullPath, $dirPermissions, $filePermissions);
         } else {
			 $c_fils++;
            echo('FILE:' . $fullPath . "\n");
            chmod($fullPath, $filePermissions);
         }

       }
     closedir($dp);
  }

  chmod_r(dirname(__FILE__), 0755, 0644);

  echo "<hr>Files: {$c_fils}  Directories: {$c_dir}";



 }

 ?>