<?php

   date_default_timezone_set('Etc/GMT-3');

   
   if( count($_REQUEST)===0 ) {

      if( count($argv)>1 ) $_REQUEST['param_login']=$argv[1];

   }
   
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
   
   require_once($amocrm_dir.'5c_amocrm_lib.php');
   require_once($current_dir_path.'amocrm_1C_mod.php');
   
   @$method=$_REQUEST['method'];
   
   if(!isset($method)) $method='';
   
   
   write_log('blank_line', $amocrm_log_file, 'AMO_QUERY');
   write_log('Start', $amocrm_log_file, 'AMO_QUERY');
   write_log($_REQUEST, $amocrm_log_file, 'AMO_QUERY');
   
   
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
      $http_requester->{'max_wait_time_for_lock_sec'}=30;
   }
   
   
   $result=array('result'=>'failed', 'error'=>'не определена');
   
   $common_fields=array();
   $custom_fields=array();

   reset($_REQUEST);
   
   while( list($key, $value)=each($_REQUEST) ) {
      
      if( is_array($value)
          && array_key_exists('type', $value)
          && array_key_exists('value', $value) ) {
      
          if( $value['type']==='custom' ) {
             $custom_fields[]=array('id'=>$key, 'values'=>array(array("value"=>$value['value'])));
          }
          elseif( $value['type']==='common' ) {
             $common_fields[$key]=$value['value'];
          }
             
      }
      
   }
   
   $lead_fields=array_merge($common_fields, array('custom_fields'=>$custom_fields));
   
   // Available methods
   if( $method==='change_status' ) {
      
      change_status($lead_fields, $http_requester, $result);
      
   }
   elseif( $method==='create_task' ) {
      
      create_task($lead_fields, $http_requester, $result);
      
   }
   elseif( $method==='get_client_codes' ) {

      get_client_codes($lead_fields, $http_requester, $result);

   }
   else {
      
      $result['error']='method '.$method.' не найден ';
      
   }
   
   $return_result=json_encode($result);
   
   write_log('result='.$return_result, $amocrm_log_file, 'AMO_QUERY');
   
   echo $return_result;
   
   write_log('Finish ', $amocrm_log_file, 'AMO_QUERY');

   
function change_status($lead_fields, $http_requester, &$result_array=array()) {
   
   $result=false;
   
   $update_status=false;
   if( array_key_exists('id', $lead_fields)
      && is_numeric($lead_fields['id'])
      && intVal($lead_fields['id'])>0 ) {
         
      $lead_id=strVal($lead_fields['id']);
      $parameters=array('id'=>$lead_id);
      $updated_fields=array(intVal($lead_id)=>$lead_fields);
      
      $update_status=update_leads_info($parameters, $updated_fields, $http_requester);
      if( $update_status===false ) {
         $result_array['error']='Ошибка запроса к амоЦРМ ';
      }
      
      $result=$update_status;
      
   }
   else {
      $result_array['error']='Идентификатор сделки не найден ';
   }
   
   if( $result===true ) {
      $result_array['result']='success';
      unset($result_array['error']);
   }
   else {
      $result_array['result']='failed';
   }
   
   return($result);   
}


function create_task($lead_fields, $http_requester, &$result_array=array()) {
   
   $result=false;
   
   return($result);
   
}


