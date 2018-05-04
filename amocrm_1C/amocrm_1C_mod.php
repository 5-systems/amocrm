<?php 


function amocrm_1C_create_contact(&$http_requester, $contact_data, $user_id, $contacts_array=array(), $companies_array=array(), &$error_status=false, $LogLineId='') {
   
   $result=array();
   
   if( strlen($user_id)===0
       || !is_numeric($user_id)
       || intVal($user_id)<=0 ) {
          
       return($result);          
   }
   
   $code_array=array();
   $phone_array=array();
   $email_array=array();
   
   $search_by_contact_info=false;
   if( count($contacts_array)===0
       || count($companies_array)===0 ) {

       $search_by_contact_info=true;
   }
   else {
      
      // find codes
      
      if( count($code_array)===0 ) $search_by_contact_info=true;
      
   }
   
   if( $search_by_contact_info===true ) {
      
      if( is_array($contact_data)
         && array_key_exists('phone', $contact_data)
         && strlen($contact_data['phone'])>=10 ) {
         
         $phone_array[]=$contact_data['phone'];
      }
      
      if( is_array($contact_data)
         && array_key_exists('email', $contact_data)
         && strlen($contact_data['email'])>=10 ) {
         
         $email_array[]=$contact_data['email'];
      }
   
   }
   
  
   return($result);   
}








?>