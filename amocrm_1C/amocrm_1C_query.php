<?php

	header('Content-Type: text/html; charset=utf-8');

  // Uncomment to use a custom settins file
   //$_REQUEST['param_login']='5systems';

   $current_dir_path=getcwd();
   $current_dir_path=rtrim($current_dir_path, '/').'/';
   $amocrm_dir=$current_dir_path.'../amocrm/';

   
   $settigs_found=false;
   if( isset($_REQUEST['param_login'])
      && strlen($_REQUEST['param_login'])>0 ) {

      $settings_file_path=$amocrm_dir.'amocrm_settings_'.strVal($_REQUEST['param_login']).'.php';
      if( file_exists($settings_file_path) ) {
         require_once($settings_file_path);
         $settigs_found=true;
      }
   }
   
   if( $settigs_found===false ) {
      require_once($amocrm_dir.'amocrm_settings.php');
   }
   
   require_once($amocrm_dir.'5c_files_lib.php');	
	
	@$method=$_REQUEST['method'];
	if( !isset($method) ) $method='';
   
	@$data=json_encode($_REQUEST);
	if( !isset($data) ) $data='';

   $result_log='result: ';
   $result_return='';

	write_log('blank_line', $amocrm_log_file, strtoupper($method));
   write_log('Start', $amocrm_log_file, strtoupper($method));	
	write_log($data, $amocrm_log_file, strtoupper($method));

	ini_set("soap.wsdl_cache_enabled", "0");
	$SoapClient1C=null;

	if( isset($amocrm_1C_integration_web_service_login)
	    && isset($amocrm_1C_integration_web_service_password)
	    && isset($amocrm_1C_integration_web_service_url) ) {
	
   	$login_password_soap=array('login' => $amocrm_1C_integration_web_service_login, 'password' => $amocrm_1C_integration_web_service_password);
   	
   	try {
   		$SoapClient1C = new SoapClient($amocrm_1C_integration_web_service_url, $login_password_soap);
   	}
   	catch (Exception $e) {
      	$result_return='Exception: '.($e->getMessage());
         $result_log.=$result_return;
         
   		write_log($result_log, $amocrm_log_file, strtoupper($method));
   
   		unset($SoapClient1C);
   		exit($result_return);
   	}
	
	}
	
	if( !isset($SoapClient1C) ) {
	   $result_return='Soap object is not created ';
	   $result_log=$result_return;
	   write_log($result_log, $amocrm_log_file, strtoupper($method));
	   
	   exit($result_return);
	}
																							
	$params=Array();
	$params['method']=$method;
	$params['data']=$data;
	
	try {

	   $Result=array();
      $Result = $SoapClient1C->getData($params);

	   foreach($Result as $line) {		
   		$result_return=strVal($line);
   		$result_log.=$result_return;
   		write_log($result_log, $amocrm_log_file, strtoupper($method));			
	   }
	
   }
	catch(Exception $e) {

       $result_return='Exception: '.($e->getMessage());

       $result_log.=$result_return;
       write_log($result_log, $amocrm_log_file, strtoupper($method));

       unset($SoapClient1C);
       exit($result_return);

	}

	unset($SoapClient1C);

	echo $result_return;
	
	write_log('Finish', $amocrm_log_file, strtoupper($method));
	
?>
