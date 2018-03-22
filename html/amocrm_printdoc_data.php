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
   require_once('5c_files_lib.php');
   require_once('5c_database_lib.php');
      
   
   @$param_login=$_REQUEST['param_login'];
   @$param_amocrm_email=$_REQUEST['param_amocrm_email'];
   @$param_amocrm_hash=$_REQUEST['param_amocrm_hash'];
   @$param_lead_id=$_REQUEST['param_lead_id'];
   @$param_contact_id=$_REQUEST['param_contact_id'];
   @$param_company_id=$_REQUEST['param_company_id'];
      
   if(!isset($param_login)) $param_login='';
   if(!isset($param_amocrm_email)) $param_amocrm_email='';
   if(!isset($param_amocrm_hash)) $param_amocrm_hash='';
   if(!isset($param_lead_id)) $param_lead_id='';
   if(!isset($param_contact_id)) $param_contact_id='';
   if(!isset($param_company_id)) $param_company_id='';

   
   $LogLineId=$param_login;
   

   // Start
   $return_result='';
   $current_time=time();
   write_log('blank_line', $amocrm_log_file, 'PRINT_DOC '.$LogLineId);
   write_log($_REQUEST, $amocrm_log_file, 'PRINT_DOC '.$LogLineId);

   // Check parameters
   if( strlen($param_login)===0
       || strlen($param_amocrm_email)===0
       || strlen($param_amocrm_hash)===0 ) {
          
       $return_result='Authorization parameters are not found ';
       exit($return_result);
   }
    
   // Create http-requester
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$param_amocrm_email;
   $http_requester->{'USER_HASH'}=$param_amocrm_hash;
   $http_requester->{'amocrm_account'}=$param_login;
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
       $http_requester->{'lock_priority'}=0;
       $http_requester->{'max_wait_time_for_lock_sec'}=20;
   }

   $result_array=array();
   
   // Company
   if( strlen($param_company_id)>0
       && is_numeric($param_company_id) ) {
        
       $parameters=array();
       $parameters['type']='company';
       $parameters['id']=$param_company_id;
       
       $error_status=false;
       $companies_info=get_companies_info($parameters, $http_requester,
                                            null, null, null,
                                            null, null, $error_status, 'json');
       
       if( $error_status!==false ) {
          write_log('Get company info: request error', $amocrm_log_file, 'PRINT_DOC '.$LogLineId);
          exit($return_result);        
       }
       
       if( strlen($companies_info)>0 ) {
          $companies_info_array=json_decode($companies_info, true);
          
          if( is_array($companies_info_array)
              && array_key_exists('response', $companies_info_array)
              && is_array($companies_info_array['response'])
              && array_key_exists('contacts', $companies_info_array['response'])
              && is_array($companies_info_array['response']['contacts'])
              && count($companies_info_array['response']['contacts'])>0 ) {
                 
              $result_array['company']=$companies_info_array['response']['contacts'][0];
          }
          
       }
          
   }
   
   // Contact
   if( strlen($param_contact_id)>0
      && is_numeric($param_contact_id) ) {
         
      $parameters=array();
      $parameters['type']='contact';
      $parameters['id']=$param_contact_id;
      
      $error_status=false;
      $contacts_info=get_contact_info($parameters, $http_requester,
                                          null, null, null,
                                          null, null, $error_status, 'json');

      
      if( $error_status!==false ) {
         write_log('Get contact info: request error', $amocrm_log_file, 'PRINT_DOC '.$LogLineId);
         exit($return_result);
      }
      
      if( strlen($contacts_info)>0 ) {
         $contacts_info_array=json_decode($contacts_info, true);
         
         if( is_array($contacts_info_array)
            && array_key_exists('response', $contacts_info_array)
            && is_array($contacts_info_array['response'])
            && array_key_exists('contacts', $contacts_info_array['response'])
            && is_array($contacts_info_array['response']['contacts'])
            && count($contacts_info_array['response']['contacts'])>0 ) {
               
               $result_array['contact']=$contacts_info_array['response']['contacts'][0];
            }
            
      }
      
   }
   
   // Lead
   if( strlen($param_lead_id)>0
       && is_numeric($param_lead_id) ) {
         
      $parameters=array();
      $parameters['id']=$param_lead_id;
      
      $error_status=false;
      $leads_info=get_leads_info($parameters, $http_requester,
                                       null, null, null,
                                       null, null, $error_status, 'json');                                                   
      
      if( $error_status!==false ) {
         write_log('Get lead info: request error', $amocrm_log_file, 'PRINT_DOC '.$LogLineId);
         exit($return_result);
      }
      
      if( strlen($leads_info)>0 ) {
         $leads_info_array=json_decode($leads_info, true);
         
         if( is_array($leads_info_array)
            && array_key_exists('response', $leads_info_array)
            && is_array($leads_info_array['response'])
            && array_key_exists('leads', $leads_info_array['response'])
            && is_array($leads_info_array['response']['leads'])
            && count($leads_info_array['response']['leads'])>0 ) {
               
               $result_array['lead']=$leads_info_array['response']['leads'][0];
            }
            
      }
      
   }
   
   // Notes
   if( strlen($param_lead_id)>0
       && is_numeric($param_lead_id) ) {
         
      $parameters=array();
      $parameters['element_id']=$param_lead_id;
      $parameters['type']='lead';
      $parameters['note_type']=4;
           
      $error_status=false;
      $notes_info=get_notes_info($parameters, $http_requester,
                                 null, null, null,
                                 null, null, $error_status, 'json');
      
      
      if( $error_status!==false ) {
         write_log('Get notes info: request error', $amocrm_log_file, 'PRINT_DOC '.$LogLineId);
         exit($return_result);
      }
      
      if( strlen($notes_info)>0 ) {
         $notes_info_array=json_decode($notes_info, true);
         
         if( is_array($notes_info_array)
            && array_key_exists('response', $notes_info_array)
            && is_array($notes_info_array['response'])
            && array_key_exists('notes', $notes_info_array['response'])
            && is_array($notes_info_array['response']['notes'])
            && count($notes_info_array['response']['notes'])>0 ) {
               
               $result_array['notes']=$notes_info_array['response']['notes'];
            }
            
      }
      
   }
   
   $return_result=json_encode($result_array);
   
   // close connection to db
   if( isset($db_conn) ) {
      $db_conn->close();
   }
   
   echo $return_result;
   
   
?>   