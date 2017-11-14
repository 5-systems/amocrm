<?php


   date_default_timezone_set('Etc/GMT-3');

   @$CallId=$_REQUEST['CallId'];
   @$CallerNumber=$_REQUEST['CallerNumber'];
   @$CalledNumber=$_REQUEST['CalledNumber'];   
   @$CallDate=$_REQUEST['CallDate'];
   @$ContactInfo=$_REQUEST['ContactInfo'];
   @$MissedCall=$_REQUEST['MissedCall'];
   @$OutcomingCall=$_REQUEST['OutcomingCall'];
   @$FromWeb=$_REQUEST['FromWeb'];
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

   
   // Get user_id and user_name
   $user_id='';
   $user_name='';
   
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'log_file'}=$amocrm_log_file;

   $user_phone=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   if( strlen($user_phone)>0 ) {
      $user_info=get_user_info_by_user_phone($user_phone, $custom_field_user_amo_crm, $custom_field_user_phone, $http_requester);
      
      if( is_array($user_info) ) {
	 if( array_key_exists('user_id', $user_info) ) $user_id=$user_info['user_id'];
	 if( array_key_exists('name', $user_info) ) $user_name=$user_info['name'];      
      }
   }

   
    // Create call note
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
    $registrator->{'phone_prefix'}=$phone_prefix_presentation;
        
    if( strlen($user_id)===0 ) {
        // Do not create contact (unsorted)
        $registrator->{'create_contact'}=false;
    }
    
    $registrator->register_call();
    $contact_created=$registrator->{'contact_created'};
  
   
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

    $leads_array_for_sort=array();
    reset($leads_array);
    while( list($key, $value)=each($leads_array) ) {
       if( array_key_exists('date_create', $value) ) $leads_array_for_sort[$key]=$value['date_create'];
    }

    if( count($leads_array_for_sort)===count($leads_array) ) {
       array_multisort($leads_array_for_sort, SORT_DESC, $leads_array);  
    }

    if( is_null($lead_id) ) {
        
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
    
    if( is_null($lead_id) ) {
        
        reset($leads_array);
        while( list($key, $value)=each($leads_array) ) {
            if( $value['status_id']!==$status_successful_realization
                && $value['status_id']!==$status_canceled ) {

                if( is_numeric($value['company_id']) && intval($value['company_id'])>0 && array_key_exists( intval($value['company_id']), $companies_array) ) {
                    $lead_id=$value['lead_id'];
                    break;	 
                }
            }
        }
        
    }    
      
   if( !is_null($lead_id) ) {
      write_log('Lead is found, lead_id='.$lead_id, $amocrm_log_file, 'REG_CALL');
   }

   
   $lead_created=false;
   $unsorted_created=false;
   $unsorted_id=null;
   
   $user_phone=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   if( is_null($lead_id)
       && $create_lead===true ) {
       
      if( strlen($user_id)>0 ) {
          
          // Create lead
          $lead_id=create_lead_local($http_requester, $user_id,
                                     $client_contact_name, $client_company,
                                     $client_company_name, $amocrm_log_file,
                                     $OutcomingCall, $parsed_client_phone,
                                     $MissedCall, $FromWeb);
          
          if( strlen($lead_id)>0 ) $lead_created=true;
          
      }
      elseif( strlen($user_phone)===0 ) {
          
          // Create unsorted
          $phone_from=( $OutcomingCall==='1' ? $CallerNumber : $phone_prefix_presentation.$parsed_client_phone );
          $phone_to=( $OutcomingCall==='1' ? $phone_prefix_presentation.$parsed_client_phone : $CalledNumber );
          
          $unsorted_id=create_unsorted_local($http_requester,
                                             $phone_from, $phone_to, 
                                             $user_id, $client_contact,
                                             $client_contact_name, $client_company,
                                             $client_company_name, $amocrm_log_file,
                                             $OutcomingCall, $MissedCall, $FromWeb);

          
          if( strlen($unsorted_id)>0 ) $unsorted_created=true;
      }
      

      // Attach lead to contact
      if( strlen($client_contact)>0
          && strlen($lead_id)>0
          && is_numeric($lead_id) ) {
          
	 $parameters=array("id"=>$client_contact);
	 $updated_fields=array("linked_leads_id"=>array( intVal($lead_id) ));
	 $update_result=update_contact_info($parameters, $updated_fields, $http_requester);
	 if( $update_result===false ) {
	    write_log('Lead is not attached to contact, lead_id='.$lead_id.', contact_id='.$client_contact, $amocrm_log_file, 'REG_CALL');
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

   
   $user_phone=($OutcomingCall==='1' ? $CallerNumber: $CalledNumber);
   if( strlen($user_phone)===0
       && $OutcomingCall!=='1' ) {
           
       $users_for_notification=$amocrm_users;      
   }
   elseif( strlen($user_id)>0 ) {
        $users_for_notification=array();
        $users_for_notification[]=array('id'=>$user_id,
                                         'name'=>$user_name,
                                         'user_phone'=>$user_phone);
   }
   
   if( $db_conn!==false ) {

      $query_text="";
      $query_text.='use &amocrm_database_name&;';
      $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);
      $db_status=mysql_query($query_text);

      $query_text="";
      $query_text.="SET NAMES 'utf8';";
      $db_status=mysql_query($query_text);
      
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
        $query_text=set_parameter('client_phone', ($OutcomingCall==='1' ? $CalledNumber: $CallerNumber), $query_text);    
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


        $db_status=mysql_query($query_text);
        if( $db_status===false ) {
           $result_message=mysql_error();
           write_log('Request to database is failed: '.$result_message, $amocrm_log_file, 'REG_CALL');
        }
      
      }

   }
      

      
   echo $CallId;

   write_log('Finish ', $amocrm_log_file, 'REG_CALL');
   
function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}


function create_lead_local($http_requester, $user_id, $client_contact_name,
                           $client_company, $client_company_name, $amocrm_log_file,
                           $OutcomingCall, $client_phone, $MissedCall, $FromWeb) {
    
    global $status_accepted_for_work;
    global $custom_field_address_type;
    global $custom_field_address_type_value_site_call;
    global $custom_field_address_type_value_string_site_call;
    global $custom_field_address_type_value_missed_call;
    global $custom_field_address_type_value_string_missed_call;
    global $custom_field_address_type_value_phone_call;
    global $custom_field_address_type_value_string_phone_call;
    
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
            && isset($custom_field_address_type_value_phone_call)
            && isset($custom_field_address_type_value_string_phone_call) ) {
                
                $fields[intVal($custom_field_address_type)]=
                array(
                    'value'=>intVal($custom_field_address_type_value_phone_call),
                    'value_string'=>strVal($custom_field_address_type_value_string_phone_call)
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
   
    if( !is_null($client_contact_name) ) {
       $name.=strval($client_contact_name).' ';     
    }
    elseif( !is_null($client_company_name) ) {
       $name.=strval($client_company_name).' ';      
    }
    
    $name_numeric=remove_symbols($name);
    if( strlen($name_numeric)<10 ) $name.=$client_phone.' ';

    $current_time_string=date('d.m.Y H:i');

    $name.='от '.$current_time_string;

    $return_result=create_lead($name, $status_accepted_for_work, $user_id, $client_company, $fields, $http_requester);     
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

          write_log('Lead is created, lead_id='.$lead_id, $amocrm_log_file, 'REG_CALL');
       }
       else {
          write_log('Lead is not created: '.$return_result, $amocrm_log_file, 'REG_CALL');	 
       }

    }    
    
    return($result);
}


