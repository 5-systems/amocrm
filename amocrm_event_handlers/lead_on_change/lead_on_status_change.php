<?php

	header('Content-Type: text/html; charset=utf-8');

  // Uncomment to use a custom settins file
   //$_REQUEST['param_login']='5systems';

   $current_dir_path=getcwd();
   $current_dir_path=rtrim($current_dir_path, '/').'/';
   $amocrm_dir=$current_dir_path.'../../amocrm/';

   
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
	

	@$data=json_encode($_REQUEST);
	if( !isset($data) ) $data='';

        $result_log='result: ';
        $result_return='';

	write_log('blank_line', $amocrm_log_file, 'LEAD_STATUS');
   write_log('Start', $amocrm_log_file, 'LEAD_STATUS');	
	write_log($data, $amocrm_log_file, 'LEAD_STATUS');

	ini_set("soap.wsdl_cache_enabled", "0");
	$SoapClient1C=null;

	try {
		$SoapClient1C = new SoapClient("http://127.0.0.1:8080/database_url/ws/wsamointegration.1cws?wsdl", array('login' => "WebService_user", 'password' => "00000"));
	}
	catch (Exception $e) {
   	 	$result_return='Exception: '.($e->getMessage());

                $result_log.=$result_return;
		write_log($result_log, $amocrm_log_file, 'LEAD_STATUS');

		unset($SoapClient1C);
		exit($result_return);
	}
																							
	$params=Array();
	$params['method']='lead_on_status_change';
	$params['data']=$data;
	
	try {

	   $Result=array();
	   $Result = $SoapClient1C->getData($params);

	   foreach($Result as $line) {
		
		$result_return=strVal($line);
		$result_log.=$result_return;
		write_log($result_log, $amocrm_log_file, 'LEAD_STATUS');			
	   }
	
        }
	catch(Exception $e) {

                $result_return='Exception: '.($e->getMessage());

                $result_log.=$result_return;
                write_log($result_log, $amocrm_log_file, 'LEAD_STATUS');

                unset($SoapClient1C);
                exit($result_return);

	}

	unset($SoapClient1C);

	echo $result_return;
	
	write_log('Finish', $amocrm_log_file, 'LEAD_STATUS');
	
?>
