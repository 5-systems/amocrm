<?php

  require_once('5c_files_lib.php');
  require_once('5c_amocrm_lib.php');
  require_once('5c_std_lib.php');
  require_once('amocrm_settings.php');

  date_default_timezone_set('Etc/GMT-3');

//  error_reporting(E_ALL);
//  ini_set('display_errors', 1);

  @$date=$_REQUEST['date'];
  @$company_number=$_REQUEST['company_number'];
  @$company_name=$_REQUEST['company_name'];
  @$user_id=$_REQUEST['user_id'];
  @$text=$_REQUEST['text'];
  
  
  write_log('blank_line', $amocrm_log_file, 'CREATE NOTE');
  write_log($_REQUEST, $amocrm_log_file, 'CREATE NOTE');  
  
  $result='';
  $result_array=array();
  $result_array['result']=false;  
  
  $date_unix=time();
  if( strlen($date)!==14 ) {
     $result_array['error']='Неправильный формат даты';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
  
  if( strlen($company_number)===0 ) {        
     $result_array['error']='Неправильный номер компании';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
  
  if( !is_string($company_name) ) $company_name='';
 
  if( !is_numeric($user_id) ) $user_id=0;
  
  if( !is_string($text) ) $text='';
  
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
     $http_requester->{'lock_priority'}=30;
     $http_requester->{'time_interval_between_lock_tries_sec'}=1;
     $http_requester->{'max_wait_time_for_lock_sec'}=100;
  }
  
  // Get company_id
  $company_id=0;  
  $companies_array=array();
  
  $parameters=array();
  $parameters['type']='company';
  
  if( strlen($company_number)>=3 ) {
     $parameters['query']=strVal($company_number);    
  }
  elseif( strlen($company_name)>=3 ) {
     $parameters['query']=strVal($company_name);
  }
  
  $http_requester->{'send_method'}='GET';
  $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
  $http_requester->{'parameters'}=$parameters;
  
  $return_result=$http_requester->request();
  
  if( $return_result===false ) {
     $result_array['error']='Не удалось выбрать компании';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
      
  $decoded_result=json_decode($return_result, true);
  if( is_array($decoded_result)
     && array_key_exists('response', $decoded_result)
     && is_array($decoded_result['response'])
     && array_key_exists('contacts', $decoded_result['response'])
     && is_array($decoded_result['response']['contacts'])
     && count($decoded_result['response']['contacts'])>0 ) {
        
     $companies=$decoded_result['response']['contacts'];
     reset($companies);
     while( list($key, $value)=each($companies) ) {
        
        $custom_fields_array=$value['custom_fields'];
        reset($custom_fields_array);
        while( list($key_2, $value_2)=each($custom_fields_array) ) {
           if( is_array($value_2)
               && array_key_exists('id', $value_2)
               && strVal($value_2['id'])===strVal($custom_field_company_number)
               && array_key_exists('values', $value_2)
               && is_array($value_2['values']) ) {
                 
                 $field_values=$value_2['values'];
                 reset($field_values);
                 foreach($field_values as $value_3) {
                    if( is_array($value_3)
                        && array_key_exists('value', $value_3)
                        && Trim($value_3['value'])===$company_number ) {
                          
                          $companies_array[]=intVal($value['id']);
                       }
                       
                 }
              }

        }
        
     }
     
  }
        
  if( count($companies_array)===0 ) {
     $result_array['error']='Компания не найдена';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
  
  if( count($companies_array)>1 ) {
     $result_array['error']='Найдено несколько компаний:';
     
     reset($companies_array);
     while( list($key, $value)=each($companies_array) ) {
        $result_array['error'].=' '.strVal($value);
     }
     
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
  
  if( count($companies_array)>0 ) $company_id=$companies_array[0];
  
  // Set note
  $fields=array();
  
  $date_unix=strtotime($date);
  if( !is_numeric($date_unix)
     || intVal($date_unix)<=0 ) {
        
     $result_array['error']='Неверный формат даты';
     
     reset($companies_array);
     while( list($key, $value)=each($companies_array) ) {
        $result_array['error'].=' '.strVal($value);
     }
     
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'CREATE NOTE');
     exit($result);
  }
  
  $fields['date_create']=$date_unix;
  $fields['last_modified']=$date_unix;
  
  $fields['created_user_id']=$user_id;
  $fields['responsable_user_id']=$user_id;
  
  $error_status=false;
  $create_status=create_note(null, null, null, $company_id, $text,
                              null, null, $fields, $http_requester,
                              null, null, null, null, null, $error_status);
  
  $result_array=array();
  $result_array['result']=!$error_status;
  $result=json_encode($result_array);

  write_log($result, $amocrm_log_file, 'CREATE NOTE');
  
  echo $result;
  
?>