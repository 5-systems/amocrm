<?php

function amocrm_1C_create_contact(&$http_requester, $contact_data, $user_id, $contacts_array=array(), $companies_array=array(), &$error_status=false, $LogLineId='') {
   
   global $amocrm_1C_integration_web_service_url;
   
   $result=array();
   
   // Call is missed or user does not work with amoCRM
   /*
   if( strlen($user_id)===0
       || !is_numeric($user_id)
       || intVal($user_id)<=0 ) {
          
       return($result);          
   }
   */
   
   // Define client and company if code_1C is set
   $contact_id=null;
   $contact_name=null;
   
   $company_id=null;
   $company_name=null; 
   
   $client_companies_array=array();
   
   $client_company_array=define_contact_company_with_code_1C($http_requester, $contacts_array, $client_companies_array, $companies_array, $error_status, $LogLineId);
     
   if( array_key_exists('contact_id', $client_company_array) ) {          
      $contact_id=$client_company_array['contact_id'];
      $contact_name=$client_company_array['contact_name'];
   }
   
   if( array_key_exists('company_id', $client_company_array) ) {        
      $company_id=$client_company_array['company_id'];
      $company_name=$client_company_array['company_name'];
   }   
   
   // code_1C of client is defined
   if( strlen($contact_id)>0
       || strlen($company_id)>0 ) {
       
       // Request to 1C to define address type
       $result['address_type']='';
          
       $client_array=array();
       
       if( isset($contact_id) ) $client_array['id']=$contact_id;
       if( isset($contact_name) ) $client_array['name']=$contact_name;
       
       if( count($client_array)>0 ) $result['contact']=$client_array;
       
       $company_array=array();
       
       if( isset($company_id) ) $company_array['id']=$company_id;
       if( isset($company_name) ) $company_array['name']=$company_name;
       
       if( count($company_array)>0 ) $result['company']=$company_array;
       
       return($result);
   }
   
   // code_1C of client is not defined
      
   // Request to 1C to define code_1C
   if( !isset($amocrm_1C_integration_web_service_url) ) {  
       write_log('amocrm_1C_create_contact: amocrm_1C_integration_web_service_url constant not defined ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       return($result);
   }
   
   
   $client_data_array=get_client_data_from_1C($contact_data, $http_requester->{'log_file'}, $LogLineId);
    
   $request_status=false;
   $contact_found=false;
   $company_found=false;
   
   reset($client_data_array);   
   while( list($key, $value)=each($client_data_array) ) {
      
      if( is_array($value) ) {
         
          if( array_key_exists('result', $value)
              && $value['result']==='success' ) {
                 
             $request_status=true;
          }
          
          if( array_key_exists('contact', $value)
              && is_array($value['contact']) ) {
                 
             $contact_found=true;   
          }
          
          if( array_key_exists('company', $value)
              && is_array($value['company']) ) {
                 
             $company_found=true;   
          }          
          
      }
 
   }
   
   $create_contact_without_integration_1C=false;
   if( $request_status===true
       && ($contact_found===true
           || $company_found===true) ) {
      
      write_log('amocrm_1C_create_contact: use 1C data ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
      
      // create contact and company
      $client_company_array=create_contact_company_using_1C_data($http_requester, $client_data_array, $contacts_array, $client_companies_array, $companies_array, $error_status, $LogLineId);
      if( $error_status===true ) {
         write_log('amocrm_1C_create_contact: create_contact_company_using_1C_data method failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
         return($result);
      }

      $contact_id=null;
      $contact_name=null;
      
      $company_id=null;
      $company_name=null;
      
      if( array_key_exists('contact_id', $client_company_array) ) {          
         $contact_id=$client_company_array['contact_id'];
         $contact_name=$client_company_array['contact_name'];
      }
      
      if( array_key_exists('company_id', $client_company_array) ) {        
         $company_id=$client_company_array['company_id'];
         $company_name=$client_company_array['company_name'];
      }      
      
       $result['address_type']='';
          
       $client_array=array();
       
       if( isset($contact_id) ) $client_array['id']=$contact_id;
       if( isset($contact_name) ) $client_array['name']=$contact_name;
       
       if( count($client_array)>0 ) $result['contact']=$client_array;
       
       $company_array=array();
       
       if( isset($company_id) ) $company_array['id']=$company_id;
       if( isset($company_name) ) $company_array['name']=$company_name;
       
       if( count($company_array)>0 ) $result['company']=$company_array;     
      
   }
   else {      
      $create_contact_without_integration_1C=true;
   }
   
   
   if( $create_contact_without_integration_1C===true ) {
      $result=create_contact_default_local($http_requester, $contact_data, $user_id, $contacts_array, $companies_array, $error_status, $LogLineId);
   }
   
   return($result);   
}


function get_client_data_from_1C($contact_data, $log_file='', $LogLineId='') {
   
   global $amocrm_1C_integration_web_service_url;
   global $amocrm_1C_integration_web_service_request_timeout;
   
   $result=array();
   
   if( !is_array($contact_data) ) return($result);
   
   $phone_array=array();
   if( array_key_exists('phone', $contact_data) ) {
      
      $phone_value=$contact_data['phone'];         
      $phone_value=remove_symbols($phone_value);
      $phone_value=substr($phone_value, -10);
      
      if( strlen($phone_value)>=10 ) {
         $phone_array[]=$phone_value;
      }
      
   }
     
   $email_array=array();
   if( array_key_exists('email', $contact_data) ) {
          
      $email_value=$contact_data['email'];
      $email_value=trim($email_value);
      
      if( strlen($email_value)>=6 ) {
          $email_array[]=$email_value; 
      }
      
   }
   
   if( count($phone_array)===0
       && count($email_array)===0 ) {
                    
      return($result);    
   }
   
   
   $common_info=array();
   
   $common_info['method']='get_client_data';
   if( count($phone_array)>0 ) $common_info["phone"]=$phone_array;
   if( count($email_array)>0 ) $common_info["email"]=$email_array;
      
   $request_data=http_build_query($common_info, null, '&', PHP_QUERY_RFC3986);
   
   $headers=array('Content-Type:application/x-www-form-urlencoded');
   
   $request_url=$amocrm_1C_integration_web_service_url;
   $request_timeout=$amocrm_1C_integration_web_service_request_timeout;
   if( !isset($request_timeout)
       || !is_numeric($request_timeout) ) {
          
       $request_timeout=3;   
   }
   
   if( is_string($request_timeout) ) $request_timeout=floatVal($request_timeout);
   
   $request_result='';
   if(  strlen($request_url)>0 ) {
           
      $request_separator='?';
      if( strpos($request_url, '?')!==false ) {
         $request_separator='&';
      }
      
      $request_result=request_POST($request_url, $request_data, $log_file, $headers, $request_timeout);   
      
      write_log('get_client_data_from_1C: request result='.strVal($request_result), $log_file, 'REG_CALL '.$LogLineId);
   
   }
   else {
      write_log('get_client_data_from_1C: request url is not set ', $log_file, 'REG_CALL '.$LogLineId);    
   }
   
   if( strlen($request_result)>0 ) {
      
      $result=json_decode($request_result, true);
      
   }
   
   if( !is_array($result) ) {
      $result=array();
   }
   
   return($result);
}


function merge_contact_data($response_array, $contact_data=array()) {
   
   global $custom_field_phone_id;
   global $custom_field_email_id;
   
   $result=$contact_data;
   
   if( !array_key_exists('code_1C', $result) ) {
      $result['code_1C']=array();
   }
   
   if( !array_key_exists('phone', $result) ) {
      $result['phone']=array();
   }
   
   if( !array_key_exists('email', $result) ) {
      $result['email']=array();
   }   
   
   // code_1C
   reset($response_array);
   while( list($key, $value)=each($response_array) ) {
      
      if( array_key_exists('code_1C', $value) ) {
         
         $code_1C=$value['code_1C'];
         for($i=0; $i<count($code_1C); $i++ ) {
            if( isset($code_1C[$i])
                && strlen($code_1C[$i])>0
                && !in_array($code_1C[$i], $result['code_1C'], true)) {
                   
               $result['code_1C'][]=$code_1C[$i];
            }
         }
         
      }
      
   }
   
   // phone
   if( isset($custom_field_phone_id)
       && strlen($custom_field_phone_id)>0 ) {
          
      $client_phone_array=get_field_values($response_array, strVal($custom_field_phone_id));
      
      reset($client_phone_array);
      while( list($key, $value)=each($client_phone_array) ) {
         
         reset($value);
         while( list($key_2, $value_2)=each($value) ) {
            $local_phone=remove_symbols($value_2);
            $local_phone=substr($local_phone, -10);
            
            if( strlen($local_phone)>=10
                && !in_array($local_phone, $result['phone'], true)) {
                   
                $result['phone'][]=$local_phone;
            }
            
         }
      }
          
   }

   // email
   if( isset($custom_field_email_id)
       && strlen($custom_field_email_id)>0 ) {
          
      $client_email_array=get_field_values($response_array, strVal($custom_field_email_id));
      
      reset($client_email_array);
      while( list($key, $value)=each($client_email_array) ) {
         
         reset($value);
         while( list($key_2, $value_2)=each($value) ) {           
            if( strlen($value_2)>=6
                && !in_array($value_2, $result['email'], true) ) {
                   $result['email'][]=$value_2;           
            }
         }
      }
          
   }   
   
   return($result);
}


function define_contact_company_with_code_1C(&$http_requester, &$contacts_array, &$client_companies_array, &$companies_array, &$error_status=false, $LogLineId='') {

   global $amocrm_1C_integration_contact_custom_field_client_code_1;
   global $amocrm_1C_integration_contact_custom_field_client_code_2;
   global $amocrm_1C_integration_contact_custom_field_client_code_3;
   global $amocrm_1C_integration_contact_custom_field_principal_client;
   
   global $amocrm_1C_integration_company_custom_field_client_code_1;
   global $amocrm_1C_integration_company_custom_field_client_code_2;
   global $amocrm_1C_integration_company_custom_field_client_code_3;
   global $amocrm_1C_integration_company_custom_field_principal_client;   
   
   $result=array();
   
   // Set code_1C property for contact
   $client_code_found=false;
   $client_id='';
   $client_name='';
   
   $company_id='';
   $company_name='';   
   
   $code_fields=array();
   $code_fields[]=$amocrm_1C_integration_contact_custom_field_client_code_1;
   $code_fields[]=$amocrm_1C_integration_contact_custom_field_client_code_2;
   $code_fields[]=$amocrm_1C_integration_contact_custom_field_client_code_3;
   
   define_code_1C($contacts_array, $code_fields, $amocrm_1C_integration_contact_custom_field_principal_client);
   
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      if( is_array($value)
          && array_key_exists('principal_client_code_1C', $value)
          && strlen($value['principal_client_code_1C'])>0 ) {
             
          $client_code_found=true;
          
          if( !isset($contact_id)
              || strlen($contact_id)===0 ) {
                 
             $client_id=strVal($value['contact_id']);
             $client_name=$value['name'];
          }

          break;
      }    
   }
   
   // Set code_1C property for company   
   $client_companies_array=array();
    
   $companies_id=array();
   reset($contacts_array);
   while( list($key, $value)=each($contacts_array) ) {
      if( is_array($value)
          && array_key_exists('company_id', $value)
          && is_numeric($value['company_id']) ) {
               
         $companies_id[ intVal($value['company_id']) ]=strVal($value['company_id']);
         
         $company_value=array();
         $company_value['company_id']=strVal($value['company_id']);
         $company_value['name']='';
         
         $client_companies_array[ intVal($value['company_id']) ]=$company_value;
      }      
   }
   
   $all_client_companies_are_found=false;
   reset($companies_id);
   while( list($key, $value)=each($companies_id) ) {
      
      $company_id_numeric=0;
      if( is_numeric($value)
          && intVal($value)>0 ) {

         $company_id_numeric=intVal($value);
      }
      
      if( $company_id_numeric>0
          && array_key_exists($company_id_numeric, $companies_array) ) {
      
          $client_companies_array[$company_id_numeric]=$companies_array[$company_id_numeric];
      }
   }
   
   if( count($companies_id)===count($client_companies_array) ) $all_client_companies_are_found=true;
   
   if( $all_client_companies_are_found===false ) {
      
      $parameters=array();
      $parameters['type']='company';
      $parameters['id']=$companies_id;   
      
      if( count($companies_id)>0 ) {
         $error_status=false;
         
         $client_companies_array=get_companies_info($parameters, $http_requester, null, null, null, null, null, $error_status);     
      }
   
   }     

   
   if( $error_status===true ) {
      write_log('define_contact_company_with_code_1C: get_companies_info failed: cannot get client companies info ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
      return($result);
   }

   $code_fields=array();
   $code_fields[]=$amocrm_1C_integration_company_custom_field_client_code_1;
   $code_fields[]=$amocrm_1C_integration_company_custom_field_client_code_2;
   $code_fields[]=$amocrm_1C_integration_company_custom_field_client_code_3;
   
   define_code_1C($client_companies_array, $code_fields, $amocrm_1C_integration_company_custom_field_principal_client);
   
   reset($client_companies_array);
   while( list($key, $value)=each($client_companies_array) ) {
      if( is_array($value)
          && array_key_exists('principal_client_code_1C', $value)
          && strlen($value['principal_client_code_1C'])>0 ) {
             
         $client_code_found=true;
         
         if( !isset($company_id)
             || strlen($company_id)===0 ) {
          
            $company_id=strVal($value['company_id']);
            $company_name=$value['name'];           
         }
         
         break;
      }    
   }   
   
   define_code_1C($companies_array, $code_fields, $amocrm_1C_integration_company_custom_field_principal_client);   
   
   reset($companies_array);
   while( list($key, $value)=each($companies_array) ) {
      if( is_array($value)
          && array_key_exists('principal_client_code_1C', $value)
          && strlen($value['principal_client_code_1C'])>0 ) {
             
          $client_code_found=true;
          
          if( !isset($company_id)
              || strlen($company_id)===0 ) {
                 
             $company_id=strVal($value['company_id']);
             $company_name=$value['name'];
          }
          
          break;
      }    
   }
   
   if( !isset($client_id)
       || strlen($client_id)===0 ) {
          
      $client_id='';
      $client_name='';
   }
   
   if( !isset($company_id)
       || strlen($company_id)===0 ) {
          
      $company_id='';
      $company_name='';
   }
   
   if( strlen($client_id)>0
       || strlen($company_id)>0 ) {
          
       if( strlen($client_id)===0
           && count($contacts_array)>0 ) {
           
           reset($contacts_array);
           $current_contact=current($contacts_array);
           $client_id=$current_contact['contact_id'];
           $client_name=$current_contact['name'];
       }
       
      if( strlen($company_id)===0
          && count($client_companies_array)>0 ) {
           
           reset($client_companies_array);
           $current_company=current($client_companies_array);
           $company_id=$current_company['company_id'];
           
           if( array_key_exists('name', $current_company) ) {
               $company_name=$current_company['name'];
           }    
       }
       
       if( strlen($company_id)===0
           && count($companies_array)>0 ) {
           
           reset($companies_array);
           $current_company=current($companies_array);
           $company_id=$current_company['company_id'];
           
           if( array_key_exists('name', $current_company) ) {
               $company_name=$current_company['name'];
           }    
       }       
          
   }
   
   $result['contact_id']=$client_id;
   $result['contact_name']=$client_name;   
   
   $result['company_id']=$company_id;
   $result['company_name']=$company_name;   
   
   return($result);
}


function define_code_1C(&$response_array, $code_fields, $principal_client_field) {
   
   $result=true;
   
   reset($response_array);
   while( list($key, $value)=each($response_array) ) {
      $response_array[$key]['code_1C']=array();
      $response_array[$key]['principal_client']='';
      $response_array[$key]['principal_client_code_1C']='';
   }   
   
   if( is_numeric($principal_client_field) ) {
      $code_fields[]=$principal_client_field;    
   }
   
   for($i=0; $i<count($code_fields); $i++) {
      $values=get_field_values($response_array, $code_fields[$i]);
      
      if( is_array($values)
          && count($values)>0 ) {
          
          reset($values);   
          while( list($key, $value)=each($values) ) {   
             $contact_data=$response_array[intVal($key)];
             
             $contact_key=intVal($key);
             if( is_array($contact_data)
                 && array_key_exists($contact_key, $values)
                 && count($values[$contact_key])>0 ) {
                
                if( $code_fields[$i]===$principal_client_field ) {
                  $response_array[$contact_key]['principal_client']=$values[$contact_key][0]; 
                }
                else {
                  $response_array[$contact_key]['code_1C'][$i]=$values[$contact_key][0];
                }
             }
          }
      }
   }

   reset($response_array);
   while( list($key, $value)=each($response_array) ) {
      $code_1C_array=$response_array[$key]['code_1C'];
      $principal_client_string=strVal($response_array[$key]['principal_client']);
      
      $principal_client_code_1C='';
      reset($code_1C_array);
      if( strlen($principal_client_string)>0
          && is_numeric($principal_client_string) ) {
          
          $principal_client_index= intVal($principal_client_string)-1;   
          if( array_key_exists($principal_client_index, $code_1C_array)
              && strlen($code_1C_array[$principal_client_index])>0 ) {
                 
              $principal_client_code_1C=$code_1C_array[$principal_client_index];
          }   
             
      }
      
      if( strlen($principal_client_code_1C)===0
          && count($code_1C_array)>0 ) {
          
          reset($code_1C_array);   
          while( list($key_2, $value_2)=each($code_1C_array) ) {
             if( strlen($value_2)>0 ) {
                $principal_client_code_1C=$value_2;
                break;
             }  
          }   
      }
      
      $response_array[$key]['principal_client_code_1C']=$principal_client_code_1C;      
   }   
   
   return($result);
}


function get_first_field_value($response_array, $field) {
   
   $result=array();
      
   $values=get_field_values($response_array, $field);  
   if( is_array($values) ) {
      
      reset($values);
      while( list($key, $value)=each($values) ) {
         
         if( is_array($value) ) {
            if( count($value)>0 ) {                
               $result[$key]=$value[0];
            }   
         }
         else {
            $result[$key]=$value;
         }
         
      }
   
   }
      
   return($result);
}


function get_field_values($response_array, $field) {
   
   $result=array();
   
   if( !isset($response_array)
       || !is_array($response_array) ) {
          
       return($result);   
   }
   
   if( !isset($field)
       || strlen($field)===0 ) {
   
       return($result); 
   }
  
   reset($response_array);
   while( list($key, $value)=each($response_array) ) {
      
      $fields_array=$value;
      if( is_array($fields_array) ) {
         
         reset($fields_array);
         while( list($key_2, $value_2)=each($fields_array) ) {
         
            if( strVal($key_2)===strVal($field) ) {
               $result[ intVal($key) ]=$value_2;
               
               break;
            }
         
         }
         
      }   
      
      if( is_array($value)
         && array_key_exists('custom_fields', $value)
         && is_array($value['custom_fields']) ) {
            
         $custom_fields_array=$value['custom_fields'];
         reset($custom_fields_array);
         while( list($key_2, $value_2)=each($custom_fields_array) ) {
            
            if( is_array($value_2)
                && array_key_exists('id', $value_2)
                && is_numeric($value_2['id'])
                && array_key_exists('values', $value_2)
                && strVal($value_2['id'])===strVal($field) ) {
                  
               $values_array=get_value($value_2['values']);
               $result[ intVal($key) ]=$values_array;
                                    
               break;
            }
               
         }
         
      }
         
   }
 
   return($result);
}


function get_value($value_array) {
   
   $result=array();
   
   if( is_array($value_array) ) {
      
      reset($value_array);
      while( list($key, $value)=each($value_array) ) {
         
         if( is_array($value)
             && array_key_exists('value', $value) ) {
                
             $result[ intVal($key) ]=$value['value'];
         }
         
      }
      
      
   } 
   
   return($result);
}


function create_contact_company_using_1C_data($http_requester, $client_data_array, $contacts_array, $client_companies_array, $companies_array, &$error_status=false, $LogLineId='') {

   global $amocrm_1C_integration_contact_custom_field_client_code_1;
   global $amocrm_1C_integration_contact_custom_field_client_code_2;
   global $amocrm_1C_integration_contact_custom_field_client_code_3;
   
   global $amocrm_1C_integration_contact_custom_field_client_name_1;
   global $amocrm_1C_integration_contact_custom_field_client_name_2;
   global $amocrm_1C_integration_contact_custom_field_client_name_3;
   
   global $amocrm_1C_integration_contact_custom_field_principal_client;
   
   global $amocrm_1C_integration_company_custom_field_client_code_1;
   global $amocrm_1C_integration_company_custom_field_client_code_2;
   global $amocrm_1C_integration_company_custom_field_client_code_3;
   
   global $amocrm_1C_integration_company_custom_field_client_name_1;
   global $amocrm_1C_integration_company_custom_field_client_name_2;   
   global $amocrm_1C_integration_company_custom_field_client_name_3;
   
   global $amocrm_1C_integration_company_custom_field_principal_client;   
   
   $result=array();

   
   $data_1C=array();
   
   $contacts_1C=get_contacts_from_1C($client_data_array, 'contact');
   if( count($contacts_1C)>0 ) $data_1C['contacts']=$contacts_1C;
   
   $companies_1C=get_contacts_from_1C($client_data_array, 'company');
   if( count($companies_1C)>0 ) $data_1C['companies']=$companies_1C;   

   
   $contact_custom_field_client_code=array();
   if( isset($amocrm_1C_integration_contact_custom_field_client_code_1)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_code_1) ) {
          
       $contact_custom_field_client_code[]=strVal($amocrm_1C_integration_contact_custom_field_client_code_1);  
   }
 
   if( isset($amocrm_1C_integration_contact_custom_field_client_code_2)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_code_2) ) {
                    
       $contact_custom_field_client_code[]=strVal($amocrm_1C_integration_contact_custom_field_client_code_2);
   }
     
   if( isset($amocrm_1C_integration_contact_custom_field_client_code_3)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_code_2) ) {
         
       $contact_custom_field_client_code[]=strVal($amocrm_1C_integration_contact_custom_field_client_code_3);         
   }
   
   $contact_custom_field_client_name=array();
   if( isset($amocrm_1C_integration_contact_custom_field_client_name_1)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_name_1) ) {
             
       $contact_custom_field_client_name[]=strVal($amocrm_1C_integration_contact_custom_field_client_name_1);  
   }
   
   if( isset($amocrm_1C_integration_contact_custom_field_client_name_2)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_name_2) ) {
             
       $contact_custom_field_client_name[]=strVal($amocrm_1C_integration_contact_custom_field_client_name_2);  
   }
   
   if( isset($amocrm_1C_integration_contact_custom_field_client_name_3)
       && is_numeric($amocrm_1C_integration_contact_custom_field_client_name_3) ) {
             
       $contact_custom_field_client_name[]=strVal($amocrm_1C_integration_contact_custom_field_client_name_3);  
   }   
      
   $company_custom_field_client_code=array();
   if( isset($amocrm_1C_integration_company_custom_field_client_code_1)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_code_1) ) {
          
       $company_custom_field_client_code[]=strVal($amocrm_1C_integration_company_custom_field_client_code_1);         
   }
   
   if( isset($amocrm_1C_integration_company_custom_field_client_code_2)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_code_2) ) {
          
       $company_custom_field_client_code[]=strVal($amocrm_1C_integration_company_custom_field_client_code_2);          
   }
             
   if( isset($amocrm_1C_integration_company_custom_field_client_code_3)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_code_3) ) {

       $company_custom_field_client_code[]=strVal($amocrm_1C_integration_company_custom_field_client_code_3);      
   }
   
   $company_custom_field_client_name=array();
   if( isset($amocrm_1C_integration_company_custom_field_client_name_1)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_name_1) ) {
          
       $company_custom_field_client_name[]=strVal($amocrm_1C_integration_company_custom_field_client_name_1);         
   }   
   
   if( isset($amocrm_1C_integration_company_custom_field_client_name_2)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_name_2) ) {
          
       $company_custom_field_client_name[]=strVal($amocrm_1C_integration_company_custom_field_client_name_2);         
   }
   
   if( isset($amocrm_1C_integration_company_custom_field_client_name_3)
       && is_numeric($amocrm_1C_integration_company_custom_field_client_name_3) ) {
          
       $company_custom_field_client_name[]=strVal($amocrm_1C_integration_company_custom_field_client_name_3);         
   }   
   
   
   if( count($contacts_array)>0
       || count($companies_array)>0 ) {
            
      if( count($contacts_array)>0
          && array_key_exists('contacts', $data_1C) ) {
         
          write_log('create_contact_company_using_1C_data: contacts amo='.count($contacts_array).' contacts 1C='.count($data_1C['contacts']), $http_requester->log_file, 'REG_CALL '.$LogLineId);             
             
          // set code
          $result=set_code_1C($http_requester, $contacts_array, 'contact',
                              $data_1C, $contact_custom_field_client_code,
                              $contact_custom_field_client_name, $error_status, $LogLineId);
             
          if( $error_status===true ) {
             return($result);
          }
             
      }
      elseif( count($companies_array)>0
              && array_key_exists('companies', $data_1C) ) {

          write_log('create_contact_company_using_1C_data: companies amo='.count($companies_array).' companies 1C='.count($data_1C['companies']), $http_requester->log_file, 'REG_CALL '.$LogLineId);                 
                 
          // set code
          $result=set_code_1C($http_requester, $companies_array, 'company',
                              $data_1C, $company_custom_field_client_code,
                              $company_custom_field_client_name, $error_status, $LogLineId);
                
          if( $error_status===true ) {
             return($result);
          }          
         
      }
      elseif( count($contacts_array)>0
              && array_key_exists('companies', $data_1C) ) {

          write_log('create_contact_company_using_1C_data: contacts amo='.count($contacts_array).' companies 1C='.count($data_1C['companies']), $http_requester->log_file, 'REG_CALL '.$LogLineId);                 
                 
          // search for company by code, by phone
          // create company
          // set code
         $result=create_contact_from_1C($http_requester, 'company', $data_1C,
                                        $company_custom_field_client_code, $company_custom_field_client_name,
                                        null, $error_status, $LogLineId);
                           
      }
      elseif( count($companies_array)>0
              && array_key_exists('contacts', $data_1C) ) {

          write_log('create_contact_company_using_1C_data: companies amo='.count($companies_array).' contacts 1C='.count($data_1C['contacts']), $http_requester->log_file, 'REG_CALL '.$LogLineId);                 
                 
          // search for contact by code, by phone
          // create contact
          // set code
         $result=create_contact_from_1C($http_requester, 'contact', $data_1C,
                                        $contact_custom_field_client_code, $contact_custom_field_client_name,
                                        null, $error_status, $LogLineId);         
      }
   
   }
   else {
      
      if( array_key_exists('contacts', $data_1C) ) {

          write_log('create_contact_company_using_1C_data: nothing in amo, contacts 1C='.count($data_1C['contacts']), $http_requester->log_file, 'REG_CALL '.$LogLineId);         
         
         // search for contact by code, phone
         // create contact
         // set code, phones, e-mails
        $result=create_contact_from_1C($http_requester, 'contact', $data_1C,
                                        $contact_custom_field_client_code, $contact_custom_field_client_name,
                                        null, $error_status, $LogLineId);         
         
      }
      elseif( array_key_exists('companies', $data_1C) ) {
 
          write_log('create_contact_company_using_1C_data: nothing in amo, companies 1C='.count($data_1C['companies']), $http_requester->log_file, 'REG_CALL '.$LogLineId);         
         
         // search for company by code, phone
         // create company
         // set code, phones, e-mails
        $result=create_contact_from_1C($http_requester, 'company', $data_1C,
                                        $company_custom_field_client_code, $company_custom_field_client_name,
                                        null, $error_status, $LogLineId);         
         
      }
      
   }
   
   return($result);
}


