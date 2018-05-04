<?php

   date_default_timezone_set('Etc/GMT-3');

   
   $settigs_found=false;
   if( isset($_REQUEST['param_login'])
      && strlen($_REQUEST['param_login'])>0 ) {
         
      $current_dir_path=getcwd();
      $current_dir_path=rtrim($current_dir_path, '/').'/';
      
      $settings_file_path=$current_dir_path.'amocrm_settings_'.strVal($_REQUEST['param_login']).'.php';
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
   
   
   @$CallId=$_REQUEST['CallId'];
   @$CallerNumber=$_REQUEST['CallerNumber'];
   @$CalledNumber=$_REQUEST['CalledNumber'];   
   @$CallDate=$_REQUEST['CallDate'];
   @$ContactInfo=$_REQUEST['ContactInfo'];
   @$MissedCall=$_REQUEST['MissedCall'];
   @$OutcomingCall=$_REQUEST['Outcoming'];
   @$FromWeb=$_REQUEST['FromWeb'];
   @$Link=$_REQUEST['Link'];
   @$FirstCalledNumber=$_REQUEST['FirstCalledNumber'];
   @$Account=$_REQUEST['Account'];
   @$login=$_REQUEST['param_login'];
   @$password=$_REQUEST['param_password'];
   @$Comment=$_REQUEST['Comment'];
   @$WebPage=$_REQUEST['WebPage'];
   @$Department=$_REQUEST['Department'];

     
   if(!isset($CallId)) $CallId='';
   if(!isset($CallerNumber)) $CallerNumber='';
   if(!isset($CalledNumber)) $CalledNumber='';
   if(!isset($CallDate)) $CallDate='';
   if(!isset($ContactInfo)) $ContactInfo='';
   if(!isset($MissedCall)) $MissedCall='0';
   if(!isset($OutcomingCall)) $OutcommingCall='0';
   if(!isset($FromWeb)) $FromWeb='0';
   if(!isset($Link)) $Link='';
   if(!isset($FirstCalledNumber)) $FirstCalledNumber='';
   if(!isset($Account)) $Account='';
   if(!isset($login)) $login='';
   if(!isset($password)) $password='';
   if(!isset($Comment)) $Comment='';
   if(!isset($WebPage)) $WebPage='';
   if(!isset($Department)) $Department='';
   
   
   $LogLineId=$CallId;

   $current_time=time();
   write_log('blank_line', $amocrm_log_file, 'REG_CALL '.$LogLineId);
   write_log('Start ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
   write_log($_REQUEST, $amocrm_log_file, 'REG_CALL '.$LogLineId);

   
   // Get user_id and user_name
   $user_id='';
   $user_name='';
   $user_pipeline_id='';
   $user_pipeline_status_id='';
   
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'log_file'}=$amocrm_log_file;
   $http_requester->{'custom_field_phone_id'}=$custom_field_phone_id;
   
   if( isset($amocrm_sleep_time_after_request_microsec)
       && is_numeric($amocrm_sleep_time_after_request_microsec)
       && intVal($amocrm_sleep_time_after_request_microsec)>0 ) {

       $http_requester->{'sleep_time_after_request_microsec'}=$amocrm_sleep_time_after_request_microsec;
   }
   
   $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
   
   if ( strlen($db_conn->connect_error)>0 ) {
      $result_message=$db_conn->connect_error;
      write_log('Connection to database is failed: '.$result_message, $amocrm_log_file, 'REG_CALL '.$LogLineId);     
   }
   
   if( isset($db_conn) ) {
       
       $db_conn->autocommit(true);
       $http_requester->{'lock_database_connection'}=$db_conn;
       $http_requester->{'lock_priority'}=0;
       $http_requester->{'max_wait_time_for_lock_sec'}=20;
   }

   $user_phone=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   $user_info=null;     
   if( isset($responsible_users_by_first_called_number)
       && is_array($responsible_users_by_first_called_number) ) {
         
      $user_id='';
      $first_called_number_parsed=remove_symbols($FirstCalledNumber);
      $first_called_number_parsed=substr($first_called_number_parsed, -10);
      
      if( strlen($user_id)===0
          && strlen($first_called_number_parsed)>0
          && array_key_exists($first_called_number_parsed, $responsible_users_by_first_called_number) ) {
            
         $user_id=$responsible_users_by_first_called_number[$first_called_number_parsed];
      }
      
      if( strlen($user_id)===0
          && strlen($Department)>0
          && array_key_exists($Department, $responsible_users_by_first_called_number) ) {
            
         $user_id=$responsible_users_by_first_called_number[$Department];
      }
      
      if( is_numeric($user_id)
          && intVal($user_id)>0 ) {
             
          $error_status=false;
          $user_info=get_user_info_by_user_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_amo_crm, $http_requester,
                                                   null, null, null, null, null, $error_status);
          
          if( $error_status===true ) {
             write_log('Search for user: request error', $amocrm_log_file, 'REG_CALL '.$LogLineId);
             exit;
          }
             
      }
            
   }
   
   if( strlen($user_id)===0
       && strlen($user_phone)>0 ) {
         
      $error_status=false;
      $user_info=get_user_info_by_user_phone($user_phone, $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester,
         null, null, null, null, null, $error_status);
      
      if( $error_status===true ) {
         write_log('Search for user: request error', $amocrm_log_file, 'REG_CALL '.$LogLineId);
         exit;
      }
         
   }
   
   
   if( is_array($user_info) ) {
 	  if( array_key_exists('user_id', $user_info) ) $user_id=$user_info['user_id'];
 	  if( array_key_exists('name', $user_info) ) $user_name=$user_info['name'];
 	  if( array_key_exists('pipeline_id', $user_info) && is_numeric($user_info['pipeline_id']) ) $user_pipeline_id=$user_info['pipeline_id'];
 	  if( array_key_exists('pipeline_status_id', $user_info) && is_numeric($user_info['pipeline_status_id']) ) $user_pipeline_status_id=$user_info['pipeline_status_id'];
   }     

   write_log('Search for user: user_id='.$user_id.' user_name='.$user_name, $amocrm_log_file, 'REG_CALL '.$LogLineId);  
  
   
   // Get contact by phone
   $client_contact=null;
   $client_contact_name=null;
   $client_company=null;
   $client_company_name=null;
   
   $client_phone=remove_symbols(($OutcomingCall==='1' ? $CalledNumber: $CallerNumber));
   $client_phone=substr($client_phone, -10);
   $parsed_client_phone=$client_phone;
   
   $error_status=false;
   $contacts_array=get_contacts_local($http_requester, $client_phone, $error_status, $LogLineId);
   
   if( $error_status===true ) {
      write_log('get_contacts_local failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;
   }
   
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      $client_contact=$value['contact_id'];
      $client_contact_name=$value['name'];
     
      if( strlen($value['company_id'])>0
         && is_numeric($value['company_id'])
         && intval($value['company_id'])>0 ) $client_company=$value['company_id'];
      
      break;
   }
  
   write_log('Search for contact, contact_id='.$client_contact.', company_id='.$client_company, $amocrm_log_file, 'REG_CALL '.$LogLineId);
   
   
   // Get company by phone
   $companies_array=array();    
   $companies_array_from_request=get_companies_local($http_requester, $client_phone, $error_status, $LogLineId);
    
   if( $error_status===true ) {
      write_log('get_company_info (phone) failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;
   }
    
   reset($companies_array_from_request);
   while( list($key, $value)=each($companies_array_from_request) ) {
      
      if( is_null($client_contact) ) {
          $client_company=$value['company_id'];
          $client_company_name=$value['name'];
      }    
          
      break;
   }
   
   reset($companies_array_from_request);
   while( list($key, $value)=each($companies_array_from_request) ) {
      
      if( is_numeric($value['company_id'])
          && strlen($value['company_id'])>0 ) {
              
          $companies_array[ intval($value['company_id']) ]=$value;                 
      }
          
   }
    
   write_log('Search for company, company_id='.$client_company, $amocrm_log_file, 'REG_CALL '.$LogLineId);  

   
   // Create contact
   $contact_created=false;
   $contact_data=array();
   
   $name_create_contact=strVal($ContactInfo);
   
   if( strlen($ContactInfo)>0
       && strlen($parsed_client_phone)>0 ) {
          
       $name_create_contact.=' ';
   }
   
   $name_create_contact.=$parsed_client_phone;
   
   $contact_data['name']=$name_create_contact;
   $contact_data['phone']=$parsed_client_phone;
   $contact_data['email']='';
   $contact_data['user_id']=$user_id;
   
   $error_status=false;
   $created_contact_data=create_contact_local($http_requester, $contact_data, $user_id,
                                              $contacts_array, $companies_array, $error_status, $LogLineId);
   
   if( $error_status===true ) {
      write_log('create_contact_local: create contact failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;
   }
   
   if( is_array($created_contact_data)
       && array_key_exists('contact', $created_contact_data) ) {
       
       $created_contact_data_array=$created_contact_data['contact'];
       if( array_key_exists('id', $created_contact_data_array) ) {   
          $client_contact=strVal($created_contact_data_array['id']);
          $contact_created=true;
       }
       
       if( array_key_exists('name', $created_contact_data_array) ) {
          $client_contact_name=strVal($created_contact_data_array['name']);
       }
   }
   
   if( is_array($created_contact_data)
      && array_key_exists('company', $created_contact_data) ) {
         
      $created_company_data_array=$created_contact_data['company'];
      if( array_key_exists('id', $created_company_data_array) ) {
         $client_company=strVal($created_company_data_array['id']);
         $contact_created=true;
      }
      
      if( array_key_exists('name', $created_company_data_array) ) {
         $client_company_name=strVal($created_company_data_array['name']);
      }
   }

   
   // Create call note
   $call_unix_time=0;
   if( strlen($CallDate)===14 ) $call_unix_time=strtotime($CallDate);
   
   $call_result='';
   $call_status='4';
   if( $MissedCall==='1' ) {
      $call_result='нет';
      $call_status='6';
   }
   elseif( $MissedCall==='0' ) {
      $call_result='да';
      $call_status='4';
   }
   
   $outcoming_call=false;
   if( $OutcomingCall==='1' ) $outcoming_call=true;
   
   $record_link='';
   if( strlen($Link)>0 ) {
      $record_link=$url_records;
      if( substr($record_link, -1)==='/' || substr($record_link, -1)==='\\' ) $record_link=substr($record_link, 0, strlen($record_link)-1);
      $record_link.='/'.$Link;
   }
   elseif( isset($url_get_record_phone_station)
           && strlen($url_get_record_phone_station)>0 ) {
      
      $record_link=$url_get_record_phone_station;
      $record_link.='?id='.$CallId;
   }
   
   $call_note_id='';
   $error_status=false;
   if( (is_numeric($client_contact)
        && intVal($client_contact)>0)
        || (is_numeric($client_company)
            && intVal($client_company)>0) ) {
          
      $call_note_id=create_note_call($http_requester, null, $client_contact, $client_company,
                                      $CallId, $parsed_client_phone, $call_unix_time, $call_status, $call_result,
                                      0, $outcoming_call, 0, $user_id, $record_link, null, $error_status);
   }
   
   if( $error_status===true ) {
      write_log('create_note_call failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;
   }
   
   
   // Check if we need to create lead
   $create_lead=true;
   $error_status=false;
   $create_lead=need_create_lead_for_company_local($http_requester, $client_company, $error_status, $LogLineId);
        
   if( $error_status===true ) {
      write_log('need_create_lead_for_company_local (company_id) failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;
   }
 
   write_log('Check if leads are created for the company: '.($create_lead===true ? 'yes': 'no') , $amocrm_log_file, 'REG_CALL '.$LogLineId);
   
   // Get leads
   $lead_id=null;
   $time_lead_modified_from=time()-60*60*24*30;
   
   if( strlen($call_note_id) ) {
      
      $lead_id_found=get_leads_local($http_requester, $contacts_array, $companies_array, $time_lead_modified_from, $error_status, $LogLineId);
      if( is_numeric($lead_id_found)
          && intVal($lead_id_found)>0 ) {
      
          $lead_id=intVal($lead_id_found);
      }
   
   }
         
   if( !is_null($lead_id) ) {
      write_log('Lead is found, lead_id='.$lead_id, $amocrm_log_file, 'REG_CALL '.$LogLineId);
   }

   
   // Create lead or unsorted
   $lead_created=false;
   $unsorted_created=false;
   $unsorted_id=null;
   
   $user_phone=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   $client_phone=$parsed_client_phone;
   
   $client_web_request='Комментарий: '.strVal($Comment).' ';
   $client_web_request.='Имя: '.strVal($ContactInfo).' ';
   $client_web_request.='Телефон: '.strVal($client_phone);
   
   $client_web_site=strVal($WebPage);
   
   if( is_null($lead_id)
       && strlen($call_note_id)>0
       && $create_lead===true ) {
       
      if( strlen($user_id)>0 ) {
          
          $additional_custom_fields_values=$_REQUEST;
         
          // Create lead
          $lead_id=create_lead_local($http_requester, $user_id,
                                     $client_contact_name, $client_company,
                                     $client_company_name, $amocrm_log_file,
                                     $OutcomingCall, $parsed_client_phone,
                                     $MissedCall, $FromWeb, $FirstCalledNumber,
                                     $client_web_request, $client_web_site, $user_pipeline_status_id,
                                     $additional_custom_fields, $additional_custom_fields_values);
          
          if( strlen($lead_id)>0 ) {
              $lead_created=true;
          }
          else {
              write_log('create_lead_local failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
              exit;
          }
          
      }
      elseif( strlen($user_phone)===0 ) {
          
          // Create unsorted
          $phone_from=( $OutcomingCall==='1' ? $CallerNumber : $phone_prefix_presentation.$parsed_client_phone );
          $phone_to=( $OutcomingCall==='1' ? $phone_prefix_presentation.$parsed_client_phone : $CalledNumber );
          
          $additional_custom_fields_values=$_REQUEST;
          
          $unsorted_id=create_unsorted_local($http_requester,
                                             $phone_from, $phone_to, 
                                             $user_id, $client_contact,
                                             $client_contact_name, $client_company,
                                             $client_company_name, $amocrm_log_file,
                                             $OutcomingCall, $MissedCall, $FromWeb,
                                             $FirstCalledNumber, $client_web_request,
                                             $client_web_site, $ContactInfo, $user_pipeline_id,
                                             $additional_custom_fields, $additional_custom_fields_values);

          
          if( strlen($unsorted_id)>0 ) {
              $unsorted_created=true;
          }
          else {
              write_log('create_unsorted_local failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
              exit;
          }
      }
      

      // Attach lead to contact
      if( strlen($client_contact)>0
          && strlen($lead_id)>0
          && is_numeric($lead_id) ) {
          
    	 $parameters=array("id"=>$client_contact);
    	 $updated_fields=array("linked_leads_id"=>array( intVal($lead_id) ));
    	 
    	 $error_status=false;
    	 $update_result=update_contact_info($parameters, $updated_fields, $http_requester, null, null, null, null, null, $error_status);
    	 
    	 if( $error_status===true ) {
    	     write_log('update_contact_info failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
    	     exit;
    	 }
    	 
    	 if( $update_result===false ) {
    	    write_log('Lead is not attached to contact, lead_id='.$lead_id.', contact_id='.$client_contact, $amocrm_log_file, 'REG_CALL '.$LogLineId);
    	 }
	 
      }
   
   }
   
   // Notification 
   $create_notification_status=false;
   if( strlen($call_note_id)>0 ) {
      $create_notification_status=create_notification($db_conn, $user_phone, $user_id, $user_name, $lead_id,
                                             $contact_created, $lead_created, $CallId, $parsed_client_phone,
                                             $client_contact_name, $client_company_name, $OutcomingCall,
                                             $MissedCall, $record_link, $amocrm_log_file, $LogLineId);
   }
   
   if( $create_notification_status===false ) {
      write_log('create_notification failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
      exit;    
   }
      
   echo $CallId;

   // close connection to db
   if( isset($db_conn) ) {
      $db_conn->close();
   }
   
   write_log('Finish ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
   

function create_notification($db_conn, $user_phone, $user_id, $user_name, $lead_id,
                             $contact_created, $lead_created, $CallId, $client_phone,
                             $client_contact_name, $client_company_name, $OutcomingCall,
                             $MissedCall, $record_link, $log_file, $LogLineId) {
   
   global $amocrm_database_host;
   global $amocrm_database_port;
   global $amocrm_database_name;   
   global $amocrm_users; 
   
   $result=false;
   
   $current_time=time();
   $current_date=date('Y-m-d H:i:s', $current_time);
   
   $db_host=$amocrm_database_host;
   if( strlen($amocrm_database_port)>0 ) $db_host.=':'.$amocrm_database_port;
   
   if( !isset($db_conn) ) {
      write_log('Connection to database is absent: '.$result_message, $log_file, 'REG_CALL '.$LogLineId);
   }

   $users_for_notification=array();
   if( strlen($user_id)>0 ) {
      $users_for_notification=array();
      $users_for_notification[]=array('id'=>$user_id,
                                       'name'=>$user_name,
                                       'user_phone'=>$user_phone);
   }
   elseif( strlen($user_phone)===0
           && $OutcomingCall!=='1' ) {
         
         $users_for_notification=$amocrm_users;
   }
   
   
   if( $db_conn!==false ) {
      
      $query_text="";
      $query_text.='use &amocrm_database_name&;';
      $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);
      
      $db_status=$db_conn->query($query_text);
      
      $query_text="";
      $query_text.="SET NAMES 'utf8';";
      $db_status=$db_conn->query($query_text);
      
      reset($users_for_notification);
      while( list($key, $value)=each($users_for_notification) ) {
         
         $query_text="";
         $query_text.=" insert into calls ";
         $query_text.=  " (date, uniqueid, client_phone, user_phone, user_id, user_name,"
                        ."  lead_id, file_path, client_name, new_client, new_lead, outcoming, missed) ";
         $query_text.=  " values";
         $query_text.=   "('&date&', '&uniqueid&', '&client_phone&', '&user_phone&', '&user_id&', '&user_name&',"
                        ." '&lead_id&', '&file_path&', '&client_name&', &new_client&, &new_lead&, &outcoming&, &missed&); ";
              
         $query_text=set_parameter('date', $current_date, $query_text);
         $query_text=set_parameter('uniqueid', $CallId, $query_text);
         $query_text=set_parameter('client_phone', $client_phone, $query_text);
         $query_text=set_parameter('user_phone', $value['user_phone'], $query_text);
         $query_text=set_parameter('user_id', is_null($value['id']) ? '': strVal($value['id']), $query_text);
         $query_text=set_parameter('user_name', $value['name'], $query_text);
         $query_text=set_parameter('lead_id', is_null($lead_id) ? '': strVal($lead_id), $query_text);
         $query_text=set_parameter('file_path', $record_link, $query_text);
         $query_text=set_parameter('client_name', strlen($client_contact_name)>0 ? $client_contact_name: $client_company_name, $query_text);
         $query_text=set_parameter('new_client', ($contact_created===true) ? 'true':'false', $query_text);
         $query_text=set_parameter('new_lead', ($lead_created===true) ? 'true':'false', $query_text);
         $query_text=set_parameter('outcoming', ($OutcomingCall==='1') ? 'true':'false', $query_text);
         $query_text=set_parameter('missed', ($MissedCall==='1') ? 'true':'false', $query_text);
         
         
         $db_status=$db_conn->query($query_text);
         if( $db_status===true ) {
            $result=true;
         }
         else {
            $result_message=$db_conn->error;
            write_log('Request to database is failed: '.$result_message, $amocrm_log_file, 'REG_CALL '.$LogLineId);
         }
               
      }
      
   }
   
   
   return($result);
}
   
   
function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}


function get_contacts_local(&$http_requester, $client_phone, &$error_status=false, $LogLineId='') {
   
   global $custom_field_phone_id;

   
   $result=array();
   
   $parsed_client_phone=$client_phone;
   
   $client_contact=null;
   $client_contact_name=null;
   $client_company=null;
   $client_company_name=null;
   
   $parameters=array();
   $parameters['type']='contact';
   $parameters['query']=urlencode($parsed_client_phone);
   
   $error_status=false;
   $contacts_array=array();
   if( strlen($parsed_client_phone)>0 ) {
      $contacts_array=get_contact_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   }
   
   if( $error_status===true ) {
      write_log('get_contact_info failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
      return($result);
   }
   
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
   
   $contacts_array_for_sort=array();
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      if( array_key_exists('date_create', $value) ) $contacts_array_for_sort[$key]=$value['date_create'];
   }
   
   if( count($contacts_array_for_sort)===count($contacts_array) ) {
      array_multisort($contacts_array_for_sort, SORT_ASC, $contacts_array);
   }
   
   
   $result=$contacts_array;
   
   return($result);
}




function get_companies_local(&$http_requester, $client_phone, &$error_status=false, $LogLineId='') {
   
   $result=array();
   
   $parsed_client_phone=$client_phone;
   
   $parameters=array();
   $parameters['type']='company';
   $parameters['query']=urlencode($parsed_client_phone);
   
   $error_status=false;
   $companies_array=array();
   if( strlen($parsed_client_phone)>0 ) {
      $companies_array=get_company_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   }
   
   if( $error_status===true ) {
      write_log('get_company_info (phone) failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
      return($result);
   }
   
   $companies_array_for_sort=array();
   reset($companies_array);
   while( list($key, $value)=each($companies_array) ) {
      if( array_key_exists('date_create', $value) ) $companies_array_for_sort[$key]=$value['date_create'];
   }
   
   if( count($companies_array_for_sort)===count($companies_array) ) {
      array_multisort($companies_array_for_sort, SORT_ASC, $companies_array);
   }
   
   $result=$companies_array;
   
   return($result);
}


function create_contact_local(&$http_requester, $contact_data, $user_id, $contacts_array=array(), $companies_array=array(), &$error_status=false, $LogLineId='') {
   
   $result=array();
   
   if( function_exists('amocrm_1C_create_contact') ) {
      $result=amocrm_1C_create_contact($http_requester, $contact_data, $user_id, $contacts_array, $companies_array, $error_status, $LogLineId);
   }
   else {
      $result=create_contact_default_local($http_requester, $contact_data, $user_id, $contacts_array, $companies_array, $error_status, $LogLineId);
   }
     
   return($result);
}


function create_contact_default_local(&$http_requester, $contact_data, $user_id, $contacts_array=array(), $companies_array=array(), &$error_status=false, $LogLineId='') {
   
   
   $result=array();
   
   $create_new_contact=false;
   if( count($contacts_array)===0
      && count($companies_array)===0 ) {
         
      // Do not create contact if unsorted and if user does not participate in amocrm
      if( strlen($user_id)>0 ) {
         $created_contact=create_contact($http_requester, $contact_data, $error_status);
         
         if( strlen($created_contact)>0
            && is_numeric($created_contact) ) {
               
               $result['contact']=array();
               $result['contact']['id']=strVal($created_contact);
               
               $result['contact']['name']='';
               if( array_key_exists('name', $contact_data) ) {
                  $result['contact']['name']=$contact_data['name'];
               }
            }
            
      }
      
   }
   
   return($result);  
}




function need_create_lead_for_company_local(&$http_requester, $client_company, &$error_status=false, $LogLineId='') {
   
   $result=false;
   
   $create_lead=true;
   if( !is_null($client_company)
       && strlen($client_company)>0
       && is_numeric($client_company) ) {
      
      $parameters=array();
      $parameters['type']='company';
      $parameters['id']=urlencode($client_company);
      
      $error_status=false;
      $companies_array=get_company_info($parameters, $http_requester, null, null, null, null, null, $error_status);
      
      if( $error_status===true ) {
         write_log('get_company_info (company_id) failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
         return($result);
      }
      
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
   
   $result=$create_lead;
   
   return($result);
}


function get_leads_local(&$http_requester, $contacts_array, $companies_array, $time_lead_modified_from=0, &$error_status=false, $LogLineId='') {

   global $status_successful_realization;
   global $status_canceled;
   
   $result='';
   
   if( is_string($time_lead_modified_from)
       && is_numeric($time_lead_modified_from) ) $time_lead_modified_from=intVal($time_lead_modified_from);
   
   
   if( !is_numeric($time_lead_modified_from)
       || $time_lead_modified_from<=0 ) {
          
       $time_lead_modified_from=time()-60*60*24*30;
   }
   
   $get_leads_from_date=date('d M Y H:i:s', $time_lead_modified_from);
   $http_requester->{'header'}=array('if-modified-since: '.$get_leads_from_date);
   
   $error_status=false;
   $leads_array=get_leads_info('', $http_requester, null, null, null, null, null, $error_status);
   
   if( $error_status===true ) {
      write_log('get_leads_info failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
      return($result);
   }
   
   $http_requester->{'header'}='';
   
   $leads_array_for_sort=array();
   reset($leads_array);
   while( list($key, $value)=each($leads_array) ) {
      if( array_key_exists('date_create', $value) ) $leads_array_for_sort[$key]=$value['date_create'];
   }
   
   if( count($leads_array_for_sort)===count($leads_array) ) {
      array_multisort($leads_array_for_sort, SORT_DESC, $leads_array);
   }
   
   $lead_id=null;
   if( !isset($lead_id) ) {
      
      reset($leads_array);
      while( list($key, $value)=each($leads_array) ) {
         if( $value['status_id']!==$status_successful_realization
            && $value['status_id']!==$status_canceled ) {
               
               if( is_numeric($value['contact_id']) && intval($value['contact_id'])>0 && array_key_exists( intval($value['contact_id']), $contacts_array) ) {
                  $lead_id=$value['lead_id'];
                  break;
               }
            }
      }
      
   }
   
   if( !isset($lead_id) ) {
      
      $companies_array_search_lead=array();
      if( count($contacts_array)>0 ) {
         
         reset($contacts_array);
         while( list($key, $value)=each($contacts_array) ) {
            
            if( strlen($value['company_id'])>0
               && is_numeric($value['company_id'])
               && intval($value['company_id'])>0 ) $companies_array_search_lead[ intval($value['company_id']) ]=strval($value['company_id']);
         }
         
      }
      else {
         $companies_array_search_lead=$companies_array;
      }
      
      reset($leads_array);
      while( list($key, $value)=each($leads_array) ) {
         if( $value['status_id']!==$status_successful_realization
            && $value['status_id']!==$status_canceled ) {
               
            if( is_numeric($value['company_id']) && intval($value['company_id'])>0 && array_key_exists( intval($value['company_id']), $companies_array_search_lead) ) {
               $lead_id=$value['lead_id'];
               break;
            }
         }
      }
      
   }
   
   $result=strVal($lead_id);
   
   return($result);
}




function create_lead_local($http_requester, $user_id, $client_contact_name,
                           $client_company, $client_company_name, $amocrm_log_file,
                           $OutcomingCall, $client_phone, $MissedCall, $FromWeb,
                           $FirstCalledNumber, $Comment, $WebSite, $user_pipeline_status_id,
                           $additional_custom_fields=null, $additional_custom_fields_values=null) {

    global $LogLineId;
                               
    global $status_accepted_for_work;
    global $custom_field_address_type;
    global $custom_field_address_type_value_site_call;
    global $custom_field_address_type_value_string_site_call;
    global $custom_field_address_type_value_missed_call;
    global $custom_field_address_type_value_string_missed_call;
    global $custom_field_address_type_value_phone_call;
    global $custom_field_address_type_value_string_phone_call;
    global $custom_field_address_type_value_outcoming_call;
    global $custom_field_address_type_value_string_outcoming_call;
    global $custom_field_first_called_number;
    global $custom_field_comment;
    global $custom_field_web_site;
    global $phone_prefix_presentation;
    
    $result='';
    
    $fields=array();
    $name='';
    if( $FromWeb==='1' ) {
        $name='С сайта ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_site_call)
            && isset($custom_field_address_type_value_string_site_call) ) {
                
            $fields[intVal($custom_field_address_type)]=
                array(
                    'value'=>intVal($custom_field_address_type_value_site_call),
                    'value_string'=>strVal($custom_field_address_type_value_string_site_call)
                );                
        }
    }
    elseif( $MissedCall==='1' ) {
        $name='Пропущенный ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_missed_call)
            && isset($custom_field_address_type_value_string_missed_call) ) {
                
                $fields[intVal($custom_field_address_type)]=
                array(
                    'value'=>intVal($custom_field_address_type_value_missed_call),
                    'value_string'=>strVal($custom_field_address_type_value_string_missed_call)
                );
         }        
    }
    elseif( $OutcomingCall==='1' ) {
        $name='Исходящий ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_outcoming_call)
            && isset($custom_field_address_type_value_string_outcoming_call) ) {
                
                $fields[intVal($custom_field_address_type)]=
                array(
                    'value'=>intVal($custom_field_address_type_value_outcoming_call),
                    'value_string'=>strVal($custom_field_address_type_value_string_outcoming_call)
                );
        }       
    }    
    else {
        $name='Входящий ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_phone_call)
            && isset($custom_field_address_type_value_string_phone_call) ) {
                
                $fields[intVal($custom_field_address_type)]=
                array(
                    'value'=>intVal($custom_field_address_type_value_phone_call),
                    'value_string'=>strVal($custom_field_address_type_value_string_phone_call)
                );
        }
    }
    
    if( isset($custom_field_first_called_number)
        && is_numeric($custom_field_first_called_number)
        && strlen($FirstCalledNumber)>0 ) {
            
        $fields[intVal($custom_field_first_called_number)]=
        array(
            'value'=>strVal($FirstCalledNumber),
            'value_string'=>strVal($FirstCalledNumber)
        );
    }
    
    if( isset($custom_field_comment)
        && is_numeric($custom_field_comment)
        && strlen($Comment)>0 ) {
            
        $fields[intVal($custom_field_comment)]=
        array(
            'value'=>strVal($Comment),
            'value_string'=>strVal($Comment)
        );
        
    }
    
    if( isset($custom_field_web_site)
        && is_numeric($custom_field_web_site)
        && strlen($WebSite)>0 ) {
            
        $fields[intVal($custom_field_web_site)]=
        array(
            'value'=>strVal($WebSite),
            'value_string'=>strVal($WebSite)
        );
            
    }
    
    if( is_array($additional_custom_fields)
        && count($additional_custom_fields)>0
        && is_array($additional_custom_fields_values) ) {
           
        while( list($key,$value)=each($additional_custom_fields) ) {
           
           if( is_array($value)
               && array_key_exists('id', $value)
               && array_key_exists('element_type', $value)
               && is_numeric($value['id'])
               && array_key_exists($key, $additional_custom_fields_values) ) {
           
               $fields[intVal($value['id'])]=
               array(
                  'value'=>strVal($additional_custom_fields_values[$key]),
                  'value_string'=>strVal($additional_custom_fields_values[$key]),
                  'element_type'=>$value['element_type']
               );
           }           
        }
    }
   
    if( !is_null($client_contact_name) ) {
       $name.=strval($client_contact_name).' ';     
    }
    elseif( !is_null($client_company_name) ) {
       $name.=strval($client_company_name).' ';      
    }
    
    $name_numeric=remove_symbols($name);
    if( strlen($name_numeric)<10 ) {
       $name.=( isset($phone_prefix_presentation) ? strVal($phone_prefix_presentation) : '');
       $name.=$client_phone.' ';
    }

    $current_time_string=date('d.m.Y H:i');

    $name.='от '.$current_time_string;

    $lead_pipeline_status=strVal($status_accepted_for_work);
    if( is_numeric($user_pipeline_status_id) ) $lead_pipeline_status=strVal($user_pipeline_status_id);
    
    
    $error_status=false;
    $return_result=create_lead($name, $lead_pipeline_status, $user_id, $client_company, $fields, $http_requester,
                               null, null, null, null, null, $error_status);
    
    if( $error_status===true ) {
        write_log('create_lead failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
        return($result);
    }
    
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

          $lead_id=strVal($decoded_result['response']['leads']['add'][0]['id']);
          $result=$lead_id;

          write_log('Lead is created, lead_id='.$lead_id, $amocrm_log_file, 'REG_CALL '.$LogLineId);
       }
       else {
          write_log('Lead is not created: '.$return_result, $amocrm_log_file, 'REG_CALL '.$LogLineId);	 
       }

    }    
    
    return($result);
}




function create_unsorted_local($http_requester, $phone_from, $phone_to, $user_id,  $client_contact, $client_contact_name,
                               $client_company, $client_company_name, $amocrm_log_file, $OutcomingCall, $MissedCall,
                               $FromWeb, $FirstCalledNumber, $Comment, $WebSite, $ContactInfo,
                               $user_pipeline_id, $additional_custom_fields=null, $additional_custom_fields_values=null) {

    global $LogLineId;
                                   
    global $status_accepted_for_work_pipeline_id;   
    global $custom_field_phone_id;
    global $custom_field_phone_enum;  
    global $custom_field_address_type;
    global $custom_field_address_type_value_site_call;
    global $custom_field_address_type_value_string_site_call;
    global $custom_field_address_type_value_missed_call;
    global $custom_field_address_type_value_string_missed_call;
    global $custom_field_address_type_value_phone_call;
    global $custom_field_address_type_value_string_phone_call;
    global $custom_field_address_type_value_outcoming_call;
    global $custom_field_address_type_value_string_outcoming_call;
    global $custom_field_first_called_number;
    global $custom_field_comment;
    global $custom_field_web_site;
    global $phone_prefix_presentation;
    
    $result='';
       
    $fields=array();
    $name='';
    $contact_name='';
    $phone_from_with_name='';
    $phone_to_with_name='';

    
    $user_phone='';
    $client_phone='';
    if( $OutcomingCall==='1' ) {
        $user_phone=$phone_from;
        $client_phone=$phone_to;
    }
    else {
        $user_phone=$phone_to;
        $client_phone=$phone_from;
    }    


    if( !is_null($client_contact_name) ) {

       if( $OutcomingCall==='1' ) {
           $phone_to_with_name.=$client_contact_name.' ';
       }
       else {
           $phone_from_with_name.=$client_contact_name.' ';
       }        
       
    }
    elseif( !is_null($client_company_name) ) {

       if( $OutcomingCall==='1' ) {
           $phone_to_with_name.=$client_company_name.' ';
       }
       else {
           $phone_from_with_name.=$client_company_name.' ';
       }
             
    }
    

    $phone_from_with_name_numeric=remove_symbols($phone_from_with_name);
    if( strlen($phone_from_with_name_numeric)<10 ) $phone_from_with_name.=$phone_from.' ';
    
    $phone_to_with_name_numeric=remove_symbols($phone_to_with_name);
    if( strlen($phone_to_with_name_numeric)<10 ) $phone_to_with_name.=$phone_to.' ';
    
    
    if( $FromWeb==='1' ) {
        $name='C сайта ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_site_call)
            && isset($custom_field_address_type_value_string_site_call) ) {
           
            $fields[intVal($custom_field_address_type)]=
            array(
                'value'=>intVal($custom_field_address_type_value_site_call),
                'value_string'=>strVal($custom_field_address_type_value_string_site_call)
            );           
        }
       
        $phone_from_with_name.='с сайта ';
    }
    elseif( $MissedCall==='1' ) {
        $name='Пропущенный ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_missed_call)
            && isset($custom_field_address_type_value_string_missed_call) ) {
                
            $fields[intVal($custom_field_address_type)]=
                    array(
                        'value'=>intVal($custom_field_address_type_value_missed_call),
                        'value_string'=>strVal($custom_field_address_type_value_string_missed_call)
                    );                
            }
        
        $phone_from_with_name.='пропущенный ';
    }
    elseif( $OutcomingCall==='1' ) {
        $name='Исходящий ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_outcoming_call)
            && isset($custom_field_address_type_value_string_outcoming_call) ) {
                
            $fields[intVal($custom_field_address_type)]=
                    array(
                        'value'=>intVal($custom_field_address_type_value_outcoming_call),
                        'value_string'=>strVal($custom_field_address_type_value_string_outcoming_call)
                    );                              
            }  
    }    
    else {
        $name='Входящий ';
        
        if( isset($custom_field_address_type)
            && isset($custom_field_address_type_value_phone_call)
            && isset($custom_field_address_type_value_string_phone_call) ) {
                
            $fields[intVal($custom_field_address_type)]=
                    array(
                        'value'=>intVal($custom_field_address_type_value_phone_call),
                        'value_string'=>strVal($custom_field_address_type_value_string_phone_call)
                    );                
            }       
    }

    $fields[intVal($custom_field_phone_id)]=
            array(
                    'value'=>$custom_field_phone_enum,
                    'value_string'=>( $OutcomingCall==='1' ? strVal($phone_to) : strVal($phone_from))    
            );
    
        
    if( isset($custom_field_first_called_number)
        && is_numeric($custom_field_first_called_number)
        && strlen($FirstCalledNumber)>0 ) {
            
            $fields[intVal($custom_field_first_called_number)]=
            array(
                'value'=>strVal($FirstCalledNumber),
                'value_string'=>strVal($FirstCalledNumber)
            );
    }
    
    if( isset($custom_field_comment)
        && is_numeric($custom_field_comment)
        && strlen($Comment)>0 ) {
            
        $fields[intVal($custom_field_comment)]=
        array(
            'value'=>strVal($Comment),
            'value_string'=>strVal($Comment)
        );
            
    }
    
    if( isset($custom_field_comment)
        && is_numeric($custom_field_comment)
        && strlen($Comment)>0 ) {
            
        $fields[intVal($custom_field_comment)]=
        array(
            'value'=>strVal($Comment),
            'value_string'=>strVal($Comment)
        );
            
    }
    
    if( isset($custom_field_web_site)
        && is_numeric($custom_field_web_site)
        && strlen($WebSite)>0 ) {
            
        $fields[intVal($custom_field_web_site)]=
        array(
            'value'=>strVal($WebSite),
            'value_string'=>strVal($WebSite)
        );
        
    }
    
    if( is_array($additional_custom_fields)
       && count($additional_custom_fields)>0
       && is_array($additional_custom_fields_values) ) {
          
          while( list($key,$value)=each($additional_custom_fields) ) {
             
             if( is_array($value)
                && array_key_exists('id', $value)
                && array_key_exists('element_type', $value)
                && is_numeric($value['id'])
                && array_key_exists($key, $additional_custom_fields_values) ) {
                   
                   $fields[intVal($value['id'])]=
                   array(
                      'value'=>strVal($additional_custom_fields_values[$key]),
                      'value_string'=>strVal($additional_custom_fields_values[$key]),
                      'element_type'=>$value['element_type']
                   );
                }
          }
    }
    
    if( !is_null($client_contact_name) ) {
        
       if( $MissedCall==='1' || $FromWeb==='1' ) $name.='(контакт: ';
       
       $name.=strval($client_contact_name);
       
       if( $MissedCall==='1' || $FromWeb==='1' ) $name.=')';
       
       $name.=' ';
       
       $contact_name.=$client_contact_name.' ';                
    }
    elseif( !is_null($client_company_name) ) {
        
       if( $MissedCall==='1' || $FromWeb==='1' ) $name.='(компания: ';
        
       $name.=strval($client_company_name);
       
       if( $MissedCall==='1' || $FromWeb==='1' ) $name.=')';
       
       $name.=' ';
       
       $contact_name.=$client_company_name;             
    }
    
    $name_numeric=remove_symbols($name);
    if( strlen($name_numeric)<10 ) {
       $name.=( isset($phone_prefix_presentation) ? strVal($phone_prefix_presentation) : '');
       $name.=$client_phone.' ';
    }
    
    if( $FromWeb==='1'
        && strlen($ContactInfo)>0 ) $contact_name.=strVal($ContactInfo).' ';
    
    $contact_name_numeric=remove_symbols($contact_name);
    if( strlen($contact_name_numeric)<10 ) $contact_name.=remove_symbols($client_phone).' ';

    $current_time_string=date('d.m.Y H:i');

    $name.='от '.$current_time_string;
    
    $outcoming=( $OutcomingCall==='1' );
    if( strlen($client_contact)>0
        || strlen($client_company)>0 ) {
        
        // Do not create contact
        $contact_name='';
    }
    
    if( $outcoming===true ) {
        $phone_from_with_name=$user_phone;
    }
    else {
        $phone_to_with_name=( strlen($user_id)>0 ? $user_id: $user_phone );
    }    
        
    if( strlen($phone_to_with_name)===0 ) $phone_to_with_name='---';
    if( strlen($phone_from_with_name)===0 ) $phone_from_with_name='---';
    
    $unsorted_pipeline_id=strVal($status_accepted_for_work_pipeline_id);
    if( is_numeric($user_pipeline_id) ) {
       $unsorted_pipeline_id=$user_pipeline_id;
    }
    
    
    $error_status=false;
    $return_result=create_unsorted($name, $unsorted_pipeline_id,
                                   $phone_from_with_name,  $phone_to_with_name,
                                   $contact_name, $client_company,
                                   '', $outcoming,
                                   $fields, $http_requester,
                                   null, null,
                                   null, null,
                                   null, $error_status);
       
    if( $error_status===true ) {
        write_log('create_unsorted failed ', $amocrm_log_file, 'REG_CALL '.$LogLineId);
        return($result);
    }
    
    if( $return_result!==false ) {

       $decoded_result=json_decode($return_result, true);
       if( is_array($decoded_result)
           && array_key_exists('response', $decoded_result)
           && is_array($decoded_result['response'])
           && array_key_exists('unsorted', $decoded_result['response'])
           && is_array($decoded_result['response']['unsorted'])
           && array_key_exists('add', $decoded_result['response']['unsorted'])
           && is_array($decoded_result['response']['unsorted']['add'])
           && array_key_exists('status', $decoded_result['response']['unsorted']['add'])
           && strcasecmp('success', $decoded_result['response']['unsorted']['add']['status'])===0 ) {

          $unsorted_id=strVal($decoded_result['response']['unsorted']['add']['data'][0]);
          $result=$unsorted_id;

          write_log('Unsorted is created, unsorted_id='.$unsorted_id, $amocrm_log_file, 'REG_CALL '.$LogLineId);
       }
       else {
          write_log('Unsorted is not created: '.$return_result, $amocrm_log_file, 'REG_CALL '.$LogLineId);	 
       }

    }    
    
    return($result);
}
   
   
?>
