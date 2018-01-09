<?php

  // version 09.01.2018

  require_once('5c_files_lib.php');  
  require_once('5c_std_lib.php');


function mysql_delete_rows($db_conn, $database_name, $table, $select_condition, $log_file='') {

   $result=false;

   if( $db_conn===false ) {
      write_log('Connection to database is not valid ', $log_file, 'MYSQL_DELETE');
      return($result);
   }
   
   if( strlen(Trim($select_condition))===0 ) {
      write_log('Select condition is not valid ', $log_file, 'MYSQL_DELETE');
      return($result);      
   }   
   
   
   $query_text="";
   $query_text.="use &database_name&;";
   
   template_set_parameter('database_name', $database_name, $query_text);
   
   $db_status=mysql_query($query_text, $db_conn);
   if( $db_status===false ) {
      write_log('Cannot select database: '.mysql_error($db_conn), $log_file, 'MYSQL_DELETE');
      return($result);      
   }
	 
   $query_text="";
   $query_text.="delete from &table& where &select_condition&;";
   
   template_set_parameter('select_condition', $select_condition, $query_text);
   template_set_parameter('table', $table, $query_text); 
   
   $db_status=mysql_query($query_text, $db_conn);
   if( $db_status===false ) {
      write_log('Cannot delete records from database: '.mysql_error($db_conn), $log_file, 'MYSQL_DELETE'); 
      return($result);
   }
   
   $result=true;
   return($result);

}


