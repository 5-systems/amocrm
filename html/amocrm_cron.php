<?php

   // version 26.03.2018

   require_once('5c_std_lib.php');  
   require_once('5c_files_lib.php');
   require_once('5c_database_lib.php');
   require_once('5c_amocrm_lib.php');

   date_default_timezone_set('Etc/GMT-3');

   if( count($_REQUEST)===0 ) {
       
      if( count($argv)>1 ) $_REQUEST['param_login']=$argv[1];
       
   }
   
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

   if( !isset($_REQUEST['param_login']) ) $_REQUEST['param_login']='';
   
   $current_time=time();
  
   if( $write_log_cron===true ) { 
   	write_log('blank_line', $amocrm_log_file, 'CRON_TASK '.$_REQUEST['param_login']);
   	write_log($_REQUEST, $amocrm_log_file, 'CRON_TASK '.$_REQUEST['param_login']);
   }
   
   // Clean amocrm_phonestation database
   if( $clean_amocrm_phonestation_database===true ) {
   
       $db_host=$amocrm_database_host;
       if( strlen($amocrm_database_port)>0 ) $db_host.=':'.$amocrm_database_port;
       
       $db_conn=mysql_connect($db_host, $amocrm_database_root_user, $amocrm_database_root_password);
       if( $db_conn!==false ) {
          
          // from calls 
          $select_condition="date<'&current_date_shifted&'";
       
          $current_date_shifted=date('Y-m-d H:i:s', $current_time-60*5);   
          template_set_parameter('current_date_shifted', $current_date_shifted, $select_condition);
          
          mysql_delete_rows($db_conn, $amocrm_database_name, 'calls', $select_condition, $amocrm_log_file);
          
          // from locks
          $select_condition="id=2 and time<'&current_time_shifted&'";
          
          $current_time_shifted=$current_time-60*5;
          template_set_parameter('current_time_shifted', $current_time_shifted, $select_condition);
          
          mysql_delete_rows($db_conn, $amocrm_database_name, 'locks', $select_condition, $amocrm_log_file);
          
          // from queue
          $select_condition="time<'&current_time_shifted&'";
          
          $current_time_shifted=$current_time-60*5;
          template_set_parameter('current_time_shifted', $current_time_shifted, $select_condition);
          
          mysql_delete_rows($db_conn, $amocrm_database_name, 'queue', $select_condition, $amocrm_log_file);
          
          mysql_close($db_conn);        
       }
       elseif( $write_log_cron===true ) {
           write_log('Connection to database '.$amocrm_database_name.' is failed: '.mysql_error(), $amocrm_log_file, 'CLEAN OLD CALLS '.$_REQUEST['param_login']);   
       }
   
   }
   
   // Clean crm_linkedid table
   if( $clean_crm_linkedid_table===true ) {
       
       $db_host=$crm_linkedid_host;
       if( strlen($crm_linkedid_port)>0 ) $db_host.=':'.$crm_linkedid_port;
       
       $db_conn=mysql_connect($db_host, $crm_linkedid_user, $crm_linkedid_password);
       if( $db_conn!==false ) {
           
           $select_condition="linkedid<'&current_date_shifted&'";
           
           $current_date_shifted=$current_time-60*60*10;
           template_set_parameter('current_date_shifted', $current_date_shifted, $select_condition);
           
           mysql_delete_rows($db_conn, $crm_linkedid_database_name, 'crm_linkedid', $select_condition, $amocrm_log_file);
           
           mysql_close($db_conn);
       }
       elseif( $write_log_cron===true ) {
           write_log('Connection to database '.$crm_linkedid_database_name.' is failed: '.mysql_error(), $amocrm_log_file, 'CLEAN crm_linkedid '.$_REQUEST['param_login']);
       }
       
   }   
   
   // Update call duration
   if( $update_call_duration===true ) {
   
       $current_time=time();
       $date_create_note_to=$current_time-$records_shift_time_for_update_duration_in_hours_phone_station*60*60;
       $date_create_note_from=$date_create_note_to-$records_period_time_for_update_duration_in_hours_phone_station*60*60;   
       
       $get_notes_from_date=date('d M Y H:i:s', $date_create_note_from);
       
       $http_requester=new amocrm_http_requester;
       $http_requester->{'USER_LOGIN'}=$amocrm_USER_LOGIN;
       $http_requester->{'USER_HASH'}=$amocrm_USER_HASH;
       $http_requester->{'amocrm_account'}=$amocrm_account;
       $http_requester->{'coockie_file'}=$amocrm_coockie_file;
       $http_requester->{'header'}=array('if-modified-since: '.$get_notes_from_date);

       if( isset($amocrm_sleep_time_after_request_microsec)
           && is_numeric($amocrm_sleep_time_after_request_microsec)
           && intVal($amocrm_sleep_time_after_request_microsec)>0 ) {
               
           $http_requester->{'sleep_time_after_request_microsec'}=$amocrm_sleep_time_after_request_microsec;
       }
       
       if( $write_log_cron===true ) {
           $http_requester->{'log_file'}=$amocrm_log_file;
       }
       
       $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
       if( isset($db_conn) ) {
           
           $db_conn->autocommit(true);
           $http_requester->{'lock_database_connection'}=$db_conn;
           $http_requester->{'lock_priority'}=20;
       }
       
       $notes_array=array();
       
       $element_types=array();
       $element_types['contact']='1';
       $element_types['company']='3';
       
       reset($element_types);
       while( list($key_type, $value_type)=each($element_types) ) {
       
           $parameters=array();
           $parameters['type']=strVal($key_type);
    
           $notes_array_tmp=get_notes_info($parameters, $http_requester);
           
           if( is_array($notes_array_tmp) ) $notes_array=array_merge($notes_array, $notes_array_tmp);      
       }
       
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
              
              if( !(intVal($value['date_create'])>=$date_create_note_from
                    && intVal($value['date_create'])<=$date_create_note_to) ) {

                 continue;
              }
                 
              $note_is_selected=false;   
              $text_array=json_decode($value['text'], true);
              if( $note_is_selected===false
                  && is_array($text_array)
        	         && array_key_exists('DURATION', $text_array) ) {
              
           	     $value['text_array']=$text_array;
           	     $selected_notes_array[]=$value;
           	     $note_is_selected=true;
           	  }
           	  
           	  if( $note_is_selected===false
           	      && is_array($text_array)
           	      && array_key_exists('LINK', $text_array) ) {
           	        
        	        $value['text_array']=$text_array;
        	        $selected_notes_array[]=$value;
        	        $note_is_selected=true;
           	  }           	  
    	  
          }    
       }
       
       if( count($selected_notes_array)>0
           && $write_log_cron===true ) {
    
          write_log('Selected '.count($selected_notes_array).' records ', $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);
       }
       
       // Get full record path       
       $elements_number=count($selected_notes_array);
       for( $i=0; $i<$elements_number; $i++ ) {
       
          $value=$selected_notes_array[$i];
          
          $_link_record='';
          $_uniqueid_record='';
          if( is_array($value)
              && array_key_exists('text_array', $value)
              && is_array($value['text_array'])
              && array_key_exists('LINK', $value['text_array']) ) {
                                
              if( array_key_exists('UNIQ', $value['text_array'])
                  && is_string($value['text_array']['UNIQ']) ) $_uniqueid_record=$value['text_array']['UNIQ'];
          }
          
          $_dir_records_array=array();
          $_url_records_left_array=array();
          
          if( is_array($dir_records) ) {
             
             if( count($dir_records)>0 ) {
               $_dir_records_array=$dir_records;
             }
             
             if( is_array($url_records)
                 && count($url_records)>0 ) {
 
               $_url_records_left_array=$url_records;
             }
             else {
               write_log('Value of url_records is not array in settings, but dir_records is array. Set array for url_records! ', $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);
             }
             
          }
          else {
             $_dir_records_array[]=$dir_records;
             $_url_records_left_array[]=$url_records;                  
          }
          
          
          $_full_record_path='';
          $_dir_index=0;
          $break_cycle=false;
          
          reset($_dir_records_array);
          while( list($dir_key, $dir_value)=each($_dir_records_array) ) {
          
             $_dir_records=$dir_value;         
             
             $_dir_records=rtrim($_dir_records, '/');
             $_dir_records=( strlen($_dir_records)>0 ? $_dir_records.'/': '');
             
             if( strlen($_dir_records)>0
                 && strlen($_uniqueid_record)>=10
                 && is_numeric($_uniqueid_record)
                 && intVal($_uniqueid_record)>1000000000
                 && intVal($_uniqueid_record)<5000000000 ) {
                 
                 $date_suffix= date('Y/m/d/', intVal($_uniqueid_record));       
                 $selected_records=select_files($_dir_records.$date_suffix, $_uniqueid_record, 10, 1);
                 if( is_array($selected_records)
                     && count($selected_records)>0 ) {
                 
                     reset($selected_records);
                     while( list($key, $value)=each($selected_records) ) {
                        $_full_record_path=$key;
                        $_dir_index=$dir_key;
                        
                        if( $write_log_cron===true ) { 
   	      	             write_log('record from uniqueid: '.$_full_record_path, $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);
                        }
                        
                        $break_cycle=true;
                        break;
                     }
                 }
                 
             }
             
             if( $break_cycle===true ) break;
          
          }
          
          $selected_notes_array[$i]['record_path']=$_full_record_path;
          $selected_notes_array[$i]['record_dir_index']=$_dir_index;
       }
    
       if( $write_log_cron===true ) {   
       	write_log('Record paths are set ', $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);   
       }
       
       $elements_number=count($selected_notes_array);
       for( $i=0; $i<$elements_number; $i++ ) {
       
          $value=$selected_notes_array[$i];
          
          // Calculate call duration
          $_full_record_path='';
          if( is_array($value)
              && array_key_exists('record_path', $value)
              && is_string($value['record_path']) ) {
              
              $_full_record_path=$value['record_path'];
          }
          
          $_record_dir_index=0;
          if( is_array($value)
              && array_key_exists('record_dir_index', $value)
              && is_numeric($value['record_dir_index']) ) {
              
              $_record_dir_index=$value['record_dir_index'];
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
          
          if( is_array($value)
              && array_key_exists('text_array', $value)
              && is_array($value['text_array'])
              && array_key_exists('DURATION', $value['text_array'])
              && is_numeric($value['text_array']['DURATION']) ) {
                  
              $selected_notes_array[$i]['record_duration']=round($_record_duration, 0);
          }
          
       }
      
       if( $write_log_cron===true ) { 
            write_log('Durations are set ', $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);
       }
    
       // Select records with non-zero duration
       $update_records=array();
       reset($selected_notes_array);
       while( list($key, $value)=each($selected_notes_array) ) {
           
           if( (array_key_exists('record_duration', $value)
                && is_numeric($value['record_duration'])
                && $value['record_duration']>0) ) {
                   
               $update_records[]=$value;
           }
                     
       }
       
       // Update record duration
       $status=true;
       
       $element_types=array();
       $element_types['contact']='1';
       $element_types['company']='3';
       
       $counter_update_records=0;
       
       reset($element_types);
       while( list($key_type, $value_type)=each($element_types) ) {
      
           $parameters=array();
           $parameters['type']=strVal($key_type);
           $parameters['id']=array();
           
           $updated_fields=array();
           
           reset($update_records);
           while( list($key, $value)=each($update_records) ) {
               
                if( strVal($value['element_type'])!==strVal($value_type) ) continue;
                
                $update_duration=false;
                $update_link=false;
                
                $text_json=$value['text'];
                $text_array=json_decode($text_json, true);
                
                if( array_key_exists('record_duration', $value)
                    && is_numeric($value['record_duration'])
                    && intVal($value['record_duration'])>0 ) {
                        
                    $text_array['DURATION']=$value['record_duration'];
                    $update_duration=true;
                }
    
                $text_json=json_encode($text_array);
        
                $note_data=array("text"=>$text_json);
                $updated_fields[intVal($value['note_id'])]=$note_data;
                
                if( $update_duration===true ) {
                
                    $parameters['id'][]=$value['note_id'];
                    $counter_update_records+=1;
                }
           }
           
           if( array_key_exists('id', $parameters)
               && count($parameters['id'])>0 ) {
               
               $status_tmp=update_notes_info($parameters, $updated_fields, $http_requester);
               $status=($status && ($status_tmp===true));
           }
             
       }
      
       // close connection to db
       if( isset($db_conn) ) {
           $db_conn->close();
       }
       
       $update_status_text='Finish: duration of record is updated, number updated records='.strVal($counter_update_records);
       if( $status===false ) {
           $update_status_text='Finish: update of record duration is failed ';      
       }
              
       if( $write_log_cron===true ) {
       	write_log($update_status_text, $amocrm_log_file, 'UPDATE DURATION '.$_REQUEST['param_login']);
       }
       
   }
   
   if( $write_log_cron===true ) {
       write_log('Finish', $amocrm_log_file, 'CRON_TASK '.$_REQUEST['param_login']);
   }
   
   // Select function for search records
   function select_function($select_function_type, $file_path, $file_attributes) {
       
       $function_result=false;
       
       if( strlen($select_function_type)>0
           && $file_attributes['directory']===false
           && strpos($file_attributes['name'], $select_function_type)!==false ) {
               
           $function_result=true;
       }
       
       return($function_result);
   }
   
?>
