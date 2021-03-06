<?php

  // version 24.05.2018

  require_once('5c_files_lib.php');  
  require_once('5c_std_lib.php');
  require_once('5c_database_lib.php');

  date_default_timezone_set('Etc/GMT-3');
  ini_set("default_socket_timeout", 600);
  
  
class amocrm_register_call {
 
  // required parameters
  public $phone;
  public $callid;
  public $USER_LOGIN;
  public $USER_HASH;
  public $amocrm_account;
  public $coockie_file;
  
  // optional parameters
  public $email;
  public $name;
  public $call_unix_time;
  public $call_duration;
  public $missed_call;
  public $record_link;
  public $outcoming_call;
  public $log_file;
  public $contact_created;
  public $custom_field_phone_id;
  public $custom_field_phone_enum;
  public $custom_field_email_id;
  public $custom_field_email_enum;
  public $user_phone;
  public $user_id;
  public $custom_field_user_phone;
  public $custom_field_user_amo_crm;
  public $phone_prefix;
  public $create_contact;
  public $http_requester;
  
  
  // read only
  public $connected;

  
  function __construct() {
      $this->connected=false;
      $this->contact_created=false;
      $this->phone_prefix='';
      $this->create_contact=true;
  }
  
  public function register_call() {
    
    $result=false;
  
    // Calculated parameters
    if( !isset($this->phone) || strlen($this->phone)===0 ) {echo('phone is not set'); return($result);};
    if( !isset($this->callid) || strlen($this->callid)===0 ) return($result);
    if( !isset($this->USER_LOGIN) || strlen($this->USER_LOGIN)===0 ) return($result);
    if( !isset($this->USER_HASH) || strlen($this->USER_HASH)===0 ) return($result);    
    if( !isset($this->amocrm_account) || strlen($this->amocrm_account)===0 ) return($result);
    if( !isset($this->coockie_file) || strlen($this->coockie_file)===0 ) return($result);    
        
    if( !isset($this->name) ) $this->name='';
    if( !isset($this->email) ) $this->email='';
    if( !isset($this->call_unix_time) || strlen($this->call_unix_time)===0 ) $this->call_unix_time=sprintf("%.0f", time());
    if( !isset($this->call_duration) ) $this->call_duration=0;
    if( !isset($this->missed_call) ) $this->missed_call='0';
    if( !isset($this->missed_call) ) $this->missed_call='';
    if( !isset($this->outcoming_call) ) $this->outcoming_call='0';
    if( !isset($this->log_file) ) $this->log_file='';
    
    $this->contact_created=false;    

    $parsed_phone=remove_symbols($this->phone);
    $parsed_phone=substr($parsed_phone, -10);    
       
    $call_result='';
    $call_status='4'; 
    if( $this->missed_call==='1' ) {
      $call_result='нет';
      $call_status='6';
    }
    elseif( $this->missed_call==='0' ) {
      $call_result='да';
      $call_status='4';   
    }
    
    $note_type=10;
    if( $this->outcoming_call==='1' ) $note_type=11;
    
    if( is_string($this->call_duration) ) {
    
      $loc_call_duration=remove_symbols($this->call_duration);
    
      if( strlen($loc_call_duration)>0 ) {
	$this->call_duration=intval($loc_call_duration);
      }
      else {
	$this->call_duration=0;
      }
           
    }
    
    $http_requester=null;
    if( isset($this->http_requester) ) $http_requester=($this->http_requester);
    
    if( !isset($http_requester) ) {    
        $http_requester=new amocrm_http_requester;
        $http_requester->{'USER_LOGIN'}=$this->USER_LOGIN;
        $http_requester->{'USER_HASH'}=$this->USER_HASH;
        $http_requester->{'amocrm_account'}=$this->amocrm_account;
        $http_requester->{'coockie_file'}=$this->coockie_file;
        $http_requester->{'log_file'}=$this->log_file;
    }
    
    // Get contact list
    $contact_id=0;
    if( strlen($parsed_phone)===10
        && isset($this->custom_field_phone_id)
        && strlen($this->custom_field_phone_id)>0
        && is_numeric($this->custom_field_phone_id) ) {
         
        $parameters=array();
        $parameters['type']='contact';
        $parameters['query']=urlencode($parsed_phone);
        
        $error_status=false;
        $contacts_array=get_contact_info($parameters, $http_requester, null, null, null, null, null, $error_status);

        if( $error_status===true ) return($result);
        
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
                            && strVal($value_2['id'])===strVal($this->custom_field_phone_id)
                            && array_key_exists('values', $value_2)
                            && is_array($value_2['values']) ) {
                                
                                $phone_values=$value_2['values'];
                                reset($phone_values);
                                foreach($phone_values as $value_3) {
                                    if( is_array($value_3)
                                        && array_key_exists('value', $value_3)
                                        && strpos( remove_symbols($value_3['value']), $parsed_phone)!==false ) {
                                            
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
            if( array_key_exists('last_modified', $value) ) $contacts_array_for_sort[$key]=$value['last_modified'];
        }
        
        if( count($contacts_array_for_sort)===count($contacts_array) ) {
            array_multisort($contacts_array_for_sort, SORT_ASC, $contacts_array);
        }
                
        reset($contacts_array);
        while( list($key, $value)=each($contacts_array) ) {
            $contact_id=$value['contact_id'];
            write_log('Contact is found: contact_id='.$contact_id, $this->log_file);
                
            break;
        }
        
    }
   
    // Get companies list
    $company_id=0;
    if( strlen($parsed_phone)===10 ) {
        
        $parameters=array();
        $parameters['type']='company';
        $parameters['query']=urlencode($parsed_phone);
        
        $error_status=false;
        $contacts_array=get_contact_info($parameters, $http_requester, null, null, null, null, null, $error_status);
        
        if( $error_status===true ) return($result);
        
        $contacts_array_for_sort=array();
        reset($contacts_array);
        while( list($key, $value)=each($contacts_array) ) {
            if( array_key_exists('last_modified', $value) ) $contacts_array_for_sort[$key]=$value['last_modified'];
        }
        
        if( count($contacts_array_for_sort)===count($contacts_array) ) {
            array_multisort($contacts_array_for_sort, SORT_ASC, $contacts_array);
        }
        
        reset($contacts_array);
        while( list($key, $value)=each($contacts_array) ) {
            $company_id=$value['contact_id'];
            write_log('Company is found: company_id='.$company_id, $this->log_file);
            
            break;
        }

    }
    
    // Create new contact
    if( $contact_id===0
        && $company_id===0
        && ($this->create_contact===true) ) {
        
      $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/contacts/set';  
      
      $custom_fields_value=array();
      
      // phones
      if( strlen($parsed_phone)>0
          && isset($this->custom_field_phone_id)
          && isset($this->custom_field_phone_enum) ) {
      
    	 $phone_values=array( array("value"=>($this->phone_prefix.$parsed_phone), "enum"=>($this->custom_field_phone_enum)) );  
    	 $phones=array("id"=>($this->custom_field_phone_id), "values"=>$phone_values);
    
    	 $custom_fields_value[]=$phones;
      }   

      if( strlen($this->email)>0
          && isset($this->custom_field_email_id)
          && isset($this->custom_field_email_enum) ) {
          
    	 $email_values=array( array("value"=>$this->email, "enum"=>($this->custom_field_email_enum)) );
    	 $emails=array("id"=>($this->custom_field_email_id), "values"=>$email_values);
    
    	 $custom_fields_value[]=$emails;  
      }
      
      $responsible_user_id=0;
      if( isset($this->user_id)
          && is_numeric($this->user_id) 
          && intVal($this->user_id)>0 ) {
              
          $responsible_user_id=intVal($this->user_id);
      }
      elseif( isset($this->user_phone)
              && strlen($this->user_phone)>0
              && strlen($this->custom_field_user_phone)>0
              && strlen($this->custom_field_user_amo_crm)>0 ) {
                  
          $user_info=get_user_info_by_user_phone($this->user_phone,
                                                 $this->custom_field_user_amo_crm,
                                                 $this->custom_field_user_phone,
                                                 $http_requester);
          
          if( is_array($user_info) ) {
              if( array_key_exists('user_id', $user_info) ) $responsible_user_id=$user_info['user_id'];
          }
      }
      
      $add_value=array( array("name"=>$this->name.' '.($parsed_phone),
                              "responsible_user_id"=>$responsible_user_id,
                              "custom_fields"=>$custom_fields_value) );
      
      $contacts_value=array( "add"=>$add_value );
      
      $request_value=array( "contacts"=>$contacts_value );
      
      $parameters=array( "request"=>$request_value );
      $parameters_json=json_encode($parameters);
            
      $http_requester->{'send_method'}='POST';
      $http_requester->{'url'}=$url;
      $http_requester->{'parameters'}=$parameters_json;
      
      $return_result=false;
      $return_result=$http_requester->request();
      
      if( $return_result===false ) return($result);
               
      if( $return_result!==false ) {
        	$decoded_result=json_decode($return_result, true);
        	$response=$decoded_result['response'];
        	
            if( isset($response['contacts']) && isset($response['contacts']['add']) && count($response['contacts']['add'])>0 && isset($response['contacts']['add'][0]['id']) && is_numeric($response['contacts']['add'][0]['id']) ) {
              $contact_id=$response['contacts']['add'][0]['id'];
              
              $this->contact_created=true;
              write_log('Contact is created', $this->log_file);
            }	
      }
  
    }
    
    $user_id=0;
    if( isset($this->user_id)
        && is_numeric($this->user_id)
        && intVal($this->user_id)>0 ) $user_id=intVal($this->user_id);
    
    if( $user_id===0
        && isset($this->custom_field_user_phone)
        && isset($this->custom_field_user_amo_crm)
        && isset($this->user_phone)
        && strlen($this->user_phone)>0 ) {
    
    	 $client_contact_user=null;
        
    	 $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/contacts/list';
    	 $parameters=array();
    	 $parameters['type']='contact';
    	 $parameters['query']=strVal($this->user_phone);

         $http_requester->{'send_method'}='GET';
         $http_requester->{'url'}=$url;
         $http_requester->{'parameters'}=$parameters;
         
         $return_result=false;
         $return_result=$http_requester->request();
         
         if( $return_result===false ) return($result);
            
    	 if( $return_result!==false ) {
    	 
    	    $decoded_result=json_decode($return_result, true);
    	    $response=$decoded_result['response'];
    
    	    if( isset($response['contacts'])
    		  && count($response['contacts'])>0 ) {
    		  
    	       $client_contacts=$response['contacts'];
    	       reset($client_contacts);
    	       while( list($key, $value)=each($client_contacts) ) {
    	       
        		  if( is_array($value)
        			&& isset($value['custom_fields'])
        			&& count($value['custom_fields'])>0 ) {
        			
        		     $custom_fields=$value['custom_fields'];
        		     reset($custom_fields);
        		     while( list($key_2, $value_2)=each($custom_fields) ) {
        		  
            			if( is_array($value_2)
            			   && isset($value_2['id'])
            			   && strVal($value_2['id'])===strVal($this->custom_field_user_phone)
            			   && is_array($value_2['values'])
            			   && strpos($value_2['values'][0]['value'], $this->user_phone)!==false ) {
            			   
            			   $client_contact_user=$value;
            			   break;
            			}
        			
        		     }
        				    
        		  }
    		  
        		  if( !is_null($client_contact_user) ) break;
        	       
                }
    	       
    	       
    	    } // search for contact
    
    	    
    	    // search for user phone
    	    if( is_array($client_contact_user)
    		  && isset($client_contact_user['custom_fields'])
    		  && count($client_contact_user['custom_fields'])>0 ) {
    		  
    	       $custom_fields=$client_contact_user['custom_fields'];
    	       reset($custom_fields);
    	       while( list($key, $value)=each($custom_fields) ) {
    		  
        		  if( is_array($value)
        		     && isset($value['id'])
        		     && strVal($value['id'])!==strVal($this->custom_field_user_amo_crm) ) continue; 
        
        		  $values_array=$value['values'];	  		  
        		  if( is_array($values_array)
        		     && count($values_array)>0
        		     && isset($values_array[0]['value'])
        		     && is_numeric($values_array[0]['value']) ) {
        		  
        		     $user_id=intVal($values_array[0]['value']);
        		     break;	    
        		  }
    		     
    	       } 
    			      
    	    } // search for user phone	 
    	    
    	 }
      
    }
   
    // Create new call record
    if( $contact_id>0
        || $company_id>0 ) {
            
      $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/notes/set';
      
      $parameters=array();
      $parameters['request']['notes']['add']=array(
	  array(
	      'element_type'=>($contact_id>0 ? 1:3),
	      'element_id'=>($contact_id>0 ? $contact_id:$company_id),
	      'last_modified'=> $this->call_unix_time,
	      'note_type'=>$note_type,
	      'created_user_id'=>$user_id,
	      'responsible_user_id'=>$user_id,
	      
	      'text' => json_encode( 
		  array(
		      'UNIQ' => $this->callid,
		      'LINK' => $this->record_link,
		      'PHONE' => $this->phone,
		      'DURATION' => $this->call_duration,
		      'SRC'=>'bwtelfivesystems',
		      'call_status' => $call_status,
		      'call_result' => $call_result
		  )
	      )
	  )
      );

      $parameters_json=json_encode($parameters);
      
      $http_requester->{'send_method'}='POST';
      $http_requester->{'url'}=$url;
      $http_requester->{'parameters'}=$parameters_json;
      
      $return_result=false;
      $return_result=$http_requester->request();
      
      if( $return_result===false ) return($result);
      
      if( $return_result!==false ) {
    	$decoded_result=json_decode($return_result, true);
    	$response=$decoded_result['response'];
    	
    	if( isset($response['notes']) && isset($response['notes']['add']) && count($response['notes']['add'])>0 && isset($response['notes']['add'][0]['id']) && is_numeric($response['notes']['add'][0]['id']) ) {
    	  write_log('Call is added: '.$this->callid, $this->log_file);
    	  $result=true;
    	}
	
      }
      
    }
    
    // All stages are passed
    $result=true;
    
    return($result);

  } // end function register_call   
  
  
} // end class


function amocrm_request($send_method, $url, $parameters, $log_path="", $coockie_path="", $header=null, &$request_status_code=null) {

  $result=false;
 
  $send_method_used='POST';
  if( strcasecmp($send_method, 'GET')===0 ) $send_method_used='GET';
  
  $url_used=$url;
 
  $record=Array();
  $record['url']=$url;
  
  $parameters_separator_found=false;
  $separator_position=strpos($url, '?');
  if( $separator_position!==false ) $parameters_separator_found=true;
  
  if( is_array($parameters) ) {
  
    reset($parameters);
    $cycle_index=0;
    while(list($key, $value)=each($parameters)) {
      $record[$key]=$value;
      
      if( $send_method_used==='GET' ) {
	
	$value_array=array();
	$braces_postfix='';
	if( is_array($value) ) {
	    $value_array=$value;
	    $braces_postfix='[]';
        }
        else {
	    $value_array[]=$value;
        }
        
        reset($value_array);
        $cycle_index_2=0;
        while( list($key_2, $value_2)=each($value_array) ) {
        
	    $prefix='&';
	    if( $cycle_index===0 && $cycle_index_2===0 && $parameters_separator_found===false ) $prefix='?';
	    $url_used.=$prefix.$key.$braces_postfix.'='.$value_2;
	    
	    $cycle_index_2++;
	}

      }
      
      $cycle_index++;
    }
  
  }
  else {
  
    $record['parameters']=strval($parameters);
    
    if( $send_method_used==='GET' ) {
    
      if( $parameters_separator_found ) {
	$url_used.='&'.strval($parameters);
      }
      else {
 	$url_used.='?'.strval($parameters);     
      }
    
    }
    
  }
  
  if( strlen($log_path)>0 ) write_log($record, $log_path);
       
  $curl = curl_init();

  if( is_array($header) ) {
    $headers=$header;
  }
  elseif( is_array($parameters) ) {
    $headers[] = "Content-Type: multipart/form-data";
  }
  else {
    $headers[] = "Content-Type: application/x-www-form-urlencoded";
  }
  
  
  // Request
  curl_setopt($curl, CURLOPT_URL, $url_used);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_COOKIEFILE, $coockie_path);
  curl_setopt($curl, CURLOPT_COOKIEJAR, $coockie_path);
  