function lock_database($db_conn, $log_file='', $min_time_from_last_lock_sec=0.5,
    $time_interval_between_lock_tries_sec=0.1, $max_wait_time_for_lock_sec=10, $priority=0) {
        
        $result=false;

        $write_log_messages=false;
        if( strlen($log_file)>0 ) $write_log_messages=true;
        
        $pid=getmypid();
        
        if( $write_log_messages===true ) {
            write_log('blank_line', $log_file, 'LOCK_AMOCRM '.strVal($pid));
        }
        
        if( !is_object($db_conn) ) {
            
            if( $write_log_messages===true ) {    
                write_log('lock_amocrm_database: connection is not an object! ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
            }
            
        }
        
        $start_time=microtime(true);
        if( $write_log_messages===true ) {
            write_log('lock_amocrm_database: start '.strVal($start_time), $log_file, 'LOCK_AMOCRM '.strVal($pid));
        }
        
        $query_text_enter_queue="insert into &table_name& (pid, time, priority) values(&pid&, &time&, &priority&)";
        $query_text_enter_queue=template_set_parameter('table_name', 'queue', $query_text_enter_queue);
        $query_text_enter_queue=template_set_parameter('pid', sprintf('%d', $pid), $query_text_enter_queue);
        $query_text_enter_queue=template_set_parameter('time', sprintf('%.6f', $start_time), $query_text_enter_queue);
        $query_text_enter_queue=template_set_parameter('priority', sprintf('%d', $priority), $query_text_enter_queue);
        
        $query_result=$db_conn->query($query_text_enter_queue);
        
        if( $query_result===false ) {
            
            if( $write_log_messages===true ) {
                write_log('lock_amocrm_database: cannot insert into queue! ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
            }
            
            return($result);
        }
        
        $query_text_select_from_queue="select COUNT(*) as num from ";
        $query_text_select_from_queue.=" (select pid, time from &table_name& order by priority asc, time asc limit 1) as common";
        $query_text_select_from_queue.=" where common.pid=&pid& and common.time=&time& ";
        $query_text_select_from_queue=template_set_parameter('table_name', 'queue', $query_text_select_from_queue);
        $query_text_select_from_queue=template_set_parameter('pid', sprintf('%d', $pid), $query_text_select_from_queue);
        $query_text_select_from_queue=template_set_parameter('time', sprintf('%.6f', $start_time), $query_text_select_from_queue);
        
        $query_text_remove_from_queue="delete from &table_name& where pid=&pid& and time=&time&";
        $query_text_remove_from_queue=template_set_parameter('table_name', 'queue', $query_text_remove_from_queue);
        $query_text_remove_from_queue=template_set_parameter('pid', sprintf('%d', $pid), $query_text_remove_from_queue);
        $query_text_remove_from_queue=template_set_parameter('time', sprintf('%.6f', $start_time), $query_text_remove_from_queue);
        
        $query_text_get_time="select time from &table_name& where id=1";
        $query_text_get_time=template_set_parameter('table_name', 'locks', $query_text_get_time);
        
        $query_text_lock="insert into &table_name& (id, time) values(2, &current_time&)";
        $query_text_lock=template_set_parameter('table_name', 'locks', $query_text_lock);
        
        $query_text_set_time="replace into &table_name& (id, time) values(1, &current_time&)";
        $query_text_set_time=template_set_parameter('table_name', 'locks', $query_text_set_time);
        
        
        $top_in_queue=false;
        $lock_is_possible=false;
        $cycle_count=0;
        
        while(true) {
            
            $cycle_count+=1;
            $current_time=microtime(true);
            
            if( $cycle_count>1000 ) {
                
                if( $write_log_messages===true ) {
                    write_log('lock_amocrm_database: number of cycles exceeded maximum ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
                }
                
                break;
            }
            
            if( ($current_time-$start_time)>$max_wait_time_for_lock_sec ) {
                            
                if( $write_log_messages===true ) {
                    write_log('lock_amocrm_database: maximum time for lock is exceeded ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
                }
                
                break;
            }
            
            if( $top_in_queue===false ) {
                
                $query_result=$db_conn->query($query_text_select_from_queue);
                
                if( $query_result!==false ) {
                    
                    $row = $query_result->fetch_assoc();
                    if( is_array($row)
                        && array_key_exists('num', $row)
                        && intVal($row['num'])===0 ) {
                            
                            usleep($time_interval_between_lock_tries_sec*1000000);
                            continue;
                        }
                        
                        $top_in_queue=true;
                        
                        if( $write_log_messages===true ) {
                            write_log('lock_amocrm_database: process in the top of queue ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
                        }
                        
                }
                else {
                    break;
                }
            }
            
            $current_time=microtime(true);
            if( $lock_is_possible===false ) {
                
                $query_result=$db_conn->query($query_text_get_time);
                if( $query_result!==false ) {
                    
                    $row = $query_result->fetch_assoc();
                    if( is_array($row)
                        && array_key_exists('time', $row)
                        && floatVal($row['time'])>=($current_time-$min_time_from_last_lock_sec)) {
                            
                            
                            usleep($time_interval_between_lock_tries_sec*1000000);
                            continue;
                        }
                        
                        $lock_is_possible=true;
                        
                        if( $write_log_messages===true ) {
                            write_log('lock_amocrm_database: lock is possible ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
                        }
                        
                }
                else {
                    break;
                }
                
            }
            
            if( $top_in_queue===true
                && $lock_is_possible===true ) {
                    
                    $current_time=microtime(true);
                    $query_text_lock=template_set_parameter('current_time', sprintf('%.6f', $current_time), $query_text_lock);
                    
                    $query_status=$db_conn->query($query_text_lock);
                    if( $query_status===true ) {
                                                
                        $query_text_set_time=template_set_parameter('current_time', sprintf('%.6f', $current_time), $query_text_set_time);
                        $query_status=$db_conn->query($query_text_set_time);
                        $result=$query_status;
                        
                        if( $write_log_messages===true ) {
                            write_log('lock_amocrm_database: lock is successful ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
                        }
                        
                        break;
                        
                    }
                    else {
                        usleep($time_interval_between_lock_tries_sec*1000000);
                    }
                    
                }
                
        }
        
        // remove from queue
        $query_status=$db_conn->query($query_text_remove_from_queue);
        if( $query_status===true ) {
            
            if( $write_log_messages===true ) {
                write_log('lock_amocrm_database: process removed from queue ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
            }
            
        }
                
        return($result);
        
}


function unlock_database($db_conn, $log_file='') {
    
    $result=false;

    $pid=getmypid();
    
    $write_log_messages=false;
    if( strlen($log_file)>0 ) $write_log_messages=true;
    
    if( !is_object($db_conn) ) {
        
        if( $write_log_messages===true ) {
            write_log('unlock_amocrm_database: connection is not an object! ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
        }
        
    }
    
    $query_text_unlock="delete from &table_name& where id=2";
    $query_text_unlock=template_set_parameter('table_name', 'locks', $query_text_unlock);
    
    $query_status=$db_conn->query($query_text_unlock);
    $result=$query_status;
    
    if( $query_status===true ) {
        
        if( $write_log_messages===true ) {
            write_log('unlock_amocrm_database: unlock is successful ', $log_file, 'LOCK_AMOCRM '.strVal($pid));
        }
        
    }
    
    return($result);
    
}

?>  