function get_client_codes($lead_fields, $http_requester, &$result_array=array()) {
   
   global $amocrm_1C_integration_contact_custom_field_client_code_1;
   global $amocrm_1C_integration_contact_custom_field_client_code_2;
   global $amocrm_1C_integration_contact_custom_field_client_code_3;
   global $amocrm_1C_integration_contact_custom_field_principal_client;
   
   global $amocrm_1C_integration_company_custom_field_client_code_1;
   global $amocrm_1C_integration_company_custom_field_client_code_2;
   global $amocrm_1C_integration_company_custom_field_client_code_3;
   global $amocrm_1C_integration_company_custom_field_principal_client;  
   
   
   $result=true;

   $leads_info=null;
   $contact_id='';
   $company_id='';
   if( array_key_exists('id', $lead_fields)
      && is_numeric($lead_fields['id'])
      && intVal($lead_fields['id'])>0 ) {
         
      $lead_id=strVal($lead_fields['id']);
      $parameters=array('id'=>$lead_id);
      
      $error_status=false;
      $leads_info=get_leads_info($parameters, $http_requester, null, null, null, null, null, $error_status);
      if( $error_status===true ) {
         $result_array['error']='get_client_codes: Ошибка запроса get_leads_info ';
         write_log($result_array['error'], $http_requester->log_file, 'AMO_QUERY');
         $result=false;
      }
      
   }
   else {
      $result_array['error']='get_client_codes: Идентификатор сделки не найден ';
      write_log($result_array['error'], $http_requester->log_file, 'AMO_QUERY');
      $result=false;
   }
   
   if( isset($leads_info)
       && is_array($leads_info)
       && count($leads_info)>0 ) {
    
       while( list($key, $value)=each($leads_info) ) {
          
          if( is_array($value)
              && array_key_exists('contact_id', $value) ) {
                 
              $contact_id=strVal($value['contact_id']);                
          }
          
          if( is_array($value)
              && array_key_exists('company_id', $value) ) {
                 
              $company_id=strVal($value['company_id']);                
          }          
          
          break;
       }
          
   }
   
   write_log('Search for contact, company: contact_id='.$contact_id.' company_id='.$company_id, $http_requester->log_file, 'AMO_QUERY');
   
   
   $data_info=array();
   
   $contacts_info=array();
   if( strlen($contact_id)>0
       && is_numeric($contact_id) ) {
       
       $error_status=false;
       $parameters=array();
       $parameters['id']=$contact_id;
       $parameters['type']='contact';
       
       $contacts_info=get_contact_info($parameters, $http_requester, null, null, null, null, null, $error_status);
       
      if( $error_status===true ) {
         $result_array['error']='get_client_codes: Ошибка запроса get_contact_info ';
         write_log($result_array['error'], $http_requester->log_file, 'AMO_QUERY');
         $result=false;
      }
               
   }
   
   $companies_info=array();
   if( strlen($company_id)>0
       && is_numeric($company_id) ) {
          
       $error_status=false;
       $parameters=array();
       $parameters['id']=$company_id;
       $parameters['type']='company';
       
       $companies_info=get_companies_info($parameters, $http_requester, null, null, null, null, null, $error_status);
       
      if( $error_status===true ) {
         $result_array['error']='get_client_codes: Ошибка запроса get_companies_info ';
         write_log($result_array['error'], $http_requester->log_file, 'AMO_QUERY');
         $result=false;
      }          
          
   }
   
   if( $result===true ) {
      
      $result_array['contact']['code_1C']=array('', '', '');
      $result_array['contact']['principal_client']='';
              
      $result_array['contact']['code_1C'][0]=get_code_1C_from_response($contacts_info, $amocrm_1C_integration_contact_custom_field_client_code_1);
      $result_array['contact']['code_1C'][1]=get_code_1C_from_response($contacts_info, $amocrm_1C_integration_contact_custom_field_client_code_2);
      $result_array['contact']['code_1C'][2]=get_code_1C_from_response($contacts_info, $amocrm_1C_integration_contact_custom_field_client_code_3);      
      $result_array['contact']['principal_client']=get_code_1C_from_response($contacts_info, $amocrm_1C_integration_contact_custom_field_principal_client);
      
         
      $result_array['company']['code_1C']=array('', '', '');
      $result_array['company']['principal_client']='';
      
      $result_array['company']['code_1C'][0]=get_code_1C_from_response($companies_info, $amocrm_1C_integration_company_custom_field_client_code_1);
      $result_array['company']['code_1C'][1]=get_code_1C_from_response($companies_info, $amocrm_1C_integration_company_custom_field_client_code_2);
      $result_array['company']['code_1C'][2]=get_code_1C_from_response($companies_info, $amocrm_1C_integration_company_custom_field_client_code_3);      
      $result_array['company']['principal_client']=get_code_1C_from_response($companies_info, $amocrm_1C_integration_company_custom_field_principal_client);      
      
   }
     
   if( $result===true ) {
      $result_array['result']='success';
      unset($result_array['error']);
   }
   else {
      $result_array['result']='failed';
   }   
   
   return($result);   
}


function get_code_1C_from_response($response_array, $field) {
   
   $result='';
   
   if( is_array($response_array)
       && isset($field) ) {
          
      $first_value_array=get_first_field_value($response_array, $field);
      if( count($first_value_array)>0 ) {
         reset($first_value_array);
         $result=strVal(current($first_value_array));
      }         
          
   } 
 
   return($result);
}
   
?>