  if( $send_method_used==='POST' ) {
    curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST,'POST');
  }
  
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);

  $return_result=curl_exec($curl);
  $return_code=curl_getinfo($curl, CURLINFO_HTTP_CODE); 
  curl_close($curl);
  
  $return_code_numeric=(int)$return_code;
  $request_status_code=$return_code_numeric;
  
  $errors=array(
    301=>'Moved permanently',
    400=>'Bad request',
    401=>'Unauthorized',
    403=>'Forbidden',
    404=>'Not found',
    500=>'Internal server error',
    502=>'Bad gateway',
    503=>'Service unavailable'
  );
  
  $status_text='';
  
  if( $return_code_numeric!=200
      && $return_code_numeric!=201
      && $return_code_numeric!=202    
      && $return_code_numeric!=204 ) {
      
    $status_text='';
    if( isset($errors[$return_code_numeric]) ) {
        $status_text='Error: '.$errors[$return_code_numeric].' code: '.$return_code.' result: '.substr($return_result, 1, 300);
    }
    else {
        $status_text='Error code: '.$return_code.' result: '.substr($return_result, 1, 300);
    }
        
    $result=false;
  }
  else {
    $status_text='success code: '.$return_code.' result: '.substr($return_result, 1, 300);
    $result=$return_result;      
  }
  
  if( strlen($log_path)>0 ) write_log($status_text, $log_path);
  
  return($result);

}


class amocrm_http_requester {

  // required parameters
  public $USER_LOGIN;
  public $USER_HASH;
  public $amocrm_account;
  public $coockie_file;
  
  // optional parameters
  public $send_method;
  public $url;
  public $parameters;
  public $log_file;
  public $header;
  public $max_number_get_parameters;
  public $max_number_rows;
  public $max_number_request_cycles;
  public $sleep_time_after_request_microsec;
  public $request_status_code;
  public $lock_database_connection;
  public $lock_priority;
  public $time_interval_between_lock_tries_sec;
  public $max_wait_time_for_lock_sec;
  public $max_number_cycles_for_lock;
  
  // class use
  public $connected;
  
  
  function __construct() {
      $this->connected=false;
      $this->max_number_get_parameters=490;
      $this->max_number_rows=490;
      $this->max_number_request_cycles=21;
      $this->sleep_time_after_request_microsec=300000;
      $this->time_interval_between_lock_tries_sec=0.1;
      $this->max_wait_time_for_lock_sec=10;
      $this->max_number_cycles_for_lock=1000;
  }
  
  public function connect($without_lock=false) {
  
    $result=false;

    // Authorization 
    $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/auth.php?type=json';
    $parameters=array('USER_LOGIN'=>$this->USER_LOGIN, 'USER_HASH'=>$this->USER_HASH);

    $return_result=false;
    $min_time_from_last_lock_sec=0;
    if( ($this->sleep_time_after_request_microsec)>0 ) $min_time_from_last_lock_sec=($this->sleep_time_after_request_microsec)/1000000;
    
    $lock_priority=0;
    if( isset($this->lock_priority) ) $lock_priority=($this->lock_priority);
    
    $db_conn=($this->lock_database_connection);
    $lock_status=true;
    if( $without_lock===false
        && isset($db_conn) ) {
            
           $lock_status=lock_database($db_conn,
                                      '',
                                      $min_time_from_last_lock_sec,
                                      $this->time_interval_between_lock_tries_sec,
                                      $this->max_wait_time_for_lock_sec,
                                      $lock_priority,
                                      $this->max_number_cycles_for_lock,
                                      0.0,
                                      $this->amocrm_account);
    }
    
    if( $lock_status===true ) {
        
        $auth_header='Content-Type: multipart/form-data';
        $return_result=amocrm_request('POST', $url, $parameters, $this->log_file, $this->coockie_file, $auth_header);
        
        if( $without_lock===false
            && isset($db_conn) ) {
                
            unlock_database($db_conn, '', $this->amocrm_account);
        }
    }
    
    if( $return_result!==false ) {
    
      $decoded_result=json_decode($return_result, true);
      $response=$decoded_result['response'];
      
      if( isset($response['auth']) ) {
    	$result=true;
    	$this->connected=true;
    	write_log('Authorization: ок', $this->log_file);
      }
    
    }
    else {
      write_log('Authorization: failed', $this->log_file);      
    }
    
    if( $without_lock===true
        || !isset($db_conn) ) {
            
        usleep($this->sleep_time_after_request_microsec);
    }    
	  
    return($result);    
  }
  
