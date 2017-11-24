<?php
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/calls.log';

	$CallId='';
	$CallDate='';
	$CallerId='';
	$CalledId='';
	$ExtNum='';
	$Link='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallDate=$argv[$i];
		if($i==3) $CallerId=$argv[$i];
		if($i==4) $CalledId=$argv[$i];
		if($i==5) $ExtNum=$argv[$i];
		if($i==6) $Link=$argv[$i];
	}
	
	//registration in CRM
	if( strlen($ExtNum)===0 ) {
	    exit('error');
	}
	else {
	    $url="http://127.0.0.1:80/amocrm/amocrm_reg_call.php";
	    $url=$url."?Account=5systems&CallId=$CallId&CallerNumber=$CallerId&CalledNumber=$CalledId&CallDate=$CallDate&Link=$Link";

	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    try {
		$response=curl_exec($curl);
	    }    
	    catch(Exception $e) {
	
	    }

	    echo $response;

	    write_log($url, $log_file, 'AMO');
	}

?>
