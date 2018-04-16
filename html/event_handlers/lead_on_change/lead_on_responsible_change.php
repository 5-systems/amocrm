<?php

   date_default_timezone_set('Etc/GMT-3');

   $settigs_found=false;
   if( isset($_REQUEST['param_login'])
      && strlen($_REQUEST['param_login'])>0 ) {
         
      $current_dir_path=getcwd();
      $current_dir_path=rtrim($current_dir_path, '/').'/';
      $current_dir_path.='../../';
      
      $settings_file_path=$current_dir_path.'amocrm_settings_'.strVal($_REQUEST['param_login']).'.php';
      if( file_exists($settings_file_path) ) {
         require_once($settings_file_path);
         $settigs_found=true;
      }
   }
   
   if( $settigs_found===false ) {
      require_once('../../amocrm_settings.php');
   }
   
   require_once('../../5c_amocrm_lib.php');
   

   @$data=json_encode($_REQUEST);
   if( !isset($data) ) $data='';
   
   write_log('blank_line', $amocrm_log_file, 'LEAD_RESP');
   write_log($data, $amocrm_log_file, 'LEAD_RESP');
   
 
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
      $http_requester->{'lock_priority'}=0;
      $http_requester->{'max_wait_time_for_lock_sec'}=20;
   }
   
   $data_array=array();
   if( strlen($data)>0 ) {
      $data_array=json_decode($data, true);     
   }
   
   $LogLineId='';
   
   // get user_id
   $lead_id='';
   $user_id='';
   $pipeline_id='';
   $status_id='';
   
   if( is_array($data_array)
       && array_key_exists('leads', $data_array)
       && is_array($data_array['leads'])
       && array_key_exists('responsible', $data_array['leads'])
       && is_array($data_array['leads']['responsible']) ) {
       
       $leads_array=$data_array['leads']['responsible'];
       reset($leads_array);
       while( list($key, $value)=each($leads_array) ) {
          
          if( is_array($value)
              && array_key_exists('id', $value) ) {
                
              $lead_id=strVal($value['id']);
          }
          
          if( is_array($value)
              && array_key_exists('responsible_user_id', $value) ) {
                 
             $user_id=strVal($value['responsible_user_id']);
          }
          
          if( is_array($value)
              && array_key_exists('pipeline_id', $value) ) {
                
             $pipeline_id=strVal($value['pipeline_id']);
          }
          
          if( is_array($value)
              && array_key_exists('status_id', $value) ) {
                
              $status_id=strVal($value['status_id']);
          }
          
       }
   
   }
   
   
   $LogLineId=$lead_id;

   if( strlen($lead_id)===0 ) {
      write_log('lead id not found ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   if( strlen($user_id)===0 ) {
      write_log('lead responsible_user_id not found ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   if( strlen($pipeline_id)===0 ) {
      write_log('lead pipeline_id not found ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   if( strlen($status_id)===0 ) {
      write_log('lead status_id not found ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   
   // get contact_info: pipeline_id, pipeline_status_id
   $user_info=array();
   $error_status=false;
   $user_info=get_user_info_by_user_phone($user_id, $custom_field_user_amo_crm, $custom_field_user_amo_crm, $http_requester,
                                             null, null, null, null, null, $error_status);
   
   if( $error_status===true ) {
      write_log('get_user_info_by_user_phone failed ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   $user_pipeline_id='';
   $user_pipeline_status_id='';
   
   $contact_is_found=false;
   if( is_array($user_info)
       && count($user_info)>0 ) {
      
      if( array_key_exists('pipeline_id', $user_info) ) {
         $user_pipeline_id=$user_info['pipeline_id'];
      }
      
      if( array_key_exists('pipeline_status_id', $user_info) ) {
         $user_pipeline_status_id=$user_info['pipeline_status_id'];
      }
      
      if( array_key_exists('contact_id', $user_info) ) $contact_is_found=true;
      
   }

   if( !is_array($user_info)
       || count($user_info)===0 ) {
          
      write_log('get_user_info_by_user_phone contact not found ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
   if( $contact_is_found===false ) {
      write_log('get_user_info_by_user_phone pipeline fields not found in contact ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      exit;
   }
   
      
   if( strlen($user_pipeline_id)===0
       || strlen($user_pipeline_status_id)===0 ) {
          
       $user_pipeline_id=$status_accepted_for_work_pipeline_id;
       $user_pipeline_status_id=$status_accepted_for_work;         
   }

   if( strlen($user_pipeline_id)===0
       || strlen($user_pipeline_status_id)===0 ) {
          
       write_log('user pipeline_id and status_id are not defined ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
       exit;
   }

    
   // compare contact pipeline and lead pipeline
   if( strVal($pipeline_id)!==strVal($user_pipeline_id) ) {
      
      $message_text='lead on change:  pipeline_id='.$pipeline_id.' status_id='.$status_id;
      $message_text.=' change to pipeline_id='.$user_pipeline_id.' status_id='.$user_pipeline_status_id;
      write_log($message_text, $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      
      
      $parameters=array('id'=>strVal($lead_id));
      
      $updated_fields=array();
      $updated_fields[intVal($lead_id)]=array('status_id'=>intVal($user_pipeline_status_id), 'pipeline_id'=>intVal($user_pipeline_id));
     
      $update_status=update_leads_info($parameters, $updated_fields, $http_requester);
      if( $update_status===false ) {     
         write_log('pipeline change failed ', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
      }   
      
   }
     
   write_log('Finish', $amocrm_log_file, 'LEAD_RESP '.$LogLineId);
   
?>   