  public function request() {
  
    $result='';

    // Request
    $request_parameters=array();
    $continue_cycle=false;
    $sum_number_objects=0;
    $cycle_counter=0;
    $divided_request_result_array=false;
    
    $divided_request=false;
    $limited_request=false;
    
    $request_is_failed=false;
    
    $initial_parameters=$this->parameters;
    if( (is_array($initial_parameters)
         && array_key_exists('id', $initial_parameters))
         || stripos($this->url, 'id=')!==false ) {
            
         if( is_array( $initial_parameters['id'] )
             && count( $initial_parameters['id'] )>($this->max_number_get_parameters) ) {   
            
             $divided_request=true;
             $continue_cycle=true;
         }        
    }
    else {
    
        $url_limit_request_array=array();
        $url_limit_request_array[]='/contacts/list';
        $url_limit_request_array[]='/leads/list';
        $url_limit_request_array[]='/company/list';
        $url_limit_request_array[]='/customers/list';
        $url_limit_request_array[]='/transactions/list';
        $url_limit_request_array[]='/tasks/list';
        $url_limit_request_array[]='/notes/list';
        
        // If request is not limited, limit it
        if( strpos($this->url, 'limit_rows')===false
            && strpos($this->url, 'limit_offset')===false
            && ( !is_array($this->parameters)
                 || ( !array_key_exists('limit_rows', $this->parameters)
                      && !array_key_exists('limit_offset', $this->parameters) ) ) ) {
                     
            reset($url_limit_request_array);
            while( list($key, $value)=each($url_limit_request_array) ) {
                if( strpos($this->url, $value)!==false ) {
                    $limited_request=true;
                    $continue_cycle=true;
                    break;
                }
            }
                
        }
    
    }
    
    
    while( true ) {
    
        $cycle_counter+=1;
        
        if( $divided_request===true ) {
                
            $request_parameters=array();
            $request_parameters=array_merge($request_parameters, $initial_parameters);
            
            $id_array=array();
            $uppper_index=min($sum_number_objects+$this->max_number_get_parameters-1, count($initial_parameters['id'])-1);
            for( $i=$sum_number_objects; $i<=$uppper_index; $i++ ) {
                $id_array[]=$request_parameters['id'][$i]; 
            }
            
            $request_parameters['id']=$id_array;
            $sum_number_objects+=count($id_array);
            
            if( $sum_number_objects>=count( $initial_parameters['id'] ) ) $continue_cycle=false;
        }
        elseif( $limited_request===true ) {
            
            $request_parameters=$initial_parameters;
            $request_parameters['limit_rows']=$this->max_number_rows;
            $request_parameters['limit_offset']=($this->max_number_rows)*($cycle_counter-1);
                        
        }
        else {
            $request_parameters=$initial_parameters;       
        }
        
        $return_result=false;
        $min_time_from_last_lock_sec=0;
        if( ($this->sleep_time_after_request_microsec)>0 ) $min_time_from_last_lock_sec=($this->sleep_time_after_request_microsec)/1000000;
        
        $lock_priority=0;
        if( isset($this->lock_priority) ) $lock_priority=($this->lock_priority);
        
        $db_conn=($this->lock_database_connection);
        $lock_status=true;
        if( isset($db_conn) ) {
           $lock_status=lock_database($db_conn, '', $min_time_from_last_lock_sec,
                                      $this->time_interval_between_lock_tries_sec, $this->max_wait_time_for_lock_sec, $lock_priority,
                                      $this->max_number_cycles_for_lock, 0.0, $this->amocrm_account);
           
           if( $lock_status===false ) {
              write_log('lock is failed', $this->log_file);
           }
           
        }
        
        if( $lock_status===true ) {
            
            $return_result=amocrm_request($this->send_method, $this->url, $request_parameters, $this->log_file, $this->coockie_file, $this->header, $this->request_status_code);
            
            if( is_numeric($this->request_status_code)
                && ($this->request_status_code)===401 ) {
                    
                $this->connected=false;
                                
                $this->connect(true);
                $return_result=amocrm_request($this->send_method, $this->url, $request_parameters, $this->log_file, $this->coockie_file, $this->header, $this->request_status_code);
            }
            
            if( isset($db_conn) ) {
                unlock_database($db_conn, '', $this->amocrm_account);
            }    
        }
        
        if( $return_result!==false ) {
            
          if( $divided_request===true
              || $limited_request===true ) {
              
              $divided_request_result=json_decode($return_result, true);
              if( is_array($divided_request_result_array)
                  && array_key_exists('response', $divided_request_result_array) ) {
                  
                  if( is_array($divided_request_result)
                      && array_key_exists('response', $divided_request_result) )  {
                      
                      $divided_request_result_array_response=$divided_request_result_array['response'];
                      reset($divided_request_result_array_response);    
                      while( list($key, $value)=each( $divided_request_result_array_response ) ) {
                          if( array_key_exists($key, $divided_request_result['response'])
                              && !is_numeric($key)
                              && is_array($divided_request_result_array['response'][$key])
                              && is_array($divided_request_result['response'][$key]) ) {
                                  
                              $divided_request_result_array['response'][$key]=array_merge($divided_request_result_array['response'][$key], $divided_request_result['response'][$key]);
                          }
                      }
                  }
              }
              elseif( is_array($divided_request_result)
                      && array_key_exists('response', $divided_request_result) ) {
                  
                  $divided_request_result_array=$divided_request_result;
              }
              
              if( $limited_request===true ) {
                                        
                  if( !is_array($divided_request_result)
                      || !array_key_exists('response', $divided_request_result)
                      || !is_array($divided_request_result['response'])
                      || count($divided_request_result['response'])===0 ) {
                          
                      $continue_cycle=false;
                  }
                  else {
                      
                      $array_of_objects_is_found=false;
                      reset($divided_request_result['response']);
                      while( list($key, $value)=each($divided_request_result['response']) ) {
                          if( !is_numeric($key)
                              && is_array($value) ) {
                           
                              $array_of_objects_is_found=true;
                                                                    
                              if( count($value)<($this->max_number_rows) ) {
                                  $continue_cycle=false;
                                  break;
                              }                                                           
                          }                          
                      }
                      
                      if( $array_of_objects_is_found===false ) $continue_cycle=false;                                           
                  }                               
              }
              
          }
          else {
            $continue_cycle=false;
            $result=$return_result;
          }  
            
        }
        else {
            $continue_cycle=false;
            $result=$return_result;
            $request_is_failed=true;
        }
        
        if( !isset($db_conn) ) {
            usleep($this->sleep_time_after_request_microsec);
        }
        
        if( $cycle_counter>=($this->max_number_request_cycles) ) $continue_cycle=false;
        
        if( $continue_cycle!==true ) {
            break;
        }
        
    }
    
    if( ( $divided_request===true
          || $limited_request===true )
         && is_array($divided_request_result_array) ) {
        
        $result=json_encode($divided_request_result_array);
    }
    
    if( $request_is_failed===true ) {
        $result=false;
        write_log('request failed', $this->log_file);
    }
	  
    return($result);     
  }
  
  
}  


function get_user_internal_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_phone, $amocrm_http_requester=null,
				                 $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {

   $result='';
   
   $user_phone='';
   if( strlen($user_id)>=3 ) {

      $http_requester=null;
      if( is_null($amocrm_http_requester) ) {
    	 $http_requester=new amocrm_http_requester;
    	 $http_requester->{'USER_LOGIN'}=$user_login;
    	 $http_requester->{'USER_HASH'}=$user_hash;
    	 $http_requester->{'amocrm_account'}=$amocrm_account;
    	 $http_requester->{'coockie_file'}=$coockie_file;
    	 $http_requester->{'log_file'}=$log_file;
      }
      else {
    	 $http_requester=$amocrm_http_requester;
      }
      
      $http_requester->{'send_method'}='GET';
      $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';

      $parameters=array();
      $parameters['type']='contact';
      $parameters['query']=urlencode($user_id);  
      $http_requester->{'parameters'}=$parameters;

      $return_result=$http_requester->request();
      
      if( $return_result===false ) $error_status=true;
      
      if( $return_result!==false ) {
      
    	 $decoded_result=json_decode($return_result, true);
    	 $response=$decoded_result['response'];
    
    	 $client_contact=null;
    	 if( isset($response['contacts'])
    	       && count($response['contacts'])>0 ) {
    	       
    	    $client_contacts=$response['contacts'];
    	    reset($client_contacts);
    	    while( list($key, $value)=each($client_contacts) ) {
    	    
    	       if( is_array($value)
    		     && isset($value['custom_fields'])
    		     && count($value['custom_fields'])>0 ) {
    		     
    		  $custom_fields=$value['custom_fields'];
    		  reset($custom_fields);
    		  while( list($key_2, $value_2)=each($custom_fields) ) {
    
    		     if( is_array($value_2)
    			&& isset($value_2['id'])
    			&& strVal($value_2['id'])===strVal($custom_field_user_amo_crm)
    			&& is_array($value_2['values'])
    			&& strVal($value_2['values'][0]['value'])===strVal($user_id) ) {
    			
    			$client_contact=$value;
    			break;
    		     }
    		     
    		  }
    				 
    	       }
    	       
    	       if( !is_null($client_contact) ) break;
    	    
    	    }
    	    
    	    
    	 } // search for contact
    	 
    	 // search for user phone
    	 if( is_array($client_contact)
    	       && isset($client_contact['custom_fields'])
    	       && count($client_contact['custom_fields'])>0 ) {
    	       
    	    $custom_fields=$client_contact['custom_fields'];
    	    reset($custom_fields);
    	    while( list($key, $value)=each($custom_fields) ) {
    	       
    	       if( is_array($value)
    		  && isset($value['id'])
    		  && strVal($value['id'])!==strVal($custom_field_user_phone) ) continue; 
    
    	       $values_array=$value['values'];	  
    	       
    	       if( is_array($values_array)
    		  && count($values_array)>0
    		  && isset($values_array[0]['value']) ) {
    	       
    		  $user_phone=$values_array[0]['value'];
    		  break;	    
    	       }
    		  
    	    } 
    			   
    	 } // search for user phone

      } // request ok

   } // user_id ok
  
   $result=$user_phone;
   
   return($result);
}


