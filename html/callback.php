<?php

require_once('5c_files_lib.php');

// call back
$ASTHost = "127.0.0.1";
$ASTPort = "5038";
$ASTTimeout = "10";
$ASTUser='admin';
$ASTPassword='---';
$path_log='/var/log/company_name/callback.log';


error_reporting(E_ALL);
ini_set('display_errors', 1);

# Set timezone
date_default_timezone_set('Etc/GMT-3');
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

@$phone=htmlspecialchars($_REQUEST['phone']);
@$name=htmlspecialchars($_REQUEST['name']);
@$comment=htmlspecialchars($_REQUEST['comment']);
@$domain=htmlspecialchars($_REQUEST['domain']);
@$parts=$_REQUEST['parts'];
@$session_id=$_REQUEST['session_id'];
@$user_id=$_REQUEST['user_id'];
@$department=$_REQUEST['department'];

write_log('blank_line', $path_log);
write_log($_REQUEST, $path_log);

exit('ok');

if(!isset($phone)) {
        $phone='';
}
else {
	$phone=filter_numeric( urldecode($phone) );
	$phone=substr($phone, -10);
	$phone='8'.$phone;
}


if(!isset($name)) {
        $name='';
}
else {
	$name=filter_alphanumeric( html_to_utf8( urldecode($name) ) );
}

if(!isset($comment)) {
        $comment='';
}
else {
	$comment=filter_alphanumeric( html_to_utf8( urldecode($comment) ) );
}

if(!isset($domain)) {
        $domain='';
}
else {
	$domain=filter_alphanumeric($domain);
}

if(!isset($parts)) {
        $parts='';
}

if(!isset($session_id)) {
        $session_id='';
}

if(!isset($user_id)) {
        $user_id='';
}

if(!isset($department)) {
        $department='';
}


$queue='100';

$errno=5;
$errstr="";
$sconn = fsockopen($ASTHost, $ASTPort, $errno, $errstr, $ASTTimeout);

$ReturnValue='';

if ($sconn) {
	fputs ($sconn, "Action: Login\r\n");
	fputs ($sconn, "Username: ".$ASTUser."\r\n");
	fputs ($sconn, "Secret: ".$ASTPassword."\r\n");
	fputs ($sconn, "\r\n");
	usleep(500000);


	fputs ($sconn, "Action: Originate\r\n");
	fputs ($sconn, "Channel: Local/s@crm_callback_inbound\r\n");
	fputs ($sconn, "Callerid: Заявка с сайта. Имя: ".$name." Телефон: ".$phone."\r\n");
	fputs ($sconn, "Context: crm_callback_outbound\r\n");
	fputs ($sconn, "Exten: s\r\n");
	fputs ($sconn, "Variable: CALLEXT=".$phone."\r\n");
	fputs ($sconn, "Variable: NAMEEXT=".$name."\r\n");
	fputs ($sconn, "Variable: COMMENTEXT=".$comment."\r\n");
	fputs ($sconn, "Variable: WEBPAGEEXT=".$domain."\r\n");
	fputs ($sconn, "Variable: ADVCHANNELEXT=".$domain."\r\n");
	fputs ($sconn, "Variable: QUEUEEXT=".$queue."\r\n");
	fputs ($sconn, "Variable: PARTSEXT=".$parts."\r\n");
	fputs ($sconn, "Variable: SESSIONIDEXT=".$session_id."\r\n");
	fputs ($sconn, "Variable: USERIDEXT=".$user_id."\r\n");

	fputs ($sconn, "Timeout: 1800000"."\r\n");
	fputs ($sconn, "Priority: 1\r\n\r\n");

	fputs ($sconn, "Action: Logoff\r\n\r\n");
	usleep (500000);

	//while(!feof($sconn)) {
  	//   	$ReturnValue=$ReturnValue.fgets($sconn);
  	//}

	fclose ($sconn);
	$ConnResult='Ok';	
}
else {
	$ReturnValue='1';
	$ConnResult='failed';
}

// Write to log
$LogResult=Array('status'=>'обратный звонок: '.$ConnResult);
$LogResult['name']=$name;
$LogResult['phone']=$phone;
$LogResult['comment']=$comment;
$LogResult['domain']=$domain;
$LogResult['queue']=$queue;
$LogResult['parts']=$parts;
$LogResult['session_id']=$session_id;
$LogResult['user_id']=$user_id;
$LogResult['department']=$department;

write_log($LogResult, $path_log);

exit($ReturnValue);

function filter_alphanumeric($input_string) {
	$output=preg_replace("/[ ]/", '_', $input_string);
	$output=preg_replace("/[^А-Яа-яA-Za-z0-9_]/u", '', $output);
	return($output);
}


function filter_numeric($input_string) {
	$output=preg_replace("/[^0-9]/u", '', $input_string);
	return($output);
}

?>

