<?php

  // version 17.11.2017

  require_once('5c_files_lib.php');  
  require_once('5c_std_lib.php');

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
    
    // Authorization 
    $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/auth.php?type=json';
    $parameters=array('USER_LOGIN'=>$this->USER_LOGIN, 'USER_HASH'=>$this->USER_HASH);
    
    $auth_status=false;
    $return_result=amocrm_request('POST', $url, $parameters, $this->log_file, $this->coockie_file);
    if( $return_result!==false ) {
    
      $decoded_result=json_decode($return_result, true);
      $response=$decoded_result['response'];
      
      if( isset($response['auth']) ) {
	$auth_status=true;
	$this->connected=true;
	write_log('Authorization: ок', $this->log_file);
      }
    
    }
    
    // Get contact list
    $contact_id=0;
    if( strlen($parsed_phone)===10 ) {
      $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/contacts/list?type=contact&query='.$parsed_phone;
      $parameters=array();
      
      $return_result=amocrm_request('GET', $url, $parameters, $this->log_file, $this->coockie_file);
      if( $return_result!==false ) {
          
    	$decoded_result=json_decode($return_result, true);
    	$response=$decoded_result['response'];
    	
    	if( isset($response['contacts']) && count($response['contacts'])>0 && isset($response['contacts'][0]['id']) && is_numeric($response['contacts'][0]['id']) ) {
    	  $contact_id=$response['contacts'][0]['id'];
    	  write_log('Contact is found', $this->log_file);
    	}
	
      }
    }
    
    // Create new contact
    if( $contact_id===0
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
                                                 null,
                                                 $this->amocrm_account,
                                                 $this->coockie_file,
                                                 $this->log_file,
                                                 $this->USER_LOGIN,
                                                 $this->USER_HASH);
          
          if( is_array($user_info) ) {
              if( array_key_exists('user_id', $user_info) ) $responsible_user_id=$user_info['user_id'];
          }
      }
      
      $add_value=array( array("name"=>($this->phone_prefix.$parsed_phone).' '.$this->name,
                              "responsible_user_id"=>$responsible_user_id,
                              "custom_fields"=>$custom_fields_value) );
      
      $contacts_value=array( "add"=>$add_value );
      
      $request_value=array( "contacts"=>$contacts_value );
      
      $parameters=array( "request"=>$request_value );
      $parameters_json=json_encode($parameters);
      
      $return_result=amocrm_request('POST', $url, $parameters_json, $this->log_file, $this->coockie_file);
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
    if( isset($this->custom_field_user_phone)
        && isset($this->custom_field_user_amo_crm)
        && isset($this->user_phone)
        && strlen($this->user_phone)>0 ) {
    
	 $client_contact_user=null;
    
	 $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/contacts/list';
	 $parameters=array();
	 $parameters['type']='contact';
	 $parameters['query']=strVal($this->user_phone);
	 
         $return_result=false;
         if( strlen($parameters['query'])>0 ) {
            $return_result=amocrm_request('GET', $url, $parameters, $this->log_file, $this->coockie_file);
         }   
            
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
			   && $value_2['id']===$this->custom_field_user_phone
			   && is_array($value_2['values'])
			   && $value_2['values'][0]['value']===$this->user_phone ) {
			   
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
		     && $value['id']!==$this->custom_field_user_amo_crm ) continue; 

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
    if( $contact_id>0 ) {
      $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/v2/json/notes/set';
      
      $parameters=array();
      $parameters['request']['notes']['add']=array(
	  array(
	      'element_id'=>$contact_id,
	      'last_modified'=> $this->call_unix_time,
	      'element_type'=>1,
	      'note_type'=>$note_type,
	      'created_user_id'=>$user_id,
	      'responsable_user_id'=>$user_id,
	      
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
      $return_result=amocrm_request('POST', $url, $parameters_json, $this->log_file, $this->coockie_file);   
      if( $return_result!==false ) {
	$decoded_result=json_decode($return_result, true);
	$response=$decoded_result['response'];
	
	if( isset($response['notes']) && isset($response['notes']['add']) && count($response['notes']['add'])>0 && isset($response['notes']['add'][0]['id']) && is_numeric($response['notes']['add'][0]['id']) ) {
	  write_log('Call is added: '.$this->callid, $this->log_file);
	  $result=true;
	}
	
      }
      
    }
    
    return($result);

  } // end function register_call   
  
  
} // end class