function get_user_info_by_user_phone($user_phone, $custom_field_user_amo_crm, $custom_field_user_phone, $amocrm_http_requester=null,
                                     $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {

   global $custom_field_user_pipeline_id;
   global $custom_field_user_pipeline_status_id;  
                                        
   $result=array();

   if( strlen($user_phone)>=3 ) {

      $http_requester=null;
      if( is_null($amocrm_http_requester) ) {
    	 $http_requester=new amocrm_http_requester;
    	 $http_requester->{'USER_LOGIN'}=$user_login;
    	 $http_requester->{'USER_HASH'}=$user_hash;
    	 $http_requester->{'amocrm_account'}=$amocrm_account;
    	 $http_requester->{'coockie_file'}=$coockie_file;
    	 $http_requester->{'log_file'}=$log_file;
      }
      else {
    	 $http_requester=$amocrm_http_requester;
      }
      
      $http_requester->{'send_method'}='GET';
      $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';

      $parameters=array();
      $parameters['type']='contact';
      $parameters['query']=urlencode($user_phone); 
      
      $http_requester->{'parameters'}=$parameters;

      $return_result=$http_requester->request();
      
      if( $return_result===false ) $error_status=true;
      
      if( $return_result!==false ) {
      
    	 $decoded_result=json_decode($return_result, true);
    	 $response=$decoded_result['response'];
    
    	 $client_contact=null;
    	 if( isset($response['contacts'])
    	       && count($response['contacts'])>0 ) {
    	       
    	    $client_contacts=$response['contacts'];
    	    reset($client_contacts);
    	    while( list($key, $value)=each($client_contacts) ) {
    	    
    	       if( is_array($value)
    		     && isset($value['custom_fields'])
    		     && count($value['custom_fields'])>0 ) {
    		     
    		  $custom_fields=$value['custom_fields'];
    		  reset($custom_fields);
    		  while( list($key_2, $value_2)=each($custom_fields) ) {
    	       
    		     if( is_array($value_2)
    			&& isset($value_2['id'])
    			&& strVal($value_2['id'])===strVal($custom_field_user_phone)
    			&& is_array($value_2['values'])
    			&& strpos($value_2['values'][0]['value'], $user_phone)!==false ) {
    			
    			$client_contact=$value;
    			break;
    		     }
    		     
    		  }
    				 
    	       }
    	       
    	       if( !is_null($client_contact) ) break;
    	    
    	    }
    	    
    	    
    	 } // search for contact
    
    	 
    	 // search for user_id
    	 if( is_array($client_contact)
    	       && isset($client_contact['custom_fields'])
    	       && count($client_contact['custom_fields'])>0 ) {
    	       
    	    $custom_fields=$client_contact['custom_fields'];
    	    reset($custom_fields);
    	    while( list($key, $value)=each($custom_fields) ) {
    	       
    	       if( is_array($value)
          		  && isset($value['id'])
          		  && strVal($value['id'])===strVal($custom_field_user_amo_crm) ) { 
          
       	       $values_array=$value['values'];
       	       
       	       if( is_array($values_array)
             		  && count($values_array)>0
             		  && isset($values_array[0]['value']) ) {
       	       
             		  $user_id=$values_array[0]['value'];
             		  $result["user_id"]=strVal($user_id);  
       	       }       	       
    	       
          	 }
          	 
          	 if( is_array($value)
          	    && isset($value['id'])
          	    && isset($custom_field_user_pipeline_id)
          	    && strVal($value['id'])===strVal($custom_field_user_pipeline_id) ) {
          	       
       	       $values_array=$value['values'];
       	       
       	       if( is_array($values_array)
       	          && count($values_array)>0
       	          && isset($values_array[0]['value']) ) {
       	             
    	             $pipeline_id=$values_array[0]['value'];
    	             $result["pipeline_id"]=strVal($pipeline_id);
    	          }
       	          
       	    }
       	    
       	    if( is_array($value)
       	       && isset($value['id'])
       	       && isset($custom_field_user_pipeline_status_id)
       	       && strVal($value['id'])===strVal($custom_field_user_pipeline_status_id) ) {
       	          
    	          $values_array=$value['values'];
    	          
    	          if( is_array($values_array)
    	             && count($values_array)>0
    	             && isset($values_array[0]['value']) ) {
    	                
    	             $pipeline_status_id=$values_array[0]['value'];
    	             $result["pipeline_status_id"]=strVal($pipeline_status_id);
 	             }
 	             
    	       }
       	    
       	    
    		  
      	 } 
    			   
    	 } // search for user_id
    	 
    	 // search for other info
    	 if( is_array($client_contact) ) {	    
    	    if( array_key_exists("name", $client_contact) ) $result["name"]=$client_contact["name"];
    	    if( array_key_exists("id", $client_contact) ) $result["contact_id"]=strVal($client_contact["id"]);	 
    	    if( array_key_exists("responsible_user_id", $client_contact) ) $result["responsible_user_id"]=strVal($client_contact["responsible_user_id"]);	 
    	 }

      } // request ok

   } // user_id ok
   
   return($result);
}


function get_contact_info($parameters='', $amocrm_http_requester=null,
                          $amocrm_account=null, $coockie_file=null, $log_file=null,
                          $user_login=null, $user_hash=null, &$error_status=false, $result_type='') {
				   
   $result=array();
   
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   } 
   
   $contacts_array=array();
   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result)
	  && array_key_exists('response', $decoded_result)
          && is_array($decoded_result['response'])
          && array_key_exists('contacts', $decoded_result['response'])
          && is_array($decoded_result['response']['contacts'])
          && count($decoded_result['response']['contacts'])>0 ) {
	    
    	 $client_contacts=$decoded_result['response']['contacts'];
    	 reset($client_contacts);
    	 while( list($key, $value)=each($client_contacts) ) {
    	    $contact_data=array();
    	    $contact_data['contact_id']=strval($value['id']);
    	    $contact_data['company_id']=strval($value['linked_company_id']);
    	    $contact_data['name']=strval($value['name']);
    	    $contact_data['last_modified']=strval($value['last_modified']);
    	    $contact_data['linked_leads_id']=$value['linked_leads_id'];
    	    $contact_data['user_id']=$value['responsible_user_id'];
    	    $contact_data['date_create']=$value['date_create'];
          $contact_data['custom_fields']=$value['custom_fields'];
    	    
    	    $contacts_array[ intval($value['id']) ]=$contact_data;	 
    	 }
	 
      }
      
   }
   
   ksort($contacts_array, SORT_NUMERIC);
   reset($contacts_array);   
   
   if( $result_type==='json' ) {
      $result=($return_result===false ? '':$return_result);
   }
   else {
      $result=$contacts_array;
   }
   
   return($result);
}


function get_company_info($parameters='', $amocrm_http_requester=null,
                          $amocrm_account=null, $coockie_file=null, $log_file=null,
                          $user_login=null, $user_hash=null, &$error_status=false, $result_type='') {

   global $custom_field_do_not_create_lead;
				   
   $result=array();
   
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }  
				   
   $companies_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result) 
   	    && array_key_exists('response', $decoded_result)
   	    && is_array($decoded_result['response'])
   	    && array_key_exists('contacts', $decoded_result['response'])
   	    && is_array($decoded_result['response']['contacts'])
   	    && count($decoded_result['response']['contacts'])>0 ) {
	    
      	 $company_contacts=$decoded_result['response']['contacts'];
      	 reset($company_contacts);
      	 while( list($key, $value)=each($company_contacts) ) {
      
      	    $company_data=array();
      	    $company_data['company_id']=strval($value['id']);
      	    $company_data['name']=strval($value['name']);
      	    $company_data['date_create']=strval($value['date_create']);
      	    $company_data['create_lead']=true;
      	    
      	    if( isset($custom_field_do_not_create_lead)
      	        && strlen($custom_field_do_not_create_lead)>0 ) {
      	    
      	       $custom_fields=$value['custom_fields'];
      
      	       reset($custom_fields);
      	       while( list($key_2, $value_2)=each($custom_fields) ) {
      		  
          		  if( is_array($value_2)
          		     && array_key_exists('id', $value_2)
          		     && strval($value_2['id'])===$custom_field_do_not_create_lead
          		     && array_key_exists('values', $value_2)
          		     && is_array($value_2['values'])
          		     && count($value_2['values'])>0
          		     && is_array($value_2['values'][0])
          		     && array_key_exists('value', $value_2['values'][0])
          		     && strval($value_2['values'][0]['value'])==='1' ) {
          		     
          		     $company_data['create_lead']=false;
          		     break;
          		  }
      	       
      	       }
      	       
      	    }
      	 
      	    $companies_array[ intval($value['id']) ]=$company_data;	 
      	 }
	 
      } 

   }
   
   ksort($companies_array, SORT_NUMERIC);
   reset($companies_array);      
				   
   if( $result_type==='json' ) {
      $result=($return_result===false ? '':$return_result);
   }
   else {
      $result=$companies_array;
   }
				   
   return($result);
				   
}


function get_leads_info($parameters='', $amocrm_http_requester=null,
                        $amocrm_account=null, $coockie_file=null, $log_file=null,
                        $user_login=null, $user_hash=null, &$error_status=false, $result_type='') {

   $result=array();
   
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }
   
   $leads_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/leads/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request(); 
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result) 
      	  && array_key_exists('response', $decoded_result)
      	  && is_array($decoded_result['response'])
      	  && array_key_exists('leads', $decoded_result['response'])
      	  && is_array($decoded_result['response']['leads'])
      	  && count($decoded_result['response']['leads'])>0 ) {
	    
      	 $leads=$decoded_result['response']['leads'];
      	 
      	 $server_time=0;
      	 if( array_key_exists('server_time', $decoded_result['response'])
      	     && is_numeric($decoded_result['response']['server_time']) ) $server_time=intVal($decoded_result['response']['server_time']);
	 
      	 reset($leads);
      	 while( list($key, $value)=each($leads) ) {
      
      	    $lead_data=array();
      	    $lead_data['lead_id']=strval($value['id']);
      	    $lead_data['status_id']=strval($value['status_id']);
      	    $lead_data['contact_id']=strval($value['main_contact_id']);
      	    $lead_data['company_id']=strval($value['linked_company_id']);
      	    $lead_data['user_id']=strval($value['responsible_user_id']);
      	    $lead_data['date_create']=intVal($value['date_create']);
      	    $lead_data['name']=strVal($value['name']);
      	    $lead_data['pipeline_id']=strVal($value['pipeline_id']);
      	    $lead_data['last_modified']=intVal($value['last_modified']);
      	    $lead_data['server_time']=intVal($server_time);
      	    $lead_data['custom_fields']=$value['custom_fields'];
      	    
      	 
      	    $leads_array[ intval($value['id']) ]=$lead_data;	 
      	 }
	 
      } 

   }
   
   ksort($leads_array, SORT_NUMERIC);
   reset($leads_array);      

   if( $result_type==='json' ) {
      $result=($return_result===false ? '':$return_result);
   }
   else {
      $result=$leads_array;
   }
				   
   return($result);
				   
}


