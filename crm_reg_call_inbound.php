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
	    //exit('1');
	} elseif( strpos($ExtNum, '4951045014')!==false 
		|| strpos($ExtNum, '4991103369')!==false 
		|| strpos($ExtNum, '4951042811')!==false 
		|| strpos($ExtNum, '4952411633')!==false 
		|| strpos($ExtNum, '4952412355')!==false 
		|| strpos($ExtNum, '4951320158')!==false  
		|| strpos($ExtNum, '4951320167')!==false 
		|| strpos($ExtNum, '4951628344')!==false 
		|| strpos($ExtNum, '4951628355')!==false ) {
	    write_log($argv, $log_file, 'IN_TEST');

	    $url="http://10.0.0.8:8080/crm_reg_call.php";
	    $url=$url."?CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&FirstCalledNumber=$ExtNum&Outcoming=0&MissedCall=0&PhoneStation=AS";

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
		//$response=curl_exec($curl);
	    }    
	    catch(Exception $e) {
	
	    }

	    echo $response;

	    write_log($url, $log_file, 'IN');
	} elseif( strpos($ExtNum, '4955404614')!==false ) {
	    write_log($argv, $log_file, 'AMO_Test');

	    $url="http://127.0.0.1:80/amocrm/amocrm_reg_call.php";
	    $url=$url."?Account=5systems&CallId=$CallId&CallerNumber=$CallerId&CalledNumber=$CalledId&CallDate=$CallDate&Link=$Link";
	    //$url=$url."?CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&FirstCalledNumber=$ExtNum&Outcoming=0&MissedCall=0&PhoneStation=AS";

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
	} elseif( strpos($ExtNum, '4991104882')!==false ) {
	    //write_log($argv, $log_file, 'AMO_STOZone');

	    $url="http://127.0.0.1:80/amocrm/amocrm_reg_call.php";
	    $url=$url."?Account=stozone&CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&Link=$Link";

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
	
	//write_log($argv, $log_file, 'TEST');
		
	//send email and sms
	if( strpos($ExtNum, '4991103369')!==false || strpos($ExtNum, '4951628355')!==false ) {
	    //write_log($argv, $log_file, 'TEST_Telegram1');
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Ярославское шоссе). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram1');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('79853099147@yandex.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Ярославское шоссе 25)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito (Ярославское шоссе 25) .<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	    //sms
	    $url="http://10.0.0.6:80/for_asterisk/send_sms.php";
	    $url=$url."?key=467286492011342891&phone=79163123322&text=".urlencode("Вы приняли звонок с Avito. Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: ".$CallerId);

	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    //$response=curl_exec($curl);
	    //write_log($url, $log_file, 'SMS');
	    

	}
	elseif( strpos($ExtNum, '4951045014')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Хорошевское шоссе). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('Cityglush@list.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Хорошевское шоссе)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito (Хорошевское шоссе).<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4951628344')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Хорошевское шоссе Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('Cityglush@list.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Хорошевское шоссе Дублер)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito (Хорошевское шоссе Дублер).<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4951042811')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Обручева). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('Cityglush00@bk.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Обручева)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito (Обручева).<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	
	}
	elseif( strpos($ExtNum, '4952411633')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Ярославское шоссе 2Е). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('cityglush@yandex.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Ярославское шоссе 2Е)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4951320158')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Ярославское шоссе 2Е Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('cityglush@yandex.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Ярославское шоссе 2Е Дублер)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4951320167')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot198899021:AAHlKIjlARI6lgw3CXHPqbB3UWJsmdpGzjU/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Вы приняли звонок с Avito (Обручева Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram2');
	
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('cityglush00@bk.ru')."&subject=".urlencode("CityGlush - Принятый звонок с Авито (Обручева Дублер)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4952412355')!==false ) {
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("AGS - Принятый звонок с Авито".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок с Avito.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
	    $curl = curl_init();
	    $headers[] = "Content-Type:text/plain; charset=utf-8";
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_COOKIESESSION, false);
	    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
	    ini_set('default_socket_timeout', 3);

	    curl_setopt($curl, CURLOPT_URL, $url);

	    $response=curl_exec($curl);
	    
	    write_log($url, $log_file, 'EMAIL');
	    
	}
	elseif( strpos($ExtNum, '4991104882')!==false ) {
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("Call Center - Принятый звонок ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок по направлению Call Center.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'EMAIL');
	}
	elseif( strpos($ExtNum, '9587568975')!==false ) {
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("Call Center Spb- Принятый звонок ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Вы приняли входящий звонок по направлению Call Center.Spb<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'EMAIL');
	}
	
	if( strlen($ExtNum)===0 ) {
	    //exit('1');
	}
	elseif( strpos($ExtNum, '4951045014')!==false 
		|| strpos($ExtNum, '4991103369')!==false 
		|| strpos($ExtNum, '4951042811')!==false 
		|| strpos($ExtNum, '4952411633')!==false 
		|| strpos($ExtNum, '4952412355')!==false 
		|| strpos($ExtNum, '4951320158')!==false  
		|| strpos($ExtNum, '4951320167')!==false 
		|| strpos($ExtNum, '4951628344')!==false 
		|| strpos($ExtNum, '4951628355')!==false ) {
	    write_log($argv, $log_file, 'IN_TEST');

	    $url="http://10.0.0.8:8080/crm_reg_call.php";
	    $url=$url."?CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&CalledNumber=$CalledId&FirstCalledNumber=$ExtNum&Outcoming=0&MissedCall=0&PhoneStation=AS";

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

	    write_log($url, $log_file, 'IN');
	}


?>
