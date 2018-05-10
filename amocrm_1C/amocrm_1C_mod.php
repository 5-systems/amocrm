<?php 

   require_once('5c_amocrm_lib.php');
   require_once('5c_std_lib.php');


function amocrm_1C_create_contact(&$http_requester, $contact_data, $user_id, $contacts_array=array(), $companies_array=array(), &$error_status=false, $LogLineId='') {
   
   global $amocrm_1C_integration_web_service_url;
   
   $result=array();
   
   // Call is missed or user does not work with amoCRM
   if( strlen($user_id)===0
       || !is_numeric($user_id)
       || intVal($user_id)<=0 ) {
          
       return($result);          
   }

   
   // Define client and company if code_1C is set
   $client_id='';
   $client_name='';
   
   $company_id='';
   $company_name=''; 
   
   $client_companies_array=array();
   
   $client_company_array=define_contact_company_with_code_1C($http_requester, $contacts_array, $client_companies_array, $companies_array, $error_status, $LogLineId);
     
   if( array_key_exists('client_id', $client_company_array) ) {          
      $client_id=$client_company_array['client_id'];
      $client_name=$client_company_array['client_name'];
   }
   
   if( array_key_exists('company_id', $client_company_array) ) {        
      $company_id=$client_company_array['company_id'];
      $company_name=$client_company_array['company_name'];
   }   
   
   // code_1C of client is defined
   if( strlen($client_id)>0
       || strlen($company_id)>0 ) {
       
       // Request to 1C to define address type
       $result['address_type']='';
          
       $client_array=array();
       $client_array['id']=$client_id;
       $client_array['name']=$client_name;
       
       $result['contact']=$client_array;
       
       $company_array=array();
       $company_array['id']=$company_id;
       $company_array['name']=$company_name;
       
       $result['company']=$company_array;
       
       return($result);
   }
   
   // code_1C of client is not defined
      
   // Request to 1C to define code_1C
   if( !isset($amocrm_1C_integration_web_service_url) ) {  
       write_log('amocrm_1C_create_contact: amocrm_1C_integration_web_service_url constant not defined ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
       return($result);
   }
   
   $common_info=array();
   $common_info=merge_contact_data($contacts_array, $common_info);
   $common_info=merge_contact_data($client_companies_array, $common_info);
   $common_info=merge_contact_data($companies_array, $common_info);
   
   $request_method='get_call_data';
   $request_data=json_encode($common_info);
   
   $headers=array('Content-Type:application/json');
   
   $request_url=$amocrm_1C_integration_web_service_url;   
   $request_result='';
   if(  strlen($request_url)>0 ) {
           
      $request_separator='?';
      if( strpos($request_url, '?')!==false ) {
         $request_separator='&';
      }
   
      $request_url.=$request_separator.'method='.$request_method;
      
      $request_result=request_POST($request_url, $request_data, $http_requester->{'log_file'}, $headers, 3);   
      
      write_log('amocrm_1C_create_contact: request result='.strVal($request_result), $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);
   
   }
   else {
      write_log('amocrm_1C_create_contact: request url is not set ', $http_requester->{'log_file'}, 'REG_CALL '.$LogLineId);    
   }
   
   $create_contact_without_integration_1C=false;
   if( strlen($request_result)>0 ) {
      
      // create contact and company
      
   }
   else {      
      $create_contact_without_integration_1C=true;
   }
   
   
   if( $create_contact_without_integration_1C===true ) {
      $result=create_contact_default_local($http_requester, $contact_data, $user_id, $contacts_array, $companies_array, $error_status, $LogLineId);
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
          && array_key_exists('principle_client_code_1C', $value)
          && strlen($value['principle_client_code_1C'])>0 ) {
             
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
          && array_key_exists('principle_client_code_1C', $value)
          && strlen($value['principle_client_code_1C'])>0 ) {
             
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
          && array_key_exists('principle_client_code_1C', $value)
          && strlen($value['principle_client_code_1C'])>0 ) {
             
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
   
   $result['client_id']=$client_id;
   $result['client_name']=$client_name;   
   
   $result['company_id']=$company_id;
   $result['company_name']=$company_name;   
   
   return($result);
}


function define_code_1C(&$response_array, $code_fields, $principle_client_field) {
   
   $result=true;
   
   reset($response_array);
   while( list($key, $value)=each($response_array) ) {
      $response_array[$key]['code_1C']=array();
      $response_array[$key]['principle_client']='';
      $response_array[$key]['principle_client_code_1C']='';
   }   
   
   if( is_numeric($principle_client_field) ) {
      $code_fields[]=$principle_client_field;    
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
                
                if( $code_fields[$i]===$principle_client_field ) {
                  $response_array[$contact_key]['principle_client']=$values[$contact_key][0]; 
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
      $principle_client_string=strVal($response_array[$key]['principle_client']);
      
      $principle_client_code_1C='';
      reset($code_1C_array);
      if( strlen($principle_client_string)>0
          && is_numeric($principle_client_string) ) {
          
          $principal_client_index= intVal($principle_client_string)-1;   
          if( array_key_exists($principal_client_index, $code_1C_array)
              && strlen($code_1C_array[$principal_client_index])>0 ) {
                 
              $principle_client_code_1C=$code_1C_array[$principal_client_index];
          }   
             
      }
      
      if( strlen($principle_client_code_1C)===0
          && count($code_1C_array)>0 ) {
          
          reset($code_1C_array);   
          while( list($key_2, $value_2)=each($code_1C_array) ) {
             if( strlen($value_2)>0 ) {
                $principle_client_code_1C=$value_2;
                break;
             }  
          }   
      }
      
      $response_array[$key]['principle_client_code_1C']=$principle_client_code_1C;      
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





?>