function get_companies_info($parameters='', $amocrm_http_requester=null,
                            $amocrm_account=null, $coockie_file=null, $log_file=null,
                            $user_login=null, $user_hash=null, &$error_status=false, $result_type='') {

    $result=array();
   
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }  
   
   $companies_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
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
      
      	    $company_data=array();
      	    $company_data['company_id']=strval($value['id']);
      	    $company_data['name']=strval($value['name']);
      	    $company_data['user_id']=strval($value['responsible_user_id']);
      	    $company_data['date_create']=strval($value['date_create']);
      	    $company_data['custom_fields']=$value['custom_fields'];
      	    $company_data['linked_leads_id']=$value['linked_leads_id'];
      	    $company_data['last_modified']=strval($value['last_modified']);
      	 
      	    $companies_array[ intval($value['id']) ]=$company_data;	 
      	 }
	 
      } 

   }
   
   ksort($companies_array, SORT_NUMERIC);
   reset($companies_array);      
	
   if( $result_type==='json' ) {
      $result=($return_result===false ? '':$return_result);     
   }
   else {
      $result=$companies_array;
   }
				   
   return($result);
				   
}


function create_lead($name, $status_id, $responsible_user_id, $company_id, $fields=array(), $amocrm_http_requester=null,
                     $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {

   global $custom_field_address_type;
   global $custom_field_first_called_number;
   global $custom_field_comment;
   global $custom_field_web_site;
		    
   $result=false;

   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   } 
   
   $parameters=array();
   $parameters['request']['leads']['add']=array(
	 array(
	    'name'=>$name,
	    'status_id'=>intval($status_id),
	    'responsible_user_id'=>intval($responsible_user_id),
	    'linked_company_id'=>strval($company_id),
	    'created_user_id'=> 0
	)
   );
   
   $parameters['request']['leads']['add'][0]['custom_fields']=array();
   
   reset($fields);
   while( list($key, $value)=each($fields) ) {
            
       if( isset($custom_field_address_type)
           && strVal($key)===strVal($custom_field_address_type) ) {
                              
           $parameters['request']['leads']['add'][0]['custom_fields'][]=
               array(
                   'id'=>intval($custom_field_address_type),
                   'values'=>array(
                       array(
                           'enum'=>intval($value['value']),
                           'value'=>strval($value['value_string'])
                       )
               )
           );
                             
       }
           
       if( isset($custom_field_first_called_number)
           && strVal($key)===strVal($custom_field_first_called_number) ) {
                             
           $parameters['request']['leads']['add'][0]['custom_fields'][]=
           array(
               'id'=>intval($custom_field_first_called_number),
               'values'=>array(                  
                   array(
                       'value'=>strval($value['value'])
                   )
               )
           );
               
        }
        
        if( isset($custom_field_comment)
           && strVal($key)===strVal($custom_field_comment) ) {
              
           $parameters['request']['leads']['add'][0]['custom_fields'][]=
           array(
              'id'=>intval($key),
              'values'=>array(
                 array(
                    'value'=>strval($value['value'])
                 )
              )
           );
           
        }
        
        if( isset($custom_field_web_site)
            && strVal($key)===strVal($custom_field_web_site) ) {
              
           $parameters['request']['leads']['add'][0]['custom_fields'][]=
           array(
              'id'=>intval($key),
              'values'=>array(
                 array(
                    'value'=>strval($value['value'])
                 )
              )
           );
           
        }
        
        if( is_array($value)
            && is_numeric($key)
            && array_key_exists('element_type', $value)
            && $value['element_type']==='lead' ) {
              
              $parameters['request']['leads']['add'][0]['custom_fields'][]=
              array(
                 'id'=>intval($key),
                 'values'=>array(
                    array(
                       'value'=>strval($value['value_string'])
                    )
                 )
              );
              
         }
          
   }
      
      
   $parameters_json=json_encode($parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/leads/set';
   $http_requester->{'parameters'}=$parameters_json;
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   $result=$return_result;
   
   return($result);
   
}


function update_leads_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null,
                           $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {
      
   $result=false;
   
   $current_time=time();
   
   // Request last modification
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }
   
   $objects_update=array();
   $objects_info=null;
   

   $objects_info=get_leads_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   
   if( $error_status===true ) return($result);

   
   $all_requests_result=false;
   $max_number_elements_by_cycle=490;
   if( is_numeric($http_requester->max_number_rows) ) {
      $max_number_elements_by_cycle=($http_requester->max_number_rows);
   }
   
   if( is_array($objects_info)
      && count($objects_info)>0 ) {
         
      $all_requests_result=true;
      
      $num_cycles=intVal(ceil( count($objects_info)/$max_number_elements_by_cycle ));
      
      $counter=0;
      $cycle_counter=0;
      $number_elements=count($objects_info);
      
      reset($objects_info);
      while( list($key, $value)=each($objects_info) ) {
         
         $counter+=1;
         $cycle_counter+=1;
         
         $object_data=array();
         
         // required properties
         $object_data['id']=intval($value['lead_id']);
         
         $last_modified=$current_time;
         if( array_key_exists('server_time', $value)
            && is_numeric($value['server_time'])
            && intVal($value['server_time'])>$current_time ) {
               
            $last_modified=intVal($value['server_time']);
         }         
         
         if( is_array($value)
            && array_key_exists('last_modified', $value)
            && is_numeric($value['last_modified'])
            && intVal($value['last_modified'])>$last_modified ) $last_modified=intVal($value['last_modified']);
         
         $object_data['updated_at']=($last_modified+1);
         
         $object_id_numeric=intVal($value['lead_id']);
         reset($updated_fields);
         if( array_key_exists($object_id_numeric, $updated_fields)
            && is_array($updated_fields[$object_id_numeric]) ) {
               
            $object_update_fields=$updated_fields[$object_id_numeric];
            reset($object_update_fields);
            while( list($key_2, $value_2)=each($object_update_fields) ) {
               $object_data[$key_2]=$value_2;
            }
            
         }
         
         $notes_update[]=$object_data;
         if( $cycle_counter>=$max_number_elements_by_cycle
            || $counter>=$number_elements ) {
               
            // Update contact data
            $cycle_result=false;
            $request_parameters=array();
            $request_parameters['update'][]=$object_data;
            $request_parameters_json=json_encode($request_parameters);
            
            $http_requester->{'send_method'}='POST';
            $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/api/v2/leads';
            $http_requester->{'parameters'}=$request_parameters_json;
            $http_requester->{'header'}=array('Content-Type: application/json');
            
            $return_result=$http_requester->request();
            $http_requester->{'header'}='';
            
            if( $return_result===false ) $error_status=true;
            
            if( $return_result===false ) return($result);
            
            if( $return_result!==false ) {
               
               $decoded_result=json_decode($return_result, true);
               if( ( is_array($decoded_result)
                     && array_key_exists('response', $decoded_result)
                     && is_array($decoded_result['response'])
                     && array_key_exists('leads', $decoded_result['response'])
                     && is_array($decoded_result['response']['leads'])
                     && count($decoded_result['response']['leads'])>0 )
                    || ( is_array($decoded_result)
                         && array_key_exists('_embedded', $decoded_result)
                         && is_array($decoded_result['_embedded'])
                         && array_key_exists('items', $decoded_result['_embedded'])
                         && is_array($decoded_result['_embedded']['items'])
                         && count($decoded_result['_embedded']['items'])>0 ) ) {
                     
                  $cycle_result=true;
               }
                  
            }
            
            $cycle_counter=0;
            $notes_update=array();
            $all_requests_result=( $all_requests_result && $cycle_result );
         }
                  
      }
      
   }
   
   
   $result=$all_requests_result;
   
   return($result);
}


function update_contact_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null,
                             $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {
				   
   $result=false;
   
   // Request last modification
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }
   
   $contact_id='';
   $last_modified=0;
   $linked_leads_id=null;
   $custom_fields=null;
   
   $contacts_info=get_contact_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   
   if( $error_status===true ) return($result);
   
   if( is_array($contacts_info)
       && count($contacts_info)>0 ) {
      
      reset($contacts_info);
      while( list($key, $value)=each($contacts_info) ) {
      
	 if( is_array($value)
	     && array_key_exists('last_modified', $value)
	     && is_numeric($value['last_modified'])
	     && $value['last_modified']>$last_modified ) $last_modified=intVal($value['last_modified']);
	     
	 if( is_array($value)
	     && array_key_exists('contact_id', $value) ) $contact_id=$value['contact_id'];
	     
	 if( is_array($value['linked_leads_id']) ) $linked_leads_id=$value['linked_leads_id'];
	 
	 if( is_array($value)
	     && array_key_exists('custom_fields', $value) ) $custom_fields=$value['custom_fields'];
	     
    	 break;
      }
      
   }
   
   if( $last_modified===0 ) $last_modified=time();
   
   if( strlen($contact_id)===0
       || !is_numeric($contact_id) ) return($result);
      
   

   // Update contact data
   $request_parameters=array();
   $request_parameters['request']['contacts']['update']=array(
							       array(
								     'id'=>intVal($contact_id),
								     'last_modified'=>(intVal($last_modified)+1)
							       )
							 );

							
   reset($updated_fields);
   while( list($key, $value)=each($updated_fields) ) {
      
      if( Trim($key)==='linked_leads_id' ) {
	 
      	 if( is_array($linked_leads_id) 
      	     && is_array($value) ) {
      	     
      	    $request_parameters['request']['contacts']['update'][0]['linked_leads_id']=array_merge($linked_leads_id, $value);
      	 }   
	    
      }
      elseif( Trim($key)==='id' ) {
         // nothing
      }
      elseif( !is_numeric($key) ) {        
	        $request_parameters['request']['contacts']['update'][0][$key]=$value;
      }
      elseif( is_numeric($key) ) {

           if( !isset($request_parameters['request']['contacts']['update'][0]['custom_fields']) )  
                $request_parameters['request']['contacts']['update'][0]['custom_fields']=array();
                 
           $request_parameters['request']['contacts']['update'][0]['custom_fields'][]=$value;      
      }
      
   }
   
   $request_parameters_json=json_encode($request_parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/set';
   $http_requester->{'parameters'}=$request_parameters_json;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result)
	  && array_key_exists('response', $decoded_result)
          && is_array($decoded_result['response'])
          && array_key_exists('contacts', $decoded_result['response'])
          && is_array($decoded_result['response']['contacts'])
          && count($decoded_result['response']['contacts'])>0 ) {
	 
    	 $result=true;	 
      }
      
   }

   return($result);
}


