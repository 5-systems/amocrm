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


  write_log($_REQUEST, $amocrm_log_file);
  
  require_once('amocrm_settings.php');

  $result='';  

  // Get user's internal phone
  $user_phone='';
  $http_requester=null;
  if( strlen($user_id)>=3 ) {
  
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'amocrm_log_file'}=$amocrm_log_file;  
  
   $user_phone=get_user_internal_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester);    
  } // user_id ok
  
 
  write_log('User phone: '.$user_phone, $amocrm_log_file);
  if( strlen($user_phone)<3 ) {
    write_log('internal phone is too short', $amocrm_log_file);
    $result.="failed: internal phone too short ".$user_phone;
  }  
  
  // Get client phone
  $client_phone=remove_symbols($phone);
  $client_phone=substr($client_phone, -10);
  $parsed_client_phone=$client_phone;
  if( strlen($client_phone)===10 ) $client_phone=$phone_prefix.$client_phone;
  
  write_log('client phone: '.$client_phone, $amocrm_log_file);
  if( strlen($client_phone)<3 ) {
    write_log('client phone is too short', $amocrm_log_file);
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
    write_log('Phone station connection failed', $amocrm_log_file);
    $result.="failed: phone station connection failed ";
  }
 
  if( $connect_status!==false ) {
 
    $phone_connection->__submit_string("Action: Login");
    $phone_connection->__submit_string("Username: ".$user_phone_station);
    $phone_connection->__submit_string("Secret: ".$password_phone_station);
    $phone_connection->__submit_string("", 500000);

    $phone_connection->__submit_string("Action: Originate");
    $phone_connection->__submit_string("Channel: Local/".$user_phone."@from-internal");
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

    write_log('call to client: success', $amocrm_log_file);
    $result.="success ";
  }


   // Get contact by phone
   $client_contact=null;
   $client_contact_name=null;
   $client_company=null;
   $client_company_name=null;
   
   $parameters=array();
   $parameters['type']='contact';
   $parameters['query']=urlencode($parsed_client_phone);   
   $contacts_array=get_contact_info($parameters, $http_requester);
   
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      $client_contact=$value['contact_id'];
      $client_company=$value['company_id'];
      $client_contact_name=$value['name'];
      break;
   }
  
  // Get company by phone
  $companies_array=array();
  reset($contacts_array);
  while( list($key, $value)=each($contacts_array) ) {
      $companies_array[ intval($value['company_id']) ]=strval($value['company_id']);
  }
  
  if( is_null($client_contact) ) {
         
      $parameters=array();
      $parameters['type']='company';
      $parameters['query']=urlencode($parsed_client_phone);
      $companies_array=get_company_info($parameters, $http_requester);
      
      reset($companies_array);
      while( list($key, $value)=each($companies_array) ) {
	    $client_company=$value['company_id'];
	    $client_company_name=$value['name'];
	    break;
      }      
  
  }
  

  // Check if we need to create lead
  $create_lead=true;
  if( !is_null($client_company) ) { 
      $parameters=array();
      $parameters['type']='company';
      $parameters['id']=urlencode($client_company);   
      $companies_array=get_company_info($parameters, $http_requester);
      
      reset($companies_array);
      while( list($key, $value)=each($companies_array) ) {
	 
	 if( is_array($value)
	     && array_key_exists('create_lead', $value)
	     && gettype($value['create_lead'])==='boolean' ) {
	     
	    $create_lead=$value['create_lead'];
	    break;
	 }   
	    
      }      
      
  }
  
  
   // Get leads
   $leads_array=get_leads_info('', $http_requester);
   $lead_id=null;
   
   reset($leads_array);
   while( list($key, $value)=each($leads_array) ) {
      if( $value['status_id']!==$status_successful_realization
          && $value['status_id']!==$status_canceled
          && ( array_key_exists( intval($value['contact_id']), $contacts_array)
               || array_key_exists( intval($value['company_id']), $companies_array) ) ) {
          
	 $lead_id=$value['lead_id'];
	 break;
      }
   }
      

   // Create lead
   if( is_null($lead_id)
       && $create_lead===true ) {

      $name='Звонок ';
      if( !is_null($client_contact_name) ) {
	 $name.='клиенту '.strval($client_contact_name).' ';     
      }
      elseif( !is_null($client_company_name) ) {
	 $name.='в компанию '.strval($client_company_name).' ';      
      }
      
      $current_time_string=date('d.m.Y H:i');
      
      $name.='от '.$current_time_string;
      
      $return_result=create_lead($name, $status_accepted_for_work, $user_id, $client_company, $http_requester);     
      if( $return_result!==false ) {
   
	 $decoded_result=json_decode($return_result, true);
	 if( is_array($decoded_result)
	     && array_key_exists('response', $decoded_result)
	     && is_array($decoded_result['response'])
	     && array_key_exists('leads', $decoded_result['response'])
	     && is_array($decoded_result['response']['leads'])
	     && array_key_exists('add', $decoded_result['response']['leads'])
	     && is_array($decoded_result['response']['leads']['add'])
	     && count($decoded_result['response']['leads']['add'])>0 ) {
	     
	    write_log('lead is created', $amocrm_log_file);
	 }
	 else {
	    write_log('lead is not created: '.$return_result, $amocrm_log_file);	 
	 }
   
      }
   
   }

  
  echo $result;

?>