function amocrm_request($send_method, $url, $parameters, $log_path="", $coockie_path="", $header=null) {

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
      
    $status_text=(isset($errors[$return_code_numeric]) ? $errors[$return_code_numeric].' '.$return_result : 'Undescribed error: '.$return_code);
    $result=false;
  }
  else {
    $status_text='success';
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
  
  // class use
  public $connected;
  
  
  function __construct() {
      $this->connected=false;
      $this->max_number_get_parameters=490;
      $this->max_number_rows=490;
      $this->max_number_request_cycles=21;
      $this->sleep_time_after_request_microsec=300000;
  }
  
  public function connect() {
  
    $result=false;

    // Authorization 
    $url='https://'.$this->amocrm_account.'.amocrm.ru/private/api/auth.php?type=json';
    $parameters=array('USER_LOGIN'=>$this->USER_LOGIN, 'USER_HASH'=>$this->USER_HASH);

    $return_result=amocrm_request('POST', $url, $parameters, $this->log_file, $this->coockie_file, $this->header);
    if( $return_result!==false ) {
    
      $decoded_result=json_decode($return_result, true);
      $response=$decoded_result['response'];
      
      if( isset($response['auth']) ) {
    	$result=true;
    	$this->connected=true;
    	write_log('Authorization: ок', $this->log_file);
    	usleep($this->sleep_time_after_request_microsec);
      }
    
    }
    else {
      write_log('Authorization: failed', $this->log_file);      
    }
	  
    return($result);    
  }
  
  public function request() {
  
    $result=false;

    // Request
    $request_parameters=array();
    $continue_cycle=false;
    $sum_number_objects=0;
    $cycle_counter=0;
    $divided_request_result_array=false;
    
    $divided_request=false;
    $limited_request=false;
    
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
        
        $return_result=amocrm_request($this->send_method, $this->url, $request_parameters, $this->log_file, $this->coockie_file, $this->header);
        if( $return_result!==false ) {
            
          if( $divided_request===true
              || $limited_request===true ) {
              
              $divided_request_result=json_decode($return_result, true);
              if( is_array($divided_request_result_array)
                  && array_key_exists('response', $divided_request_result_array) ) {
                  
                  if( is_array($divided_request_result)
                      && array_key_exists('response', $divided_request_result) )  {
                      
                      reset($divided_request_result_array['response']);    
                      while( list($key, $value)=each( $divided_request_result_array['response'] ) ) {
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
            $result=$return_result;
          }  
            
        }
        
        usleep($this->sleep_time_after_request_microsec);
        
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
	  
    return($result);     
  }
  
  
}  


function get_user_internal_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_phone, $amocrm_http_requester=null,
				 $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

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
      
      if( ($http_requester->{'connected'})!==true ) $http_requester->connect();
      
      $http_requester->{'send_method'}='GET';
      $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';

      $parameters=array();
      $parameters['type']='contact';
      $parameters['query']=urlencode($user_id);  
      $http_requester->{'parameters'}=$parameters;

      $return_result=$http_requester->request();
      
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
		  && $value['id']!==$custom_field_user_phone ) continue; 

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
				 $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

   
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
      
      if( ($http_requester->{'connected'})!==true ) $http_requester->connect();
      
      $http_requester->{'send_method'}='GET';
      $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';

      $parameters=array();
      $parameters['type']='contact';
      $parameters['query']=urlencode($user_phone); 
      
      $http_requester->{'parameters'}=$parameters;

      $return_result=$http_requester->request();
      
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
			&& $value_2['id']===$custom_field_user_phone
			&& is_array($value_2['values'])
			&& $value_2['values'][0]['value']===$user_phone ) {
			
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
		  && $value['id']!==$custom_field_user_amo_crm ) continue; 

	       $values_array=$value['values'];	  
	       
	       if( is_array($values_array)
		  && count($values_array)>0
		  && isset($values_array[0]['value']) ) {
	       
		  $user_id=$values_array[0]['value'];
		  $result["user_id"]=strVal($user_id);
		  break;	    
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
				   $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {
				   
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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
   $contacts_array=array();
   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request(); 
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
            $contact_data['custom_fields']=$value['custom_fields'];
	    
	    $contacts_array[ intval($value['id']) ]=$contact_data;	 
	 }
	 
      }
      
   }
   
   ksort($contacts_array, SORT_NUMERIC);
   reset($contacts_array);   
   
   $result=$contacts_array;
   
   return($result);
}


function get_company_info($parameters='', $amocrm_http_requester=null,
				   $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

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
   
   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
				   
   $companies_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request();
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
				   
   $result=$companies_array;
				   
   return($result);
				   
}