function create_unsorted_local($http_requester, $phone_from, $phone_to, $user_id,  $client_contact, $client_contact_name,
                               $client_company, $client_company_name, $amocrm_log_file, $OutcomingCall, $MissedCall, $FromWeb) {
    
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
            && isset($custom_field_address_type_value_phone_call)
            && isset($custom_field_address_type_value_string_phone_call) ) {
                
            $fields[intVal($custom_field_address_type)]=
                    array(
                        'value'=>intVal($custom_field_address_type_value_phone_call),
                        'value_string'=>strVal($custom_field_address_type_value_string_phone_call)
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
    
        
        
    
    if( !is_null($client_contact_name) ) {
       $name.=strval($client_contact_name).' ';
       $contact_name.=$client_contact_name.' ';                
    }
    elseif( !is_null($client_company_name) ) {
       $name.=strval($client_company_name).' ';
       $contact_name.=$client_company_name;             
    }
    
    $name_numeric=remove_symbols($name);
    if( strlen($name_numeric)<10 ) $name.=$client_phone.' ';
    
    $contact_name_numeric=remove_symbols($contact_name);
    if( strlen($contact_name_numeric)<10 ) $contact_name.=$client_phone.' ';

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
    
    $return_result=create_unsorted($name, $status_accepted_for_work_pipeline_id,
                                   $phone_from_with_name,  $phone_to_with_name,
                                   $contact_name, $client_company,
                                   '', $outcoming,
                                   $fields, $http_requester);
       
    
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

          write_log('Unsorted is created, unsorted_id='.$unsorted_id, $amocrm_log_file, 'REG_CALL');
       }
       else {
          write_log('Unsorted is not created: '.$return_result, $amocrm_log_file, 'REG_CALL');	 
       }

    }    
    
    return($result);
}
   
 
  
   
?>