function update_company_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null, &$error_status=false) {
				   
   $result=false;
   
   $http_requester=$amocrm_http_requester;
   
   $company_id='';
   $last_modified=0;
   $linked_leads_id=null;
   $custom_fields=null;
   
   $companies_info=get_companies_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   
   if( $error_status===true ) return($result);
   
   if( is_array($companies_info)
       && count($companies_info)>0 ) {
      
      reset($companies_info);
      while( list($key, $value)=each($companies_info) ) {
      
      	 if( is_array($value)
      	     && array_key_exists('last_modified', $value)
      	     && is_numeric($value['last_modified'])
      	     && $value['last_modified']>$last_modified ) $last_modified=intVal($value['last_modified']);
      	     
      	 if( is_array($value)
      	     && array_key_exists('company_id', $value) ) $company_id=$value['company_id'];
      	     
      	 if( is_array($value['linked_leads_id']) ) $linked_leads_id=$value['linked_leads_id'];
      	 
      	 if( is_array($value)
      	     && array_key_exists('custom_fields', $value) ) $custom_fields=$value['custom_fields'];
	     
       	 break;
      }
      
   }
   
   if( $last_modified===0 ) $last_modified=time();
   
   if( strlen($company_id)===0
       || !is_numeric($company_id) ) return($result);
      
   

   // Update company data
   $request_parameters=array();
   $request_parameters['update']=array(
							       array(
								     'id'=>intVal($company_id),
								     'updated_at'=>(intVal($last_modified)+1)
							       )
							 );

							
   reset($updated_fields);
   while( list($key, $value)=each($updated_fields) ) {
      
      if( Trim($key)==='linked_leads_id' ) {
	 
      	 if( is_array($linked_leads_id) 
      	     && is_array($value) ) {
      	     
      	    $request_parameters['update'][0]['leads_id']=array_merge($linked_leads_id, $value);
      	 }   
	    
      }
      elseif( Trim($key)==='id' ) {
         // nothing
      }
      elseif( !is_numeric($key) ) {        
	        $request_parameters['update'][0][$key]=$value;
      }
      elseif( is_numeric($key) ) {

           if( !isset($request_parameters['update'][0]['custom_fields']) )  
                $request_parameters['update'][0]['custom_fields']=array();
                 
           $request_parameters['update'][0]['custom_fields'][]=$value;      
      }
      
   }
   
   $request_parameters_json=json_encode($request_parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/api/v2/companies';
   $http_requester->{'parameters'}=$request_parameters_json;
   $http_requester->{'header'}=array('Content-Type: application/json');
   
   $return_result=$http_requester->request();
   
   $http_requester->{'header'}='';
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      
      if( is_array($decoded_result)
          && array_key_exists('_embedded', $decoded_result)
          && is_array($decoded_result['_embedded'])
          && array_key_exists('items', $decoded_result['_embedded'])
          && is_array($decoded_result['_embedded']['items'])
          && count($decoded_result['_embedded']['items'])>0 ) {
             
   	    $result=true;             
      }
      
   }

   return($result);
}


function get_notes_info($parameters='', $amocrm_http_requester=null,
                        $amocrm_account=null, $coockie_file=null, $log_file=null,
                        $user_login=null, $user_hash=null, &$error_status=false, $result_type='') {

   $result=array();
   
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }   
   
   $elements_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
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
      
      	    $element_data=array();
      	    $element_data['note_id']=strval($value['id']);
      	    $element_data['note_type']=strval($value['note_type']);
      	    $element_data['element_id']=strval($value['element_id']);
      	    $element_data['element_type']=strval($value['element_type']);
      	    $element_data['created_user_id']=strval($value['created_user_id']);
      	    $element_data['responsible_user_id']=strval($value['responsible_user_id']);
      	    $element_data['last_modified']=strval($value['last_modified']);
      	    $element_data['date_create']=strval($value['date_create']);
      	    $element_data['text']=strval($value['text']);
      	 
      	    $elements_array[ intval($value['id']) ]=$element_data;	 
      	 }
	 
      } 

   } 
   
   ksort($elements_array, SORT_NUMERIC);
   reset($elements_array);      

   if( $result_type==='json' ) {
      $result=($return_result===false ? '':$return_result);
   }
   else {
      $result=$elements_array;
   }
				   
   return($result);		

}


function update_notes_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null,
                           $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {
      
   $result=false;
   
   // Request last modification
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }
   
   $notes_update=array();
   $notes_info=get_notes_info($parameters, $http_requester, null, null, null, null, null, $error_status);
   
   if( $error_status===true ) return($result);
   
   $all_requests_result=false;
   $max_number_elements_by_cycle=490;
   if( is_array($notes_info)
      && count($notes_info)>0 ) {
         
      $all_requests_result=true;
      
      $num_cycles=intVal(ceil( count($notes_info)/$max_number_elements_by_cycle ));
      
      $counter=0;
      $cycle_counter=0;
      $number_elements=count($notes_info);
      
      reset($notes_info);
      while( list($key, $value)=each($notes_info) ) {
         
         $counter+=1;
         $cycle_counter+=1;
         
         $note_data=array();
         $note_data['id']=intVal($value['note_id']);
         $note_data['element_id']=intVal($value['element_id']);
         $note_data['element_type']=intVal($value['element_type']);
         $note_data['created_user_id']=intVal($value['created_user_id']);
         $note_data['responsible_user_id']=intVal($value['responsible_user_id']);
         $note_data['text']=strVal($value['text']);
         $note_data['last_modified']=intVal($value['last_modified']);
         
         $last_modified=0;
         if( is_array($value)
            && array_key_exists('last_modified', $value)
            && is_numeric($value['last_modified'])
            && intVal($value['last_modified'])>$last_modified ) $last_modified=intVal($value['last_modified']);
            
            if( $last_modified===0 ) $last_modified=time();
            
            $note_data['last_modified']=($last_modified+1);
            
            $note_id_numeric=intVal($value['note_id']);
            reset($updated_fields);
            if( array_key_exists($note_id_numeric, $updated_fields) ) {
               
               reset($updated_fields[$note_id_numeric]);
               while( list($key_2, $value_2)=each($updated_fields[$note_id_numeric]) ) {
                  $note_data[$key_2]=$value_2;
               }
               
            }
            
            $notes_update[]=$note_data;
            if( $cycle_counter>=$max_number_elements_by_cycle
               || $counter>=$number_elements ) {
                  
                  
               // Update contact data
               $cycle_result=false;
               $request_parameters=array();
               $request_parameters['request']['notes']['update']=$notes_update;
               $request_parameters_json=json_encode($request_parameters);
               
               $http_requester->{'send_method'}='POST';
               $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/set';
               $http_requester->{'parameters'}=$request_parameters_json;
               
               $return_result=$http_requester->request();
               
               if( $return_result===false ) $error_status=true;
               
               if( $return_result===false ) return($result);
               
               if( $return_result!==false ) {
                  
                  $decoded_result=json_decode($return_result, true);
                  if( is_array($decoded_result)
                     && array_key_exists('response', $decoded_result)
                     && is_array($decoded_result['response'])
                     && array_key_exists('notes', $decoded_result['response'])
                     && is_array($decoded_result['response']['notes'])
                     && count($decoded_result['response']['notes'])>0 ) {
                        
                        $cycle_result=true;
                     }
                     
               }
               
               
               $cycle_counter=0;
               $notes_update=array();
               $all_requests_result=( $all_requests_result && $cycle_result );
            }
               
      }
         
   }
   
   
   $result=$all_requests_result;
   
   return($result);
}


function create_unsorted($name, $pipeline_id, $phone_from, $phone_to,
                         $contact_name, $company_id, $record_link='', $outcoming=false, $fields=array(), $amocrm_http_requester=null,
                         $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {

   global $custom_field_phone_id;
   global $custom_field_phone_enum;  
   global $custom_field_address_type;
   global $custom_field_first_called_number;
   global $custom_field_comment;
   global $custom_field_web_site;
   
		    
   $result=false;

   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }  
   
   $unsorted_id=strVal(uniqid('un', true));
   $note_id=strVal(uniqid('no', true));
   
   $date_create=time();
   
   if( !is_numeric($pipeline_id) ) $pipeline_id=0;
   if( !is_numeric($company_id) ) $company_id=0;
   
   $parameters=array();
   $parameters['request']['unsorted']=array(
        'category' => 'sip'
   );
   
   $parameters['request']['unsorted']['add']=array(
       array(
            'source'      => 'src',
            'source_uid'  => $unsorted_id,
            'date_create' => $date_create,
            'pipeline_id' => intVal($pipeline_id),
            'source_data' => array(
                'from'     => $phone_from,
                'to'       => $phone_to,
                'date'     => $date_create,
                'duration' => 0,
                'link'     => strVal($record_link),
                'service'  => 'src',
            )
       )     
    );
   
   $parameters['request']['unsorted']['add'][0]['data']=
    array(
        'leads'=>array(
            array(
                 'name' => strVal($name),
                 'linked_company_id' => intVal($company_id),           
            )
        )
    );
   
   $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields']=array();
    
   reset($fields);
   while( list($key, $value)=each($fields) ) {
       
        if( isset($custom_field_address_type)
            && strVal($key)===strVal($custom_field_address_type) ) {

           $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields'][]=
                                         array(
                                               'id'=>intval($custom_field_address_type),
                                               'values'=>array(
                                                                 array(
                                                                       'enum'=>intval($value['value']),
                                                                       'value'=>strVal($value['value_string'])
                                                                 )
                                                         )
                                         );
        }
        
        if( isset($custom_field_first_called_number)
            && strVal($key)===strVal($custom_field_first_called_number) ) {
                
           $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields'][]=
                array(
                    'id'=>intval($custom_field_first_called_number),
                    'values'=>array(
                        array(
                            'value'=>strval($value['value'])
                        )
                    )
                );
                
        }
        
        if( isset($custom_field_comment)
            && strVal($key)===strVal($custom_field_comment) ) {
                
            $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields'][]=
            array(
                'id'=>intval($key),
                'values'=>array(
                    array(
                        'value'=>strval($value['value'])
                    )
                )
            );
            
        }
        
        if( isset($custom_field_web_site)
            && strVal($key)===strVal($custom_field_web_site) ) {
                
                $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields'][]=
                array(
                    'id'=>intval($key),
                    'values'=>array(
                        array(
                            'value'=>strval($value['value'])
                        )
                    )
                );
                
        }
        
        if( is_array($value)
            && is_numeric($key)
            && array_key_exists('element_type', $value)
            && $value['element_type']==='lead') {
              
              $parameters['request']['unsorted']['add'][0]['data']['leads'][0]['custom_fields'][]=
              array(
                 'id'=>intval($key),
                 'values'=>array(
                    array(
                       'value'=>strval($value['value_string'])
                    )
                 )
              );
              
        }   
   
   }
   
   if( strlen($contact_name)>0 ) {
       
        $parameters['request']['unsorted']['add'][0]['data']['contacts']=
        array(
               array(
                    'name' => strVal($contact_name),
                    'linked_company_id' => intVal($company_id),           
               )
        );

        reset($fields);
        while( list($key, $value)=each($fields) ) {
        
            if( isset($custom_field_phone_id)
                && strVal($custom_field_phone_id)===strVal($key) ) {

                $parameters['request']['unsorted']['add'][0]['data']['contacts'][0]['custom_fields']=
                    array(
                          array(
                            'id'     => strVal($key),
                            'values' => array(
                              array(
                                'enum'  => intVal($value['value']),
                                'value' => strVal($value['value_string']),
                              ),
                            ),
                          ),
                    );             
            }
            
            if( is_array($value)
                && is_numeric($key)
                && array_key_exists('element_type', $value)
                && $value['element_type']==='contact') {
                  
                $parameters['request']['unsorted']['add'][0]['data']['contacts'][0]['custom_fields']=
                   array(
                      array(
                         'id'     => strVal($key),
                         'values' => array(
                            array(
                               'value' => strVal($value['value_string'])
                            )
                         )
                      )
                   );
                  
             } 
        
        }
        
        $parameters['request']['unsorted']['add'][0]['data']['contacts'][0]['notes']=
        array( 
              array(
                'note_type' => ( $outcoming===true ? 11: 10 ),
                'element_type' => ( strlen($contact_name)>0 ? 1:2 ),  
                'text' => json_encode(array(
                  'UNIQ' => strVal($note_id), 
                  'LINK' => strVal($record_link),
                  'PHONE' => ( $outcoming===true ? $phone_to : $phone_from ), 
                  'DURATION' => 0, 
                  'SRC' => 'src', 
                ))
             )
        );

   }
    

   
   

   $parameters_json=json_encode($parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).
                            '.amocrm.ru/api/unsorted/add/'.
                            '?api_key='.($http_requester->{'USER_HASH'}).
                            '&login='.urlencode($http_requester->{'USER_LOGIN'});
   $http_requester->{'parameters'}=$parameters_json;
   $http_requester->{'header'}=array('Content-Type: application/json');
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   $http_requester->{'header'}='';
   
   $result=$return_result;
   
   return($result);
   
}


