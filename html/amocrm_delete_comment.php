<?php

  require_once('5c_files_lib.php');
  require_once('5c_amocrm_lib.php');
  require_once('5c_std_lib.php');
  require_once('amocrm_settings.php');

  date_default_timezone_set('Etc/GMT-3');

//  error_reporting(E_ALL);
//  ini_set('display_errors', 1);

  if( count($_REQUEST)===0 ) {
     
     if( count($argv)>1 ) $_REQUEST['start_date']=$argv[1];
     if( count($argv)>2 ) $_REQUEST['finish_date']=$argv[2];
     if( count($argv)>3 ) $_REQUEST['company_number']=$argv[3];
     if( count($argv)>4 ) $_REQUEST['company_name']=$argv[4];
     
  }
  
  @$start_date=$_REQUEST['start_date'];
  @$finish_date=$_REQUEST['finish_date'];
  @$company_number=$_REQUEST['company_number'];
  @$company_name=$_REQUEST['company_name'];

  
  write_log('blank_line', $amocrm_log_file, 'DELETE NOTE');
  write_log($_REQUEST, $amocrm_log_file, 'DELETE NOTE');  
  
  $result='';
  $result_array=array();
  $result_array['result']=false;  
  
  $date_unix=time();
  if( strlen($start_date)!==14 ) {
     $result_array['error']='Неправильный формат даты начала';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'DELETE NOTE');
     exit($result);
  }
  
  if( strlen($finish_date)!==14 ) {
     $result_array['error']='Неправильный формат даты конца';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'DELETE NOTE');
     exit($result);
  }
  
  if( strlen($company_number)===0 ) {        
     $company_number='';
  }
  
  $http_requester=new amocrm_http_requester;
  $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
  $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
  $http_requester->{'amocrm_account'}=$amocrm_account;
  $http_requester->{'coockie_file'}=$amocrm_coockie_file;
  $http_requester->{'log_file'}=$amocrm_log_file;
  $http_requester->{'max_number_request_cycles'}=101;
  
  if( isset($amocrm_sleep_time_after_request_microsec)
      && is_numeric($amocrm_sleep_time_after_request_microsec)
      && intVal($amocrm_sleep_time_after_request_microsec)>0 ) {
        
     $http_requester->{'sleep_time_after_request_microsec'}=$amocrm_sleep_time_after_request_microsec;
  }
  
  $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
  if( isset($db_conn) ) {
     
     $db_conn->autocommit(true);
     $http_requester->{'lock_database_connection'}=$db_conn;
     $http_requester->{'lock_priority'}=-20;
  }
  
  // Get company_id
  $company_id=0;  
  $companies_array=array();
  
  if( strlen($company_number)>0 ) {
  
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
        write_log($result, $amocrm_log_file, 'DELETE NOTE');
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
                        && strVal($value_3['value'])===strVal($company_number) ) {
                          
                        $companies_array[]=intVal($value['id']);
                    }
                       
                 }
                 
              }
   
           }
           
        }
        
     }
     
  }   
        
  
  if( count($companies_array)>1 ) {
     $result_array['error']='Найдено несколько компаний:';
     
     reset($companies_array);
     while( list($key, $value)=each($companies_array) ) {
        $result_array['error'].=' '.strVal($value);
     }
     
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'DELETE NOTE');
     exit($result);
  }
  
  if( count($companies_array)>0 ) $company_id=$companies_array[0];
  
  // Erase notes
  $start_date_unix=strtotime($start_date);
  if( !is_numeric($start_date_unix)
      || $start_date_unix<=0 ) {
  
      $result_array['error']='Неправильный формат даты начала';
      $result=json_encode($result_array);
      write_log($result, $amocrm_log_file, 'DELETE NOTE');
      exit($result);
  }
  
  $finish_date_unix=strtotime($finish_date);
  if( !is_numeric($finish_date_unix)
      || $finish_date_unix<=0 ) {
        
     $result_array['error']='Неправильный формат даты конца';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'DELETE NOTE');
     exit($result);
  }
  
  $notes_array=array();
  $notes_array_type_params=array();
  
  $parameters=array();
  $parameters['type']='company';
  
  $http_requester->{'send_method'}='GET';
  $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/list';
  $http_requester->{'parameters'}=$parameters;
  
  $get_notes_from_date=date('d M Y H:i:s', $start_date_unix);
  $http_requester->{'header'}=array('if-modified-since: '.$get_notes_from_date);
  
  $return_result=$http_requester->request();
  
  if( $return_result===false ) {
     $result_array['error']='Не удалось выбрать заметки';
     $result=json_encode($result_array);
     write_log($result, $amocrm_log_file, 'DELETE NOTE');
     exit($result);
  }
  
  $decoded_result=json_decode($return_result, true);
  if( is_array($decoded_result)
     && array_key_exists('response', $decoded_result)
     && is_array($decoded_result['response'])
     && array_key_exists('notes', $decoded_result['response'])
     && is_array($decoded_result['response']['notes'])
     && count($decoded_result['response']['notes'])>0 ) {
        
     $elements=$decoded_result['response']['notes'];
     reset($elements);
     while( list($key, $value)=each($elements) ) {
        
        if( is_array($value)
           && array_key_exists('date_create', $value)
           && is_numeric($value['date_create'])
           && intVal($value['date_create'])>=$start_date_unix
           && intVal($value['date_create'])<=$finish_date_unix
           && ( intVal($company_id)===0
                || is_numeric($value['element_id']) 
                   && intVal($value['element_id'])===$company_id )
           && array_key_exists('text', $value)
           && strVal($value['text'])!=='-') {
                      
           $notes_array[]=intVal($value['id']);
        }
        
        if( is_array($value)
           && array_key_exists('date_create', $value)
           && is_numeric($value['date_create'])
           && intVal($value['date_create'])>=$start_date_unix
           && intVal($value['date_create'])<=$finish_date_unix
           && ( intVal($company_id)===0
              || is_numeric($value['element_id'])
              && intVal($value['element_id'])===$company_id )
           && array_key_exists('params', $value)
           && array_key_exists('text', $value['params'])
           && strVal($value['params']['text'])!=='-') {
              
           $notes_array_type_params[]=intVal($value['id']);
        }        
                
     }
     
  }
       
  
  // Update notes
  $error_status=false;
  
  // Delete orinary notes
  $parameters=array();
  $parameters['id']=$notes_array;
  $parameters['type']='company';
  
  $fields=array();
  
  reset($notes_array);
  while( list($key, $value)=each($notes_array) ) {
     $fields[intVal($value)]=array('text'=>'-');
  }
  
  reset($notes_array);
  while( list($key, $value)=each($notes_array) ) {
     write_log('ord note='.$value, $amocrm_log_file, 'DELETE NOTE');
  }
  
  
  if( count($notes_array)>0 ) {
     $update_status=update_notes_info($parameters, $fields, $http_requester,
        null, null, null, null, null, $error_status);
  }
  
  // Delete system notes
  $parameters=array();
  $parameters['id']=$notes_array_type_params;
  $parameters['type']='company';
  
  $fields=array();
  
  reset($notes_array_type_params);
  while( list($key, $value)=each($notes_array_type_params) ) {
     $fields[intVal($value)]=array('params'=>array('text'=>'-'));
  }
  
  reset($notes_array_type_params);
  while( list($key, $value)=each($notes_array_type_params) ) {
     write_log('sys note='.$value, $amocrm_log_file, 'DELETE NOTE');
  }
  
  
  if( count($notes_array_type_params)>0 ) {
     $update_status=update_notes_info($parameters, $fields, $http_requester,
        null, null, null, null, null, $error_status);
  }
  
  
  $result_array=array();
  $result_array['result']=!$error_status;
  $result=json_encode($result_array);
  
  write_log($result, $amocrm_log_file, 'DELETE NOTE');
  
  echo $result;
  
?>
