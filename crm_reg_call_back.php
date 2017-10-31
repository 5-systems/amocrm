<?php
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/callback.log';

	$CallId='';
	$CallerId='';
	$CallDate='';
	$CalledId='';
	$CallerName='';
	$Comment='';
	$WebPage='';
	$AdvChannel='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallerId=$argv[$i];
		if($i==3) $CallDate=$argv[$i];
		if($i==4) $CalledId=$argv[$i];
		if($i==5) $CallerName=$argv[$i];
		if($i==6) $Comment=$argv[$i];
		if($i==7) $WebPage=$argv[$i];
		if($i==8) $AdvChannel=$argv[$i];
	}

	write_log($argv, $log_file, 'OUT');

	$url="http://10.0.0.8:8080/crm_reg_call.php";
	$url=$url."?$CallId&$CallerId&$CallDate&$CalledId&FromWeb=1&MissedCall=0&$CallerName&$Comment&$WebPage&$AdvChannel";

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
