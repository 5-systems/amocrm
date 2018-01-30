<?php

  require_once('5c_files_lib.php');
  require_once('5c_amocrm_lib.php');
  require_once('5c_std_lib.php');
  require_once('5c_asterisk_lib.php');

 
  date_default_timezone_set('Etc/GMT-3');

//  error_reporting(E_ALL);
//  ini_set('display_errors', 1);

  @$user_id=$_REQUEST['param_user_id'];
  @$phone=$_REQUEST['param_phone'];
  @$login=$_REQUEST['param_login'];
  @$password=$_REQUEST['param_password'];

  if( !isset($user_id) ) $user_id='';
  if( !isset($phone) ) $phone='';
  if( !isset($login) ) $login='';
  if( !isset($password) ) $password='';
  
  $settigs_found=false;
  if( isset($_REQUEST['param_login'])
      && strlen($_REQUEST['param_login'])>0 ) {
          
      $settings_file_path='amocrm_settings_'.strVal($_REQUEST['param_login']).'.php';
      if( file_exists($settings_file_path) ) {
          require_once($settings_file_path);
          $settigs_found=true;
      }
  }
  
  if( $settigs_found===false ) {
      require_once('amocrm_settings.php');
  }     
  
  write_log('blank_line', $amocrm_log_file, 'CONN2CALL');
  write_log($_REQUEST, $amocrm_log_file, 'CONN2CALL');

  $result='';  

  // Get user's internal phone
  $user_phone='';
  $http_requester=null;
  $db_conn=null;
  if( strlen($user_id)>=3 ) {
  
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'log_file'}=$amocrm_log_file;
   
   if( isset($amocrm_sleep_time_after_request_microsec)
       && is_numeric($amocrm_sleep_time_after_request_microsec)
       && intVal($amocrm_sleep_time_after_request_microsec)>0 ) {
           
       $http_requester->{'sleep_time_after_request_microsec'}=$amocrm_sleep_time_after_request_microsec;
   }
   
   $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
   if( isset($db_conn) ) {
       
       $db_conn->autocommit(true);
       $http_requester->{'lock_database_connection'}=$db_conn;
       $http_requester->{'lock_priority'}=0;
   }
  
   $user_phone=get_user_internal_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester);    
  } // user_id ok
  
 
  write_log('User phone: '.$user_phone, $amocrm_log_file);
  if( strlen($user_phone)<3 ) {
    write_log('internal phone is too short', $amocrm_log_file, 'CONN2CALL');
    $result.="failed: internal phone too short ".$user_phone;
  }  
  
  // Get client phone
  $client_phone=remove_symbols($phone);
  $client_phone=substr($client_phone, -10);
  $parsed_client_phone=$client_phone;
  if( strlen($client_phone)===10 ) $client_phone=$phone_prefix.$client_phone;
  
  write_log('client phone: '.$client_phone, $amocrm_log_file);
  if( strlen($client_phone)<3 ) {
      write_log('client phone is too short', $amocrm_log_file, 'CONN2CALL');
    $result.="failed: client phone too short ".$client_phone;
  }


  // Connect callers
  $connect_status=false;
  $phone_connection=null;
  $phone_station_reply='';
  if( strlen($user_phone)>=3
      && strlen($client_phone)>=3 ) {

    $phone_connection=new asterisk_connector();
    $phone_connection->{'host'}=$host_phone_station;
    $phone_connection->{'port'}=$port_phone_station;
   
    $connect_status=$phone_connection->__connect();
  }
 
  if( $connect_status===false ) {
    write_log('Phone station connection failed', $amocrm_log_file, 'CONN2CALL');
    $result.="failed: phone station connection failed ";
  }
 
  if( $connect_status!==false ) {
 
    $phone_connection->__submit_string("Action: Login");
    $phone_connection->__submit_string("Username: ".$user_phone_station);
    $phone_connection->__submit_string("Secret: ".$password_phone_station);
    $phone_connection->__submit_string("", 500000);

    $phone_connection->__submit_string("Action: Originate");
    $phone_connection->__submit_string("Channel: SIP/".$user_phone);
    $phone_connection->__submit_string("Callerid: CRM ".$client_phone);
    $phone_connection->__submit_string("Context: crm_connect2callers_outbound");
    $phone_connection->__submit_string("Timeout: 30000");
    $phone_connection->__submit_string("Exten: s");
    $phone_connection->__submit_string("Variable: CALLERID(num)=".$user_phone);
    $phone_connection->__submit_string("Variable: CALLEXT1=".$user_phone);
    $phone_connection->__submit_string("Variable: CALLEXT2=".$client_phone);
    $phone_connection->__submit_string("Variable: OBJECT1CID=");
    $phone_connection->__submit_string("Variable: OBJECT1CTYPE=");
    $phone_connection->__submit_string("Priority: 1");
    $phone_connection->__submit_string("");
   
    $phone_connection->__submit_string("Action: Logoff");
    $phone_connection->__submit_string("", 500000);
   
    $phone_station_reply=$phone_connection->__get_reply();

    $phone_connection->__close_connection();

    write_log('call to client: success', $amocrm_log_file, CONN2CALL);
    $result.="success ";
  }

  if( isset($db_conn) ) {
      $db_conn->close();
  }

  echo $result;

?>
