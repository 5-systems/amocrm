<?php
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/get_calldata.log';

	$CallerId='';
	$ExtNum='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallerId=$argv[$i];
		if($i==2) $ExtNum=$argv[$i];
	}

	write_log($argv, $log_file, '');

       $url='';
       $response='';

       if( strpos($ExtNum, '4951045014')!==false
                || strpos($ExtNum, '4991103369')!==false
                || strpos($ExtNum, '4951042811')!==false
                || strpos($ExtNum, '4952411633')!==false
                || strpos($ExtNum, '4952412355')!==false
                || strpos($ExtNum, '4951320158')!==false
                || strpos($ExtNum, '4951320167')!==false
                || strpos($ExtNum, '4951628344')!==false
                || strpos($ExtNum, '4951628355')!==false ) {

		$url="http://10.0.0.8:8080/getcalltype.php";
		$url=$url."?CallerNumber=$CallerId";
	}
	elseif( strpos($ExtNum, '4955404614')!==false ) {
		$url="http://127.0.0.1:80/amocrm/amocrm_getcalldata.php";
		$url=$url."?CallerNumber=$CallerId&Account=5systems";
	}
	elseif( strpos($ExtNum, '4991104882')!==false ) {
		$url="http://127.0.0.1:80/amocrm/amocrm_getcalldata.php";
		$url=$url."?CallerNumber=$CallerId&Account=stozone";
	}
	else {
		$url="http://10.0.0.8:8080/getcalltype.php";
		$url=$url."?CallerNumber=$CallerId";
	}


	if( strlen($url)>0 ) {
 
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

	}

	echo $response;

	write_log($url, $log_file, '');

?>
