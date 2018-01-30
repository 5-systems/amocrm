<?php

   date_default_timezone_set('Etc/GMT-3');

   error_reporting(0);
   ini_set('display_errors', 0);
   
   if( count($_REQUEST)===0 ) {
       
       if( count($argv)>1 ) $_REQUEST['CallerNumber']=$argv[1];
       if( count($argv)>2 ) $_REQUEST['param_login']=$argv[2];
       
   }
   
   @$CallerNumber=$_REQUEST['CallerNumber'];
   
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
       
   require_once('5c_amocrm_lib.php');
   require_once('5c_std_lib.php');
  
   $result='';
   $result.='<type>0</type>';
   $result.='<number></number>';
   $result.='<name>Первичный клиент</name>';   

   write_log('blank_line', $amocrm_log_file, 'GET_CALL_TYPE');   
   write_log($_REQUEST, $amocrm_log_file, 'GET_CALL_TYPE');   
   
   // Create http_requester
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
       $http_requester->{'lock_priority'}=-10;
   }
   
   // Get contact by phone
   $client_phone=remove_symbols($CallerNumber);
   $client_phone=substr($client_phone, -10);
   $parsed_client_phone=$client_phone;
   
   if( strlen($parsed_client_phone)<3 ) exit($result); 

   $client_contact=null;
   $client_company=null;
   $client_name='';
   $redirection_type='0';
   
   $contacts_array=array();
   $parameters=array();
   $parameters['type']='contact';
   $parameters['query']=urlencode($parsed_client_phone);   
   $contacts_array=get_contact_info($parameters, $http_requester);
   
   // Additional filter by phone
   $contacts_array_tmp=array();
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
       if( is_array($value)
           && array_key_exists('custom_fields', $value)
           && is_array($value['custom_fields']) ) {
           
           $phone_is_found_in_contact=false;
           $custom_fields_array=$value['custom_fields'];
           reset($custom_fields_array);
           while( list($key_2, $value_2)=each($custom_fields_array) ) {
               if( is_array($value_2)
                   && array_key_exists('id', $value_2)
                   && strVal($value_2['id'])===strVal($custom_field_phone_id)
                   && array_key_exists('values', $value_2)
                   && is_array($value_2['values']) ) {
                 
                   $phone_values=$value_2['values'];
                   reset($phone_values);
                   foreach($phone_values as $value_3) {
                       if( is_array($value_3)
                           && array_key_exists('value', $value_3)
                           && strpos( remove_symbols($value_3['value']), $parsed_client_phone)!==false ) {
                           
                           $contacts_array_tmp[$key]=$value;
                           $phone_is_found_in_contact=true;
                           break;
                       }
                       
                   }
               }
               
               
               if( $phone_is_found_in_contact===true ) break;
           }           
       }
   }
   
   $contacts_array=$contacts_array_tmp;
   
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      $client_contact=$value['contact_id'];
      $client_name=$value['name'];
      
      if( strlen($value['company_id'])>0 ) {
	       $client_company=$value['company_id'];
      }

      break;
   }   
   
   $companies_array=array();
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      if( strlen($value['company_id'])>0 ) $companies_array[ intval($value['company_id']) ]=strval($value['company_id']);
   }
 
   write_log('Search for contact, contact_id='.$client_contact.', company_id='.$client_company, $amocrm_log_file, 'GET_CALL_TYPE');
   
   // Get companies by phone
   $companies_array_from_request=null;
   $client_company_from_request=null;
       
   $parameters=array();
   $parameters['type']='company';
   $parameters['query']=urlencode($parsed_client_phone);
   $companies_array_from_request=get_companies_info($parameters, $http_requester);
   
   reset($companies_array_from_request);
   while( list($key, $value)=each($companies_array_from_request) ) {
       if( is_numeric($value['company_id'])
           && strlen($value['company_id'])>0 ) {
               
           $companies_array[ intval($value['company_id']) ]=strval($value['company_id']);
           
           if( strlen($client_name)===0 ) $client_name=$value['name'];
           
           if( !isset($client_company_from_request)
               && strlen($value['company_id'])>0 ) {
                   
               $client_company_from_request=$value['company_id'];
           }
           
       }
       
   }
   
   write_log('Search for company, company_id='.$client_company_from_request, $amocrm_log_file, 'GET_CALL_TYPE');
   
   // Search user_id in leads
   $user_id=null;
   $lead_id=null;
   if( count($contacts_array)>0
       || count($companies_array)>0 ) {
   
        $get_leads_from_date=date('d M Y H:i:s', time()-60*60*24*30);
        $http_requester->{'header'}=array('if-modified-since: '.$get_leads_from_date);
        $leads_array=get_leads_info('', $http_requester);

        $leads_array_for_sort=array();
        reset($leads_array);
        while( list($key, $value)=each($leads_array) ) {
           if( array_key_exists('date_create', $value) ) $leads_array_for_sort[$key]=$value['date_create'];
        }

        if( count($leads_array_for_sort)===count($leads_array) ) {
            array_multisort($leads_array_for_sort, SORT_DESC, $leads_array);
        }

        if( !isset($user_id) ) {
            
            reset($leads_array);
            while( list($key, $value)=each($leads_array) ) {
               if( $value['status_id']!==$status_successful_realization
                   && $value['status_id']!==$status_canceled
                   && array_key_exists( intval($value['contact_id']), $contacts_array) ) {
    
                  if( is_numeric($value['user_id'])
                      && strlen($value['user_id'])>0 ) {
    
                     $user_id=$value['user_id'];
                     $lead_id=$value['lead_id'];
                     $redirection_type='1';
                     break;
                  }
               }
            }
        
        }
        
        if( !isset($user_id) ) {
            
            reset($leads_array);
            while( list($key, $value)=each($leads_array) ) {
                if( $value['status_id']!==$status_successful_realization
                    && $value['status_id']!==$status_canceled
                    && array_key_exists( intval($value['company_id']), $companies_array) ) {
                        
                    if( is_numeric($value['user_id'])
                        && strlen($value['user_id'])>0 ) {
                            
                        $user_id=$value['user_id'];
                        $lead_id=$value['lead_id'];
                        $redirection_type='1';
                        break;
                    }
                }
            }
            
        }
               
        $http_requester->{'header'}='';
   }
   
   if( !is_null($user_id) ) {
      write_log('User_id found from lead='.$lead_id.', user_id='.$user_id, $amocrm_log_file, 'GET_CALL_TYPE');
   }
  
   // Search user_id in contacts
   if( is_null($user_id) ) {
   
      reset($contacts_array);
      while( list($key, $value)=each($contacts_array) ) {
	 if( is_numeric($value['user_id'])
	    && strlen($value['user_id'])>0 ) {
	    
	    $user_id=$value['user_id'];
	    $client_name=$value['name'];
	    $redirection_type='2';
	 }
      }
      
      if( !is_null($user_id) ) {
	 write_log('User_id found from contact, user_id='.$user_id, $amocrm_log_file, 'GET_CALL_TYPE');
      }      
   
   }
   
   // Search companies by phone
   if( is_null($user_id) ) {

      $companies_array=$companies_array_from_request;
      
      reset($companies_array);
      while( list($key, $value)=each($companies_array) ) {
    	 if( is_numeric($value['user_id'])
                 && strlen($value['user_id'])>0 ) {
                 
    	    $user_id=$value['user_id'];
    	    $client_name=$value['name'];
    	    $redirection_type='3';
    	    break;
    	 }
      }

      if( !is_null($user_id) ) {      
    	 write_log('User_id found in company, user_id='.$user_id, $amocrm_log_file, 'GET_CALL_TYPE');
      }
    
   }
  
   if( !is_null($user_id)
       && strlen($user_id)>0 ) {
   
      $user_phone=get_user_internal_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester);
      
      $result='';
      $result.='<type>'.$redirection_type.'</type>';
      $result.='<number>'.$user_phone.'</number>';
      $result.='<name>'.$client_name.'</name>';
   }
   
   // close connection to db
   if( isset($db_conn) ) {
      $db_conn->close();
   }
   
   echo $result;   

?>
