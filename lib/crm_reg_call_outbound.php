<?php
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/calls.log';

	$CallId='';
	$CallDate='';
	$CallerId='';
	$CalledId='';
	$ObjectId='';
	$ObjectType='';
	$Link='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallDate=$argv[$i];
		if($i==3) $CallerId=$argv[$i];
		if($i==4) $CalledId=$argv[$i];
		if($i==5) $Link=$argv[$i];
		if($i==6) $ObjectId=$argv[$i];
		if($i==7) $ObjectType=$argv[$i];
	}

	if( strlen($CallerId)===0 || substr($CallerId, 0, 1)!=='9' ) exit('1');

	write_log($argv, $log_file, 'OUT');

	$url="http://10.0.0.4:80/amocrm/amocrm_reg_call.php";
	//$url=$url."?CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&Outcoming=1&MissedCall=0&Object1CId=$ObjectId&Object1CType=$ObjectType&PhoneStation=AS";
	$url=$url."?Account=5systems&CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&OutcomingCall=1&Link=$Link";

	$curl = curl_init();
	$headers[] = "Content-Type:text/plain; charset=utf-8";
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

	curl_setopt($curl, CURLOPT_URL, $url);

	$response=curl_exec($curl);

	echo $response;

	write_log($url, $log_file, 'OUT');

?>
