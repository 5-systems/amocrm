<?php

require_once('5c_files_lib.php');
require_once('amocrm_settings.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

# Set timezone
date_default_timezone_set('Etc/GMT-3');
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

@$phone=htmlspecialchars($_REQUEST['phone']);
@$name=htmlspecialchars($_REQUEST['startparam2']);
@$comment=htmlspecialchars($_REQUEST['startparam3']);
@$domain=htmlspecialchars($_REQUEST['domain']);
@$parts=$_REQUEST['parts'];
@$session_id=$_REQUEST['session_id'];
@$user_id=$_REQUEST['user_id'];

if(!isset($phone)) {
        $phone='';
}
else {
	$phone=filter_numeric( urldecode($phone) );
	$phone=substr($phone, -10);
	$phone=$phone_prefix.$phone;
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


#if( $domain=='maxisyspro' ) $queue='199';
#if( $domain=='proboschru' || $domain=='proboschpro' ) $queue='199';


$errno=5;
$errstr="";
$sconn = fsockopen($host_phone_station, $port_phone_station, $errno, $errstr, $timeout_phone_station);

$ReturnValue='';

if ($sconn) {
	fputs ($sconn, "Action: Login\r\n");
	fputs ($sconn, "Username: ".$user_phone_station."\r\n");
	fputs ($sconn, "Secret: ".$password_phone_station."\r\n");
	fputs ($sconn, "\r\n\r\n");
	usleep(500000);


	fputs ($sconn, "Action: Originate\r\n");
	fputs ($sconn, "Channel: Local/s@crm_callback_inbound\r\n");
	fputs ($sconn, "Callerid: Заявка с сайта. Имя: ".$name." Телефон: ".$phone." Комментарий: ".$comment."\r\n");
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

	while(!feof($sconn)) {
  	   	$ReturnValue=$ReturnValue.fgets($sconn);
  	}

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

write_log($LogResult, $callback_log);

exit($ReturnValue);

function filter_alphanumeric($input_string) {
	$output=preg_replace("/[ ]/", '_', $input_string);
	$output=preg_replace("/[^А-Яа-яA-Za-z0-9_]/u", '', $output);
	return($output);
}

function html_to_utf8($input_str) {

	$output_str=preg_replace("/\\%u([A-Fa-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V',hexdec('U$1')))", $input_str);

        $output_str=preg_replace("/\\\\u([A-Fa-f0-9]{4})/e", "iconv('UCS-4LE','UTF-8',pack('V',hexdec('U$1')))", $output_str);
	
return($output_str);

}

function filter_numeric($input_string) {
	$output=preg_replace("/[^0-9]/u", '', $input_string);
	return($output);
}

?>