function get_leads_info($parameters='', $amocrm_http_requester=null,
			$amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
   $leads_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/leads/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request(); 
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result) 
	  && array_key_exists('response', $decoded_result)
	  && is_array($decoded_result['response'])
	  && array_key_exists('leads', $decoded_result['response'])
	  && is_array($decoded_result['response']['leads'])
	  && count($decoded_result['response']['leads'])>0 ) {
	    
	 $leads=$decoded_result['response']['leads'];
	 reset($leads);
	 while( list($key, $value)=each($leads) ) {

	    $lead_data=array();
	    $lead_data['lead_id']=strval($value['id']);
	    $lead_data['status_id']=strval($value['status_id']);
	    $lead_data['contact_id']=strval($value['main_contact_id']);
	    $lead_data['company_id']=strval($value['linked_company_id']);
	    $lead_data['user_id']=strval($value['responsible_user_id']);
	    $lead_data['date_create']=intVal($value['date_create']);
	 
	    $leads_array[ intval($value['id']) ]=$lead_data;	 
	 }
	 
      } 

   }
   
   ksort($leads_array, SORT_NUMERIC);
   reset($leads_array);      
				   
   $result=$leads_array;
				   
   return($result);
				   
}


function get_companies_info($parameters='', $amocrm_http_requester=null,
			$amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
   $companies_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request(); 
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
	 
	    $companies_array[ intval($value['id']) ]=$company_data;	 
	 }
	 
      } 

   }
   
   ksort($companies_array, SORT_NUMERIC);
   reset($companies_array);      
				   
   $result=$companies_array;
				   
   return($result);
				   
}



function create_lead($name, $status_id, $responsible_user_id, $company_id, $fields=array(), $amocrm_http_requester=null,
		             $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

   global $custom_field_address_type;
   global $custom_field_first_called_number;
		    
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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
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
          
   }
      
      
   $parameters_json=json_encode($parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/leads/set';
   $http_requester->{'parameters'}=$parameters_json;
   
   $return_result=false;
   $return_result=$http_requester->request(); 
   
   $result=$return_result;
   
   return($result);
   
}


function update_contact_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null,
				   $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {
				   
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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();
   
   $contact_id='';
   $last_modified=0;
   $linked_leads_id=null;
   
   $contacts_info=get_contact_info($parameters, $http_requester);
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
      elseif( Trim($key)!=='id' ) {
	 $request_parameters['request']['contacts']['update'][0][$key]=$value;
      }
   }
   
   $request_parameters_json=json_encode($request_parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/contacts/set';
   $http_requester->{'parameters'}=$request_parameters_json;
   
   $return_result=$http_requester->request(); 
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


function get_notes_info($parameters='', $amocrm_http_requester=null,
			$amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
   $elements_array=array();
				   
   $http_requester->{'send_method'}='GET';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/list';
   $http_requester->{'parameters'}=$parameters;
   
   $return_result=$http_requester->request(); 
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
				   
   $result=$elements_array;
				   
   return($result);		

}


function update_notes_info($parameters='', $updated_fields=array(), $amocrm_http_requester=null,
			  $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {
				   
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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();
   
   $notes_update=array();
   $notes_info=get_notes_info($parameters, $http_requester);
   if( is_array($notes_info)
       && count($notes_info)>0 ) {
      
      reset($notes_info);
      while( list($key, $value)=each($notes_info) ) {
      
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
      }
      
   }
   

   // Update contact data
   $request_parameters=array();
   $request_parameters['request']['notes']['update']=$notes_update;   
   $request_parameters_json=json_encode($request_parameters);

   $http_requester->{'send_method'}='POST';
   $http_requester->{'url'}='https://'.($http_requester->{'amocrm_account'}).'.amocrm.ru/private/api/v2/json/notes/set';
   $http_requester->{'parameters'}=$request_parameters_json;
   
   $return_result=$http_requester->request(); 
   if( $return_result!==false ) {
   
      $decoded_result=json_decode($return_result, true);
      if( is_array($decoded_result)
	  && array_key_exists('response', $decoded_result)
          && is_array($decoded_result['response'])
          && array_key_exists('notes', $decoded_result['response'])
          && is_array($decoded_result['response']['notes'])
          && count($decoded_result['response']['notes'])>0 ) {
	 
	 $result=true;	 
      }
      
   }

   return($result);
}


function create_unsorted($name, $pipeline_id, $phone_from, $phone_to,
                         $contact_name, $company_id, $record_link='', $outcoming=false, $fields=array(), $amocrm_http_requester=null,
		         $amocrm_account=null, $coockie_file=null, $log_file=null, $user_login=null, $user_hash=null) {

   global $custom_field_phone_id;
   global $custom_field_phone_enum;  
   global $custom_field_address_type;
   global $custom_field_first_called_number;
		    
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

   if( ($http_requester->{'connected'})!==true ) $http_requester->connect();   
   
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
   
   $http_requester->{'header'}='';
   
   $result=$return_result;
   
   return($result);
   
}



?>
