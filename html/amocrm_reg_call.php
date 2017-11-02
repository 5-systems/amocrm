<?php

   // version 01.11.2017

   date_default_timezone_set('Etc/GMT-3');

   @$CallId=$_REQUEST['CallId'];
   @$CallerNumber=$_REQUEST['CallerNumber'];
   @$CalledNumber=$_REQUEST['CalledNumber'];   
   @$CallDate=$_REQUEST['CallDate'];
   @$ContactInfo=$_REQUEST['ContactInfo'];
   @$MissedCall=$_REQUEST['MissedCall'];
   @$OutcomingCall=$_REQUEST['OutcomingCall'];
   @$Link=$_REQUEST['Link'];
   @$Account=$_REQUEST['Account'];
   @$login=$_REQUEST['param_login'];
   @$password=$_REQUEST['param_password'];

   if( !isset($login) ) $login='';
   if( !isset($password) ) $password='';
   if(!isset($CalledNumber)) $CalledNumber='';
   if(!isset($MissedCall)) $MissedCall='0';
   if(!isset($OutcomingCall)) $OutcommingCall='0';
   if(!isset($Link)) $Link='';

   require_once('amocrm_settings.php');
   require_once('5c_amocrm_lib.php');
   require_once('5c_std_lib.php');

/*   
   $CallId="1508333734.17265";
   $CallerNumber="907";
   $CallDate="20171101090843";
   $CalledNumber='4955404614';
   $OutcomingCall='1';
*/

   // Input parameters
   $call_unix_time='';
   if( strlen($CallDate)===14 ) $call_unix_time=strtotime($CallDate);

   $record_link=$url_records;
   if( substr($record_link, -1)==='/' || substr($record_link, -1)==='\\' ) $record_link=substr($record_link, 0, strlen($record_link)-1);
   $record_link.='/'.$Link;

   // Register call
   $current_time=time();
   write_log('blank_line', $amocrm_log_file, 'REG_CALL');
   write_log($_REQUEST, $amocrm_log_file, 'REG_CALL');
   
   $contact_created=false;
   $registrator=new amocrm_register_call;

   // From settings
   $registrator->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $registrator->{'USER_HASH'}=$amocrm_USER_HASH;
   $registrator->{'amocrm_account'}=$amocrm_account;
   $registrator->{'coockie_file'}=$amocrm_coockie_file;
   $registrator->{'log_file'}=$amocrm_log_file;
   $registrator->{'custom_field_phone_id'}=$custom_field_phone_id;   
   $registrator->{'custom_field_phone_enum'}=$custom_field_phone_enum; 
   $registrator->{'custom_field_email_id'}=$custom_field_email_id; 
   $registrator->{'custom_field_email_enum'}=$custom_field_email_enum; 
   
   // From call
   $registrator->{'phone'}=($OutcomingCall==='1' ? $CalledNumber: $CallerNumber);
   $registrator->{'callid'}=$CallId;
   $registrator->{'name'}=$ContactInfo;
   $registrator->{'call_unix_time'}=$call_unix_time;
   $registrator->{'missed_call'}=$MissedCall;
   $registrator->{'record_link'}=$record_link;
   $registrator->{'outcoming_call'}=$OutcomingCall;
   $registrator->{'user_phone'}=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   $registrator->{'custom_field_user_phone'}=$custom_field_user_phone;
   $registrator->{'custom_field_user_amo_crm'}=$custom_field_user_amo_crm;
   $registrator->{'phone_prefix'}='+7';

   $registrator->register_call();
   $contact_created=$registrator->{'contact_created'};
   
   // Get user_id and user_name
   $user_id='';
   $user_name='';
   
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'log_file'}=$amocrm_log_file;

   if( strlen($CalledNumber)>0 ) {
      $user_info=get_user_info_by_user_phone(($OutcomingCall==='1' ? $CallerNumber: $CalledNumber), $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester);
      
      if( is_array($user_info) ) {
	 if( array_key_exists('user_id', $user_info) ) $user_id=$user_info['user_id'];
	 if( array_key_exists('name', $user_info) ) $user_name=$user_info['name'];      
      }
   }

   // Create lead
   // Get contact by phone
   $client_phone=remove_symbols(($OutcomingCall==='1' ? $CalledNumber: $CallerNumber));
   $client_phone=substr($client_phone, -10);
   $parsed_client_phone=$client_phone;   
   
   $client_contact=null;
   $client_contact_name=null;
   $client_company=null;
   $client_company_name=null;
   
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
           while( list($key_2, $value_2)=each($value['custom_fields']) ) {
               if( is_array($value_2)
                   && array_key_exists('id', $value_2)
                   && strVal($value_2['id'])===strVal($custom_field_phone_id)
                   && array_key_exists('values', $value_2)
                   && is_array($value_2['values']) ) {
                 
                   $phone_values=$value_2['values'];
                   foreach($phone_values as $value_3) {
                       if( is_array($value_3)
                           && array_key_exists('value', $value_3)
                           && strpos($value_3['value'], $parsed_client_phone)!==false ) {
                           
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
      $client_contact_name=$value['name'];
     if( strlen($value['company_id'])>0
         && is_numeric($value['company_id'])
         && intval($value['company_id'])>0 ) $client_company=$value['company_id'];

      break;
   }
  
  write_log('Search for contact, contact_id='.$client_contact.', company_id='.$client_company, $amocrm_log_file, 'REG_CALL');

  // Get company by phone
  $companies_array=array();
  reset($contacts_array);
  while( list($key, $value)=each($contacts_array) ) {
	if( strlen($value['company_id'])>0
            && is_numeric($value['company_id'])
            && intval($value['company_id'])>0 ) $companies_array[ intval($value['company_id']) ]=strval($value['company_id']);
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
      
      write_log('Search for company, company_id='.$client_company, $amocrm_log_file, 'REG_CALL');  
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
   $lead_created=false;

   $leads_array_for_sort=array();
   reset($leads_array);
   while( list($key, $value)=each($leads_array) ) {
      if( array_key_exists('date_create', $value) ) $leads_array_for_sort[$key]=$value['date_create'];
   }

   if( count($leads_array_for_sort)===count($leads_array) ) {
      array_multisort($leads_array_for_sort, SORT_DESC, $leads_array);  
   }
   
   reset($leads_array);
   while( list($key, $value)=each($leads_array) ) {
      if( $value['status_id']!==$status_successful_realization
          && $value['status_id']!==$status_canceled ) {

        if( is_numeric($value['contact_id']) && intval($value['contact_id'])>0 && array_key_exists( intval($value['contact_id']), $contacts_array) ) {
	    $lead_id=$value['lead_id'];
	    break;
	}
	elseif( is_numeric($value['company_id']) && intval($value['company_id'])>0 && array_key_exists( intval($value['company_id']), $companies_array) ) {
	    $lead_id=$value['lead_id'];
	    break;	 
	 }

      }
   }
      
   if( !is_null($lead_id) ) {
      write_log('Lead is found, lead_id='.$lead_id, $amocrm_log_file, 'REG_CALL');
   }

   // Create lead
   if( is_null($lead_id)
       && $create_lead===true ) {

      $name='Звонок ';
      if( !is_null($client_contact_name) ) {
      
	 if( $OutcomingCall==='1' ) $name.='клиенту ';
	 else $name.='клиентa ';
	 
	 $name.=strval($client_contact_name).' ';     
      }
      elseif( !is_null($client_company_name) ) {
      
	 if( $OutcomingCall==='1' ) $name.='в компанию ';
	 else $name.='из компании ';
	 
	 $name.=strval($client_company_name).' ';      
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
	    
	    $lead_id=strVal($decoded_result['response']['leads']['add'][0]['id']);
	    $lead_created=true;
	    write_log('Lead is created, lead_id='.$lead_id, $log_file, 'REG_CALL');
	 }
	 else {
	    write_log('Lead is not created: '.$return_result, $log_file, 'REG_CALL');	 
	 }
   
      }

      // Attach lead to contact
      if( strlen($client_contact)>0
          && strlen($lead_id)>0
          && is_numeric($lead_id) ) {
          
	 $parameters=array("id"=>$client_contact);
	 $updated_fields=array("linked_leads_id"=>array( intVal($lead_id) ));
	 $update_result=update_contact_info($parameters, $updated_fields, $http_requester);
	 if( $update_result===false ) {
	    write_log('Lead is not attached to contact, lead_id='.$lead_id.', contact_id='.$client_contact, $log_file, 'REG_CALL');
	 }
	 
      }
   
   }

   $current_date=date('Y-m-d H:i:s', $current_time);
   
   // Defined variables:
   // CallId
   // CallerNumber
   // CalledNumber
   // user_id
   // user_name
   // lead_id
   // record_link
   // current_date
   // client_contact_name
   // contact_created
   // lead_created   
   
   // Write in database
   $db_host=$amocrm_database_host;
   if( strlen($amocrm_database_port)>0 ) $db_host.=':'.$amocrm_database_port;

   $db_conn=mysql_connect($db_host, $amocrm_database_user, $amocrm_database_password);
   if( $db_conn===false ) {
      write_log('Connection to database is failed', $amocrm_log_file, 'REG_CALL');
      exit($result);
   }

   if( $db_conn!==false ) {

      $query_text="";
      $query_text.='use &amocrm_database_name&;';
      $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);
      $db_status=mysql_query($query_text);

      $query_text="";
      $query_text.="SET NAMES 'utf8';";
      $db_status=mysql_query($query_text);

      $query_text="";
      $query_text.=" insert into calls ";
      $query_text.=  " (date, uniqueid, client_phone, user_phone, user_id, user_name, lead_id, file_path, client_name, new_client, new_lead, outcoming) ";
      $query_text.=  " values";
      $query_text.=   "('&date&', '&uniqueid&', '&client_phone&', '&user_phone&', '&user_id&', '&user_name&', '&lead_id&', '&file_path&', '&client_name&', &new_client&, &new_lead&, &outcoming&); ";
        

      $query_text=set_parameter('date', $current_date, $query_text);
      $query_text=set_parameter('uniqueid', $CallId, $query_text);
      $query_text=set_parameter('client_phone', ($OutcomingCall==='1' ? $CalledNumber: $CallerNumber), $query_text);    
      $query_text=set_parameter('user_phone', ($OutcomingCall==='1' ? $CallerNumber: $CalledNumber), $query_text);      
      $query_text=set_parameter('user_id', $user_id, $query_text);
      $query_text=set_parameter('user_name', $user_name, $query_text);      
      $query_text=set_parameter('lead_id', $lead_id, $query_text);
      $query_text=set_parameter('file_path', $record_link, $query_text);      
      $query_text=set_parameter('client_name', strlen($client_contact_name)>0 ? $client_contact_name: $client_company_name, $query_text);
      $query_text=set_parameter('new_client', ($contact_created===true) ? 'true':'false', $query_text);
      $query_text=set_parameter('new_lead', ($lead_created===true) ? 'true':'false', $query_text);
      $query_text=set_parameter('outcoming', ($OutcomingCall==='1') ? 'true':'false', $query_text);

      
      $db_status=mysql_query($query_text);
      if( $db_status===false ) {
         $result_message=mysql_error();
         write_log('Request to database is failed: '.$result_message, $amocrm_log_file, 'REG_CALL');
      }

   }
      

      
   echo $CallId;

   write_log('Finish ', $amocrm_log_file, 'REG_CALL');
   
function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}
  
   
?>