function create_note($type=null, $lead_id=null, $contact_id=null, $company_id=null,  $text=null,
   $created_user_id=null, $responsable_user_id=null, $fields=array(), $amocrm_http_requester=null,
   $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null, &$error_status=false) {
      
   $http_requester=null;
   if( is_null($amocrm_http_requester) ) {
      $http_requester=new amocrm_http_requester;
      $http_requester->{'USER_LOGIN'}=$user_login;
      $http_requester->{'USER_HASH'}=$user_hash;
      $http_requester->{'amocrm_account'}=$amocrm_account;
      $http_requester->{'coockie_file'}=$coockie_file;
      $http_requester->{'log_file'}=$log_file;
   }
   else {
      $http_requester=$amocrm_http_requester;
   }
   
   $current_date=time();
   
   $element_type=0;
   $element_id=0;
   if( is_numeric($lead_id)
      && intVal($lead_id)>0 ) {
         
      $element_type=2;
      $element_id=intVal($lead_id);
   }
   elseif( is_numeric($contact_id)
      && intVal($contact_id)>0 ) {
         
         $element_type=1;
         $element_id=intVal($contact_id);
   }
   elseif( is_numeric($company_id)
      && intVal($company_id)>0) {
         
         $element_type=3;
         $element_id=intVal($company_id);
   }
      
   $note_type=4;
   if( is_numeric($type)
      && intVal($type)>0 ) $note_type=intVal($type);
      
   $date_create=$current_date;
   
   $note_created_user_id=0;
   if( is_numeric($created_user_id)
      && intVal($created_user_id)>0 ) $note_created_user_id=$created_user_id;
      
   $note_responsable_user_id=0;
   if( is_numeric($responsable_user_id)
      && intVal($responsable_user_id)>0 ) $note_responsable_user_id=$responsable_user_id;
      
   $note_text='';
   if( is_string($text) ) $note_text=$text;
   
   $notes['request']['notes']['add']=array(
      array(
         'element_type'=>$element_type,
         'element_id'=>$element_id,
         'note_type'=>$note_type,
         
         'date_create'=>$date_create,
         'last_modified'=>$date_create,
         'request_id'=>0,
         'created_user_id'=>$note_created_user_id,
         'responsable_user_id'=>$note_responsable_user_id,
         'text'=>$note_text
      )
   );
   
   if( is_array($fields) ) {
      
      reset($fields);
      while( list($key, $value)=each($fields) ) {
         $notes['request']['notes']['add'][0][strVal($key)]=$value;
      }
      
   }
   
   
   $parameters_json=json_encode($notes);
   
   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/set';
   $http_requester->{'parameters'}=$parameters_json;
   $http_requester->{'header'}=array('Content-Type: application/json');
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   $http_requester->{'header'}='';
   
   $result=$return_result;
   
   return($result);
}


function create_contact($http_requester, $contact_data_array, &$error_status=false) {
   
   
   global $custom_field_phone_id;
   global $custom_field_phone_enum;
   global $phone_prefix_presentation;
   global $custom_field_email_id;
   global $custom_field_email_enum;
   
   if( !isset($phone_prefix_presentation) ) $phone_prefix_presentation='';
   
   $result='';

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->amocrm_account).'.amocrm.ru/private/api/v2/json/contacts/set';
   
   $custom_fields_value=array();
   
   if( !is_array($contact_data_array) ) $contact_data_array=array();
   
   if( !array_key_exists('name', $contact_data_array)
       || strlen($contact_data_array['name'])===0 ) {
          
       write_log('create_contact: failed create contact, contact name is absent ', $http_requester->{'log_file'});
       return($result);
   }
   
   // name
   $name=$contact_data_array['name'];
   
   // phones
   $phone_array=array();
   $parsed_phone_array=array();
   if( array_key_exists('phone', $contact_data_array) ) {
      
      if( is_array($contact_data_array['phone']) ) {
         $phone_array=$contact_data_array['phone'];
      }
      else {
         $phone_array[]=strVal($contact_data_array['phone']);
      }
      
   }
   
   reset($phone_array);
   while( list($key, $value)=each($phone_array) ) {   
      $parsed_phone=$value;
      $parsed_phone=remove_symbols($parsed_phone);
      $parsed_phone=substr($parsed_phone, -10);
      
      if( strlen($parsed_phone)>0 ) {
         $parsed_phone_array[]=$parsed_phone;
      }
   }
   
   if( count($parsed_phone_array)>0
       && isset($custom_field_phone_id)
       && isset($custom_field_phone_enum) ) {
         
      $phone_values=array();
      
      reset($parsed_phone_array);
      while( list($key, $value)=each($parsed_phone_array) ) {
          $phone_values[]=array("value"=>($phone_prefix_presentation.$value), "enum"=>($custom_field_phone_enum));
      }
      
      $phones=array("id"=>$custom_field_phone_id, "values"=>$phone_values);
      
      $custom_fields_value[]=$phones;
   }
   
   // emails
   $email_array=array();
   if( array_key_exists('email', $contact_data_array) ) {
      
      if( is_array($contact_data_array['email']) ) {
         $email_array=$contact_data_array['email'];
      }
      else {
         $email_array[]=strVal($contact_data_array['email']);
      }
      
   }
   
   if( count($email_array)>0
       && isset($custom_field_email_id)
       && isset($custom_field_email_enum) ) {
         
      $email_values=array();
      while( list($key, $value)=each($email_array) ) {
         $email_values[]=array("value"=>$value, "enum"=>$custom_field_email_enum);
      }
         
      $emails=array("id"=>$custom_field_email_id, "values"=>$email_values);
      
      $custom_fields_value[]=$emails;
   }

   // addition custom fields
   reset($contact_data_array);
   while( list($key, $value)=each($contact_data_array) ) {
      
      if( is_numeric($key) ) {
         $custom_field_values=array();
         $custom_field_values[]=array("value"=>$value);
         
         $custom_field=array("id"=>strVal($key), "values"=>$custom_field_values);
         
         $custom_fields_value[]=$custom_field;
      }
      
   }
   
   // user_id
   $user_id='';
   if( array_key_exists('user_id', $contact_data_array) ) {
      $user_id=$contact_data_array['user_id'];
   }
   
   $responsible_user_id=0;
   if( isset($user_id)
       && is_numeric($user_id)
       && intVal($user_id)>0 ) {
         
       $responsible_user_id=intVal($user_id);
   }
   
   
   $add_value=array( array("name"=>$name,
                           "responsible_user_id"=>$responsible_user_id,
                           "custom_fields"=>$custom_fields_value) );
   
   $simple_fields=array();
   $simple_fields[]='linked_company_id';
   
   reset($contact_data_array);
   while( list($key, $value)=each($contact_data_array) ) {
      
      if( in_array(strVal($key), $simple_fields) ) {
         $add_value[0][strVal($key)]=strVal($value);         
      }
      
   }   
   
   $contacts_value=array( "add"=>$add_value );
   
   $request_value=array( "contacts"=>$contacts_value );
   
   $parameters=array( "request"=>$request_value );
   $parameters_json=json_encode($parameters);
   

   $http_requester->{'parameters'}=$parameters_json;
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
      $decoded_result=json_decode($return_result, true);
      $response=$decoded_result['response'];
      
      if( isset($response['contacts'])
          && isset($response['contacts']['add'])
          && count($response['contacts']['add'])>0
          && isset($response['contacts']['add'][0]['id'])
          && is_numeric($response['contacts']['add'][0]['id']) ) {
             
         $contact_id=$response['contacts']['add'][0]['id'];
         
         $result=strVal($contact_id);
         write_log('create_contact: contact '.$name.' is created', $http_requester->{'log_file'});
      }
      else {
         write_log('create_contact: failed create contact '.$name, $http_requester->{'log_file'});         
      }
   }
   
   $http_requester->{'header'}='';
   
   return($result);   
}