function get_contacts_from_1C($client_data_array, $contact_type) {
   
   $result=array();
   
   if( !is_array($client_data_array) ) return($result);
   
   reset($client_data_array);
   while( list($key, $value)=each($client_data_array) ) {
      
      if( is_array($value)
        && array_key_exists($contact_type, $value)
        && is_array($value[$contact_type]) ) {
        
        $properties_1C=array();   
        $contacts_1C=$value[$contact_type];
        reset($contacts_1C);
        while( list($key_2, $value_2)=each($contacts_1C) ) {
           if( is_array($value_2) ) {
              
              reset($value_2);
              while( list($key_3, $value_3)=each($value_2) ) {
                 
                  if( $key_3==='phone'
                      || $key_3==='email' ) {
                         
                     $properties_1C[$key_3][]=$value_3;    
                  }
                  else {
                     $properties_1C[$key_3]=$value_3;
                  }
                  
              }
              
           }
        }
        
        if( array_key_exists('code_1C', $properties_1C)
            && array_key_exists('name', $properties_1C)
            && strlen($properties_1C['code_1C'])>0
            && strlen($properties_1C['name'])>0 ) {
               
            $result[]=$properties_1C;               
        }
   
      }
      
   }
  
   return($result);
}


function set_code_1C($http_requester, $contacts_array, $contact_type, $data_1C, $contact_custom_field_code, $contact_custom_field_name, &$error_status=false, $LogLineId='') {
   
   global $amocrm_1C_integration_contact_custom_field_principal_client;
   global $amocrm_1C_integration_company_custom_field_principal_client;
   
   $result=array();
   
    // set code
    $contact_id='';
    $contact_name='';
    reset($contacts_array);
    while( list($key, $value)=each($contacts_array) ) {
       if( is_numeric($key) ) {
          $contact_id=strVal($key);
          $contact_name=$value['name'];
          break;
       }
    }
    
    $data_type='contacts';
    if( $contact_type==='company' ) $data_type='companies';
    
    $contacts_1C=$data_1C[$data_type];
              
    if( is_numeric($contact_id)
        && count($contact_custom_field_code)>0
        && count($contact_custom_field_name)>0 ) {
           
       $parameters=array();
       $parameters['id']=$contact_id;
       $parameters['type']=$contact_type;
       
       $updated_fields=array();
       
       for($i=0; $i<count($contacts_1C); $i++ ) {
          
          if( count($contact_custom_field_code)<=$i ) continue;
          if( count($contact_custom_field_name)<=$i ) continue;               
          
          $updated_field_code=$contact_custom_field_code[$i];
          $updated_fields[$updated_field_code]=array('id'=>$updated_field_code, 'values'=>array(array('value'=>$contacts_1C[$i]['code_1C'])));
          
          $updated_field_code=$contact_custom_field_name[$i];
          $updated_fields[$updated_field_code]=array('id'=>$updated_field_code, 'values'=>array(array('value'=>$contacts_1C[$i]['name'])));                
       }
       
       $custom_field_principal_client=$amocrm_1C_integration_contact_custom_field_principal_client;
       if( $contact_type==='company' ) {
          $custom_field_principal_client=$amocrm_1C_integration_company_custom_field_principal_client;
       }
       
       if( isset($custom_field_principal_client)
           && is_numeric($custom_field_principal_client)
           && count($contacts_1C)>0 ) {
              
          $updated_field_code=strVal($custom_field_principal_client);
          $updated_fields[$updated_field_code]=array('id'=>$updated_field_code, 'values'=>array(array('value'=>'1')));                    
       }
       
       $error_status=false;
       if( count($updated_fields)>0 ) {
         
         if( $contact_type==='company' ) { 
            update_company_info($parameters, $updated_fields, $http_requester, $error_status);
         }
         else {
            update_contact_info($parameters, $updated_fields, $http_requester, null, null, null, null, null, $error_status);
         }
         
       }
       
       if( $error_status===false ) {
          
          if( count($updated_fields)>0 ) {
             $result[$contact_type.'_id']=$contact_id;
             $result[$contact_type.'_name']=$contact_name;
          }
          
       }
       else {
          write_log('set_code_1C: update contact request failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
          return($result);
       }
       
    }
    else {
       $error_message='set_code_1C: some parameters are not defined ';
       $error_message.=$contact_type.'_id='.$contact_id.' ';
       $error_message.='number custom fields code='.count($contact_custom_field_code).' ';
       $error_message.='number custom fields name='.count($contact_custom_field_name).' ';
       
       write_log($error_message, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       
       $error_status=true;
       
       return($result);
    }   
   

    return($result);
}


function create_contact_from_1C($http_requester, $contact_type, $data_1C, $custom_field_client_code,  $custom_field_client_name, $addition_fields=array(), &$error_status=false, $LogLineId='') {
   
    global $custom_field_phone_id;
    global $custom_field_phone_enum;
    global $custom_field_email_id;
    global $custom_field_email_enum;
    global $amocrm_1C_integration_contact_custom_field_principal_client;
    global $amocrm_1C_integration_company_custom_field_principal_client;
   
    $result=array();   
   
    // search for contact by code, by phone
    // create contact
    // set code, contact info
    $contact_1C_data=array();
   
    $code_1C='';
    $name_1C='';
    $phone_1C_array=array();
    
    $contacts_1C=array();
    if( $contact_type==='contact' ) {
       $contacts_1C=$data_1C['contacts'];
    }
    elseif( $contact_type==='company' ) {
       $contacts_1C=$data_1C['companies'];
    }
           
    if( is_array($contacts_1C)
        && count($contacts_1C)>0 ) {
    
        reset($contacts_1C);
        $contact_1C_data=current($contacts_1C);
        
        if( is_array($contact_1C_data)
            && array_key_exists('code_1C', $contact_1C_data)
            && array_key_exists('name', $contact_1C_data) ) {
               
            $code_1C=$contact_1C_data['code_1C'];
            $name_1C=$contact_1C_data['name'];
        }
        
        if( is_array($contact_1C_data)
            && array_key_exists('phone', $contact_1C_data) ) {
               
            $phone_1C_array=$contact_1C_data['phone'];  
        }
    }
    
    $phone_1C_main='';
    if( is_array($phone_1C_array)
        && count($phone_1C_array)>0 ) {
       
       reset($phone_1C_array);    
       while( list($key, $value)=each($phone_1C_array) ) {
          $phone_1C_tmp=remove_symbols($value);
          $phone_1C_tmp=substr($phone_1C_tmp, -10);
          
          if( strlen($phone_1C_tmp)>=10 ) {
             $phone_1C_main=$phone_1C_tmp;
             break;
          }
       }
    }
    
    // search by code    
    $search_by_code_performed=false;
    $contacts_found=array();
    if( strlen($code_1C)>=3
        && count($custom_field_client_code)>0 ) {
       
      $parameters=array();
      $parameters['type']=$contact_type;
      $parameters['query']=$code_1C;
      
      $contacts_found=contact_search_filtered($parameters, $http_requester, $code_1C, $custom_field_client_code, $error_status);
      $search_by_code_performed=true;
    }
    
    if( $error_status===true ) {
       write_log('create_contact_from_1C: contact_search_filtered failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       return($result);
    }
    
    if( count($contacts_found)>0 ) {
       
       reset($contacts_found);
       $contact_current=current($contacts_found);
       
       $result[$contact_type.'_id']=$contact_current['id'];
       $result[$contact_type.'_name']=$contact_current['name'];
       return($result);
    }
    
    // search by phone
    $custom_field_client_phone=array();
    if( isset($custom_field_phone_id)
        && is_numeric($custom_field_phone_id) ) {
           
        $custom_field_client_phone[]=$custom_field_phone_id;   
    }
    
    $search_by_phone_performed=false;
    $contacts_found=array();
    if( strlen($phone_1C_main)>=10
        && count($custom_field_client_phone)>0 ) {
       
      $parameters=array();
      $parameters['type']=$contact_type;
      $parameters['query']=$phone_1C_main;            
       
      $contacts_found=contact_search_filtered($parameters, $http_requester, $phone_1C_main, $custom_field_client_phone, $error_status);
      $search_by_phone_performed=true;
    }
    
    if( $error_status===true ) {
       write_log('create_contact_from_1C: contact_search_filtered failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       return($result);
    }
    
    if( count($contacts_found)>0 ) {
       
       reset($contacts_found);
       $contact_current=current($contacts_found);
       
       $result[$contact_type.'_id']=$contact_current['id'];
       $result[$contact_type.'_name']=$contact_current['name'];
       
       $code_1C_is_set=false;
       $contact_current_array=array( intVal($contact_current['id'])=>$contact_current );
       reset($custom_field_client_code);
       while( list($key, $value)=each($custom_field_client_code) ) {
         $value_array=get_field_values($contact_current_array, $value);
         
         if( is_array($value_array)
             && count($value_array)>0 ) {
                
            $code_1C_is_set=true;
            write_log('create_contact_from_1C: code_1C is present in '.$contact_type.'_id='.$contact_current['id'], $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
         }
       }
       
       if( $code_1C_is_set===false ) {
          
          write_log('create_contact_from_1C: set code_1C for '.$contact_type.'_id='.$contact_current['id'], $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
          
          $contact_for_code_update_array=array();
          $contact_for_code_update_array[intVal($contact_current['id'])]=array('name'=>$contact_current['name']);
          
          set_code_1C($http_requester, $contact_for_code_update_array, $contact_type, $data_1C, $custom_field_client_code, $custom_field_client_name, $error_status, $LogLineId);
          
          if( $error_status===true ) {
             write_log('create_contact_from_1C: set_code_1C failed ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
          }
       
       }
       
       return($result);
    }
    
    if( $search_by_code_performed===true
        && $search_by_phone_performed===true ) {
         
        // create
        if( $contact_type==='contact' ) {
            
            $result=create_contact_from_1C_data($http_requester, $contact_type, $contacts_1C,
                                                $custom_field_client_code, $custom_field_client_name, $error_status, $LogLineId);
            
            if( $error_status===true ) {
                write_log('create_contact_from_1C: create_contact_from_1C_data failed, contact_type='.$contact_type, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
                return($result);
            }            
           
        }
        elseif( $contact_type==='company' ) {
           
            $result=create_contact_from_1C_data($http_requester, $contact_type, $contacts_1C,
                                                $custom_field_client_code, $custom_field_client_name, $error_status, $LogLineId);
            
            if( $error_status===true ) {
                write_log('create_contact_from_1C: create_contact_from_1C_data failed, contact_type='.$contact_type, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
                return($result);
            }             
                   
        }
        
    }  
   
   
   return($result);   
}


function create_contact_from_1C_data($http_requester, $contact_type, $contacts_1C,
                                     $custom_field_client_code, $custom_field_client_name, $error_status=false, $LogLineId='') {
   
   global $amocrm_1C_integration_contact_custom_field_principal_client;
   global $amocrm_1C_integration_company_custom_field_principal_client;
                                        
   $result=array();
   
   $contact_1C_data=array();
   if( is_array($contacts_1C)
       && count($contacts_1C)>0 ) {
       
       reset($contacts_1C);   
       $contact_1C_data=current($contacts_1C);   
   }
   
   $contact_name='';
   if( is_array($contact_1C_data)
       && array_key_exists('name', $contact_1C_data) ) {
          
       $contact_name=$contact_1C_data['name'];   
   }
      
   write_log('create_contact_from_1C_data: create contact, contact_type='.$contact_type.' name='.$contact_name, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
   
   $new_contact_data=array();
   
   reset($contact_1C_data);
   while( list($key, $value)=each($contact_1C_data) ) {
      
      if( $key!=='name'
          && $key!=='phone'
          && $key!=='email' ) {
      
          continue;   
      }
      
      $new_contact_data[$key]=$value;
   }
               
   $count_custom_fileds=min(count($contacts_1C), count($custom_field_client_code), count($custom_field_client_name));
   for($i=0; $i<$count_custom_fileds; $i++) {
      
      if( is_array($contacts_1C[$i])
          && array_key_exists('code_1C', $contacts_1C[$i]) ) {
             
          $new_contact_data[$custom_field_client_code[$i]]=$contacts_1C[$i]['code_1C'];
      }
      
      if( is_array($contacts_1C[$i])
          && array_key_exists('name', $contacts_1C[$i]) ) {
             
          $new_contact_data[$custom_field_client_name[$i]]=$contacts_1C[$i]['name'];
      }
      
   }
   
   $field_principal_client=null;
   if( $contact_type==='contact' ) {
      $field_principal_client=$amocrm_1C_integration_contact_custom_field_principal_client;
   }
   elseif( $contact_type==='company' ) {
      $field_principal_client=$amocrm_1C_integration_company_custom_field_principal_client;
   }
      
   if( isset($field_principal_client)
       && is_numeric($field_principal_client)
       && $count_custom_fileds>0 ) {
          
       $new_contact_data[$field_principal_client]='1';   
   }
   
   $contact_id='';
   if( $contact_type==='contact' ) {
      $contact_id=create_contact($http_requester, $new_contact_data, $error_status);
   }
   elseif( $contact_type==='company' ) {
      $contact_id=create_company($http_requester, $new_contact_data, $error_status);
   }
   
   if( $error_status===true ) {
       write_log('create_contact_from_1C_data: create_contact failed, contact_type='.$contact_type, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       write_log('create_contact_from_1C_data: create_contact failed, cintact_type='.$contact_type, $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       return($result);
   }
   
   $result[$contact_type.'_id']=$contact_id;
   $result[$contact_type.'_name']=$contact_name;
   
   return($result);
}

?>