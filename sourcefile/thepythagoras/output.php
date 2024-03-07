<?php

//@@TODO: 
//  if there is no post , and except pages where content can be modified, or is personal, such as myaccount, then chache the content
// generate signature $content (crc, md5) and if it exists in cache/pages/crc_1232353465.html , and is not older than 2min serve that instead
// this will create a short time cache and avoid db loading of same data within few minutes. Makes little difference when few users, but makes 
// huge difference when many many users load same pages, or attack


$page_content = ob_get_contents();
ob_end_clean();


$tpl = array();
$tpl['some_page_title'] = 'some title'; //example. get data from db or file


$page_content = str_replace(array_keys($tpl), array_values($tpl), $page_content);

echo $page_content;