function create_company($http_requester, $contact_data_array, &$error_status=false) {
   
   
   global $custom_field_phone_id;
   global $custom_field_phone_enum;
   global $phone_prefix_presentation;
   global $custom_field_email_id;
   global $custom_field_email_enum;
   
   if( !isset($phone_prefix_presentation) ) $phone_prefix_presentation='';
   
   $result='';

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->amocrm_account).'.amocrm.ru/api/v2/companies';
   $http_requester->{'header'}=array('Content-Type: application/json');
   
   $custom_fields_value=array();
   
   if( !is_array($contact_data_array) ) $contact_data_array=array();
   
   if( !array_key_exists('name', $contact_data_array)
       || strlen($contact_data_array['name'])===0 ) {
          
       write_log('create_company: failed create company, company name is absent ', $http_requester->{'log_file'});
       return($result);
   }
   
   // name
   $name=$contact_data_array['name'];
   
   // phones
   $phone_array=array();
   $parsed_phone_array=array();
   if( array_key_exists('phone', $contact_data_array) ) {
      
      if( is_array($contact_data_array['phone']) ) {
         $phone_array=$contact_data_array['phone'];
      }
      else {
         $phone_array[]=strVal($contact_data_array['phone']);
      }
      
   }
   
   reset($phone_array);
   while( list($key, $value)=each($phone_array) ) {   
      $parsed_phone=$value;
      $parsed_phone=remove_symbols($parsed_phone);
      $parsed_phone=substr($parsed_phone, -10);
      
      if( strlen($parsed_phone)>0 ) {
         $parsed_phone_array[]=$parsed_phone;
      }
   }
   
   if( count($parsed_phone_array)>0
       && isset($custom_field_phone_id)
       && isset($custom_field_phone_enum) ) {
         
      $phone_values=array();
      
      reset($parsed_phone_array);
      while( list($key, $value)=each($parsed_phone_array) ) {
          $phone_values[]=array("value"=>($phone_prefix_presentation.$value), "enum"=>($custom_field_phone_enum));
      }
      
      $phones=array("id"=>$custom_field_phone_id, "values"=>$phone_values);
      
      $custom_fields_value[]=$phones;
   }
   
   // emails
   $email_array=array();
   if( array_key_exists('email', $contact_data_array) ) {
      
      if( is_array($contact_data_array['email']) ) {
         $email_array=$contact_data_array['email'];
      }
      else {
         $email_array[]=strVal($contact_data_array['email']);
      }
      
   }
   
   if( count($email_array)>0
       && isset($custom_field_email_id)
       && isset($custom_field_email_enum) ) {
         
      $email_values=array();
      while( list($key, $value)=each($email_array) ) {
         $email_values[]=array("value"=>$value, "enum"=>$custom_field_email_enum);
      }
         
      $emails=array("id"=>$custom_field_email_id, "values"=>$email_values);
      
      $custom_fields_value[]=$emails;
   }

   // addition custom fields
   reset($contact_data_array);
   while( list($key, $value)=each($contact_data_array) ) {
      
      if( is_numeric($key) ) {
         $custom_field_values=array();
         $custom_field_values[]=array("value"=>$value);
         
         $custom_field=array("id"=>strVal($key), "values"=>$custom_field_values);
         
         $custom_fields_value[]=$custom_field;
      }
      
   }
   
   // user_id
   $user_id='';
   if( array_key_exists('user_id', $contact_data_array) ) {
      $user_id=$contact_data_array['user_id'];
   }
   
   $responsible_user_id=0;
   if( isset($user_id)
       && is_numeric($user_id)
       && intVal($user_id)>0 ) {
         
       $responsible_user_id=intVal($user_id);
   }
   
   
   $add_value=array( array("name"=>$name,
                           "responsible_user_id"=>$responsible_user_id,
                           "custom_fields"=>$custom_fields_value) );
   
   $parameters=array("add"=>$add_value);
   $parameters_json=json_encode($parameters);
   

   $http_requester->{'parameters'}=$parameters_json;
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
      
      $decoded_result=json_decode($return_result, true);
      
      if( is_array($decoded_result)
          && array_key_exists('_embedded', $decoded_result)
          && is_array($decoded_result['_embedded'])
          && array_key_exists('items', $decoded_result['_embedded'])
          && is_array($decoded_result['_embedded']['items'])
          && count($decoded_result['_embedded']['items'])>0
          && is_array($decoded_result['_embedded']['items'][0])
          && array_key_exists('id', $decoded_result['_embedded']['items'][0]) ) {
          
          $contact_id=$decoded_result['_embedded']['items'][0]['id'];   
   	    $result=strVal($contact_id);
   	    
   	    write_log('create_company: company '.$name.' is created', $http_requester->{'log_file'});
      }
      else {
          write_log('create_company: failed create company '.$name, $http_requester->{'log_file'});
      }

   }
   
   $http_requester->{'header'}='';
   
   return($result);   
}


function create_note_call($http_requester, $lead_id=null, $contact_id=null, $company_id=null,
                          $callid='', $phone='', $creation_time=0, $call_status='', $call_result='',
                          $duration=0, $outcoming=false, $created_user_id=null,
                          $responsable_user_id=null, $record_link='', $fields=array(), &$error_status=false) {
   
   $result='';
   
   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.$http_requester->amocrm_account.'.amocrm.ru/private/api/v2/json/notes/set';
   
   $element_type=0;
   $element_id=0;
   if( is_numeric($lead_id)>0 ) {
      $element_type=2;
      $element_id=intVal($lead_id);
   }
   elseif( is_numeric($contact_id)>0 ) {
      $element_type=1;
      $element_id=intVal($contact_id);
   }
   elseif( is_numeric($company_id)>0 ) {
      $element_type=3;
      $element_id=intVal($company_id);
   }
   
   if( !is_numeric($creation_time)
       || intVal($creation_time)===0 ) {
   
       $creation_time=0;
   }
   
   
   $note_type=10;
   if( $outcoming===true ) $note_type=11;
   
   if( is_string($duration) ) {
      
      $loc_call_duration=remove_symbols($duration);
      
      if( strlen($loc_call_duration)>0 ) {
         $duration=intval($loc_call_duration);
      }
      else {
         $duration=0;
      }
      
   }
   
   $parameters=array();
   $parameters['request']['notes']['add']=array(
      array(
         'element_type'=>$element_type,
         'element_id'=>$element_id,
         'last_modified'=> $creation_time,
         'note_type'=>$note_type,
         'created_user_id'=>$created_user_id,
         'responsible_user_id'=>$responsable_user_id,
         
         'text' => json_encode(
            array(
               'UNIQ' => $callid,
               'LINK' => $record_link,
               'PHONE' => $phone,
               'DURATION' => $duration,
               'SRC'=>'phonestation',
               'call_status' => $call_status,
               'call_result' => $call_result
            )
         )
      )
   );
   
   $parameters_json=json_encode($parameters);
   

   $http_requester->{'parameters'}=$parameters_json;
   
   $return_result=false;
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
      $decoded_result=json_decode($return_result, true);
      
      $response=$decoded_result['response'];     
      if( isset($response['notes']) &&
          isset($response['notes']['add']) &&
          count($response['notes']['add'])>0 &&
          isset($response['notes']['add'][0]['id']) &&
          is_numeric($response['notes']['add'][0]['id']) ) {
         
         $note_id=strVal($response['notes']['add'][0]['id']);
         
         write_log('create_note_call: Call is added: '.$note_id, $http_requester->log_file);
         $result=$note_id;
      }
      
   }
         
   return($result);
   
}



function contact_search_filtered($parameters='', $amocrm_http_requester=null, $search_value='', $search_fields=array(), &$error_status=false) {
   
   $result=array();
   
   $http_requester=$amocrm_http_requester; 
   
   $contacts_array=array();
   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
   
   if( $return_result===false ) $error_status=true;
   
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result)
      	  && array_key_exists('response', $decoded_result)
                && is_array($decoded_result['response'])
                && array_key_exists('contacts', $decoded_result['response'])
                && is_array($decoded_result['response']['contacts'])
                && count($decoded_result['response']['contacts'])>0 ) {
      	    
       	 $client_contacts=$decoded_result['response']['contacts'];
       	 reset($client_contacts);
       	 while( list($key, $value)=each($client_contacts) ) {
       	    
       	    // filter
       	    if( count($search_fields)>0
       	        && is_array($value) ) {
       	       
        	        $search_result=search_filter($value, $search_value, $search_fields);
        	        if( $search_result===false ) continue;
       	              	           
       	    }
       	    
       	    $contacts_array[ intval($value['id']) ]=$value;	 
       	 }
	 
      }
      
   }
   
   ksort($contacts_array, SORT_NUMERIC);
   reset($contacts_array);   
   
   $result=$contacts_array;
   
   return($result);
}



function search_filter($data_array, $search_value='', $search_fields=array()) {
 
   $result=false;
   
   if( !is_array($search_fields)
       || count($search_fields)===0 ) {
          
      return($result);       
   }
   
   $search_fields_id=$search_fields;
   reset($search_fields_id);
   while( list($key, $value)=each($search_fields_id) ) {
      $search_fields_id[$key]=strVal($value);
   }
   
   reset($data_array);
   while( list($key, $value)=each($data_array) ) {
      
      if( !is_array($value) ) {
         
         if( in_array(strVal($key), $search_fields_id)
             && strVal($value)===strVal($search_value) ) {
                
            $result=true;
            break;
         }
         
      }     
      elseif( is_array($value)
              && $key==='custom_fields' ) {
            
            $value_is_found=false;
            $custom_fields_array=$value;
            reset($custom_fields_array);
            while( list($key_2, $value_2)=each($custom_fields_array) ) {
               
               if( is_array($value_2)
                  && array_key_exists('id', $value_2)
                  && in_array(strVal($value_2['id']), $search_fields_id)
                  && array_key_exists('values', $value_2)
                  && is_array($value_2['values']) ) {
                     
                  $field_code='';
                  if( array_key_exists('code', $value_2) ) $field_code=$value_2['code'];
                  
                  $field_values=$value_2['values'];
                  reset($field_values);
                  foreach($field_values as $value_3) {
                     
                     if( is_array($value_3)
                         && array_key_exists('value', $value_3) ) {
                            
                        $current_value=$value_3['value'];
                        if( $field_code==='PHONE' ) {
                           $current_value=remove_symbols($current_value);
                        }
                        
                        if( strlen($search_value)>0
                            && strpos($current_value, $search_value)!==false ) {
                               
                           $value_is_found=true;
                           $result=true;
                           break;
                        }
                        
                     }
                        
                  }
                  
               }
                                    
               if( $value_is_found===true ) break;
               
           }
       }
       
   }
   
   return($result);   
}

?>
