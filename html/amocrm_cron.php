<?php

   // version 30.10.2017

   require_once('amocrm_settings.php');
   require_once('5c_std_lib.php');  
   require_once('5c_files_lib.php');
   require_once('5c_database_lib.php');
   require_once('5c_amocrm_lib.php');

   date_default_timezone_set('Etc/GMT-3');
   
   $current_time=time();
  
   if( $write_log_cron===true ) { 
   	write_log('blank_line', $amocrm_log_file, 'CRON_TASK');
   }
   
   // Clean amocrm_phonestation database
   $db_host=$amocrm_database_host;
   if( strlen($amocrm_database_port)>0 ) $db_host.=':'.$amocrm_database_port;
   
   $db_conn=mysql_connect($db_host, $amocrm_database_root_user, $amocrm_database_root_password);
   if( $db_conn!==false ) {
   
      $select_condition="date<'&current_date_shifted&'";
   
      $current_date_shifted=date('Y-m-d H:i:s', $current_time-60*60*10);   
      template_set_parameter('current_date_shifted', $current_date_shifted, $select_condition);
      
      mysql_delete_rows($db_conn, $amocrm_database_name, 'calls', $select_condition, $amocrm_log_file);
      
      mysql_close($db_conn);        
   }
   elseif( $write_log_cron===true ) {
      write_log('Connection to database is failed: '.mysql_error(), $amocrm_log_file, 'CLEAN OLD CALLS');   
   }
   
   // Update call duration
   $current_time=time();
   $date_create_note_to=$current_time-$records_shift_time_for_update_duration_in_hours_phone_station*60*60;
   $date_create_note_from=$date_create_note_to-$records_period_time_for_update_duration_in_hours_phone_station*60*60;   
   
   $get_notes_from_date=date('d M Y H:i:s', $date_create_note_from);
   
   $http_requester=new amocrm_http_requester;
   $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
   $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
   $http_requester->{'amocrm_account'}=$amocrm_account;
   $http_requester->{'coockie_file'}=$amocrm_coockie_file;
   $http_requester->{'amocrm_log_file'}=$amocrm_log_file;
   $http_requester->{'header'}=array('if-modified-since: '.$get_notes_from_date);
   
   $parameters=array();
   $parameters['type']='contact';
   $parameters['limit_rows']='100';
   $notes_array=get_notes_info($parameters, $http_requester);
   
   $http_requester->{'header'}=null;
   
   $notes_array_for_sort=array();
   reset($notes_array);
   while( list($key, $value)=each($notes_array) ) {
      if( array_key_exists('date_create', $value) ) $notes_array_for_sort[$key]=$value['date_create'];
   }

   if( count($notes_array_for_sort)===count($notes_array) ) {
      array_multisort($notes_array_for_sort, SORT_ASC, $notes_array);  
   }   
   
   $selected_notes_array=array();
   
   reset($notes_array);
   while( list($key, $value)=each($notes_array) ) {
      
      if( is_array($value)     
          && array_key_exists('note_type', $value)         
          && (strval($value['note_type'])==='10'
              || strval($value['note_type'])==='11')
          && array_key_exists('text', $value)
          && is_string($value['text'])
          && strlen($value['text'])>0 ) {
          
          $text_array=json_decode($value['text'], true);
          if( is_array($text_array)
	      && array_key_exists('DURATION', $text_array) 
	      && strVal($text_array['DURATION'])==='0'
	      && intVal($value['date_create'])>=$date_create_note_from
	      && intVal($value['date_create'])<=$date_create_note_to ) {
          
	    $value['text_array']=$text_array;
	    $selected_notes_array[]=$value;
	  }
	  
      }    
   }
   
   if( count($selected_notes_array)>0
       && $write_log_cron===true ) {

      write_log('Selected '.count($selected_notes_array).' records ', $amocrm_log_file, 'UPDATE DURATION');
   }
   
   // Get full record path   
   $_url_records_left=$url_records;
   $_url_records_left=rtrim($_url_records_left, '/');
   
   $_dir_records=$dir_records;
   $_dir_records=rtrim($_dir_records, '/');
   $_dir_records=( strlen($_dir_records)>0 ? $_dir_records.'/': '');
   
   $elements_number=count($selected_notes_array);
   for( $i=0; $i<$elements_number; $i++ ) {
   
      $value=$selected_notes_array[$i];
      
      $_link_record='';
      if( is_array($value)
          && array_key_exists('text_array', $value)
          && is_array($value['text_array'])
          && array_key_exists('LINK', $value['text_array'])
          && is_string($value['text_array']['LINK']) ) {
          
          $_link_record=$value['text_array']['LINK'];
      }
      
      $_host_position=strpos($_link_record, $_url_records_left);
      $_short_record_path='';
      if( $_host_position!==false
          && strlen($_link_record)>($_host_position+strlen($_url_records_left)) ) {
          
          $_short_record_path=substr($_link_record, $_host_position+strlen($_url_records_left));
          $_short_record_path=ltrim($_short_record_path, '/');
      }
      
      $_full_record_path=$_dir_records.$_short_record_path;
      
      $selected_notes_array[$i]['record_path']=$_full_record_path;      
   }

   if( $write_log_cron===true ) {   
   	write_log('Record paths are set ', $amocrm_log_file, 'UPDATE DURATION');   
   }
   
   // Calculate call duration
   $elements_number=count($selected_notes_array);
   for( $i=0; $i<$elements_number; $i++ ) {
   
      $value=$selected_notes_array[$i];
      $_full_record_path='';
      if( is_array($value)
          && array_key_exists('record_path', $value)
          && is_string($value['record_path']) ) {
          
          $_full_record_path=$value['record_path'];
      }    
      
      $_record_duration=0;
      if( strlen($_full_record_path)>0
          && file_exists($_full_record_path) ) {
          
	    $_file_info=get_file_info($_full_record_path);
	    
  	    $_file_coeff_size_sec=0;
	    if( strcasecmp($_file_info['extension'], 'mp3')===0 ) {
		  $_file_coeff_size_sec=$records_coeff_byte_to_sec_mp3_phone_station;
	    }
	    elseif( strcasecmp($_file_info['extension'], 'wav')===0 ) {
		  $_file_coeff_size_sec=$records_coeff_byte_to_sec_wav_phone_station;
	    }
	    

	    if( array_key_exists('size', $_file_info) ) {
		  $_record_duration=intVal($_file_info['size'])*$_file_coeff_size_sec;
	    }
                   
      }
      
      $selected_notes_array[$i]['record_duration']=round($_record_duration, 0);
          
   }
  
   if( $write_log_cron===true ) { 
  	write_log('Durations are set ', $amocrm_log_file, 'UPDATE DURATION');
   }

   // Select records with non-zero duration
   $records_non_zero_duration=array();
   reset($selected_notes_array);
   while( list($key, $value)=each($selected_notes_array) ) {
       if( $value['record_duration']>0 ) {
           $records_non_zero_duration[]=$value;
       }
   }
   
   
   // Update record duration
   $parameters=array();
   $parameters['type']='contact';
   $parameters['id']=array();
   
   $updated_fields=array();
   
   reset($records_non_zero_duration);
   while( list($key, $value)=each($records_non_zero_duration) ) {
        $parameters['id'][]=$value['note_id'];
       
        $text_json=$value['text'];
        $text_array=json_decode($text_json, true);     
        $text_array['DURATION']=$value['record_duration'];
        $text_json=json_encode($text_array);

        $note_data=array("text"=>$text_json);
        $updated_fields[intVal($value['note_id'])]=$note_data;       
   }
   
   $status=true;
   if( array_key_exists('id', $parameters)
       && count($parameters['id'])>0 ) {
       
      $status=update_notes_info($parameters, $updated_fields, $http_requester);      
   }

   
   $update_status_text='Finish: duration of record is updated ';
   if( $status===false ) {
       $update_status_text='Finish: update of record duration is failed ';      
   }
   
   if( $write_log_cron===true ) {
   	write_log($update_status_text, $amocrm_log_file, 'UPDATE DURATION');
   }
   
?>
