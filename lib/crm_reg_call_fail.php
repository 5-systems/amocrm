<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$log_file='/var/log/5-systems/calls.log';

	$CallId='';
	$CallDate='';
	$CallerId='';
	$ExtNum='';
	$CallDuration='';
	$CallTerminatedBy='';
	
	$num_parameters=count($argv);
	for( $i=0; $i<$num_parameters; $i++ ) {
		if($i==1) $CallId=$argv[$i];
		if($i==2) $CallDate=$argv[$i];
		if($i==3) $CallerId=$argv[$i];
		if($i==4) $ExtNum=$argv[$i];
		if($i==5) $CallDuration=$argv[$i];
		if($i==6) $CallTerminatedBy=$argv[$i];
	}

	//registration in CRM
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
	    write_log($argv, $log_file, 'FAIL1');

	    $url="http://10.0.0.8:8080/crm_reg_call.php";
	    $url=$url."?CallId=$CallId&CallerNumber=$CallerId&FirstCalledNumber=$ExtNum&CallDate=$CallDate&CallDuration=$CallDuration&CallTerminatedBy=$CallTerminatedBy&MissedCall=1&PhoneStation=AS";

	    //write_log($url, $log_file, 'FAIL2');
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
	
	    echo $response;

	    //write_log($url, $log_file, 'FAIL3');
	} elseif( strpos($ExtNum, '4955404614')!==false ) {
	    $url="http://127.0.0.1:80/amocrm/amocrm_reg_call.php";
	    $url=$url."?Account=5systems&CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&MissedCall=1";

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
	} elseif( strpos($ExtNum, '4991104882')!==false ) {
	    $url="http://127.0.0.1:80/amocrm/amocrm_reg_call.php";
	    $url=$url."?Account=stozone&CallId=$CallId&CallerNumber=$CallerId&CallDate=$CallDate&MissedCall=1";

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
	}

	
	//send email and sms
	if( strpos($ExtNum, '4991103369')!==false || strpos($ExtNum, '4951628355')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Ярославское шоссе 25). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('79853099147@yandex.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Ярославское шоссе 25) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Ярославское шоссе 25). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	elseif( strpos($ExtNum, '4951045014')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('Cityglush@list.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Хорошевское шоссе) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	elseif( strpos($ExtNum, '4951628344')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('Cityglush@list.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Хорошевское шоссе Дублер) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе Дублер). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	elseif( strpos($ExtNum, '4951320158')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Ярославское шоссе 2Е Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('cityglush@yandex.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Ярославское шоссе 2Е Дублер) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	elseif( strpos($ExtNum, '4951320167')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Обручева Дублер). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    //write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('cityglush00@bk.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Обручева Дублер) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Хорошевское шоссе). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	elseif( strpos($ExtNum, '4951042811')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Обручева). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('tuk2007@mail.ru')."&mail[]=".urlencode('Cityglush00@bk.ru')."&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Обручева) ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок с Avito (Обручева). Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'EMAIL');
	}
	elseif( strpos($ExtNum, '4952411633')!==false ) {
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
	    $url=$url."&text=".urlencode("Был зафиксирован пропущенный звонок с Avito (Ярославское шоссе 2Е). Дата/время звонка: ".date("d-m-Y H:i:s").". Номер абонента: 8".substr($CallerId,-10,10));

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
	    write_log($url, $log_file, 'telegram');
	
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&mail[]=".urlencode('cityglush@yandex.ru')."&mail[]=".urlencode('tuk2007@mail.ru')."&subject=".urlencode("CityGlush - Пропущенный звонок с Авито (Ярославское шрссе 2Е)".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок. Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
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
	    //telegram
	    $url="https://api.telegram.org/bot:token/sendMessage?chat_id=-152627214";
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

	    //$response=curl_exec($curl);
	    //write_log($url, $log_file, 'telegram2');
	    
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("AGS - Пропущенный звонок с Авито".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок по направлению AGS. Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
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
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CallCenter - Пропущенный звонок ".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок по направлению CallCenter. Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
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
	    
	}elseif( strpos($ExtNum, '9587568975')!==false ) {
	    //email
	    $url="http://10.0.0.6:80/for_asterisk/send_mail.php";
	    $url=$url."?key=467286492011342891&mail[]=".urlencode('neutrino64@ya.ru')."&mail[]=".urlencode('89774464942psto@gmail.com')."&subject=".urlencode("CallCenter Spb - Пропущенный звонок".date("d-m-Y H:i:s"))."&text=".urlencode("Добрый день!<br/>Был зафиксирован пропущенный звонок по направлению CallCenter Spb. Пожалуйста перезвоните.<br/>Дата/время звонка: ".date("d-m-Y H:i:s")."<br/>Номер абонента: 8".substr($CallerId,-10,10));
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

	//registration in CRM
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
	    write_log($argv, $log_file, 'FAIL1');

	    $url="http://10.0.0.8:8080/crm_reg_call.php";
	    $url=$url."?CallId=$CallId&CallerNumber=$CallerId&FirstCalledNumber=$ExtNum&CallDate=$CallDate&CallDuration=$CallDuration&CallTerminatedBy=$CallTerminatedBy&MissedCall=1&PhoneStation=AS";

	    //write_log($url, $log_file, 'FAIL2');
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

	    //write_log($url, $log_file, 'FAIL3');
	}


?>
