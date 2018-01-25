<?php

   require_once('5c_std_lib.php');
   require_once('5c_files_lib.php');
   require_once('5c_database_lib.php');
   require_once('amocrm_settings.php');

   date_default_timezone_set('Etc/GMT-3');    
   
   $result='';
   
   @$user_id=$_REQUEST['param_user_id'];
   
   if( !isset($user_id) ) {
      $user_id='';
   }

   //write_log('blank_line', $amocrm_log_file, 'QUERY');   
   //write_log($_REQUEST, $amocrm_log_file, 'QUERY');   
   
   if( strlen($user_id)===0 ) exit($result);
      
   // Check active calls
   $current_time=time();

   $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
   if( $db_conn===false ) {
      write_log('Connection to database is failed', $amocrm_log_file, 'QUERY');
      exit($result);    
   } 

   $selected_uniqueid=null;
   $selected_user_id=null;
   if( $db_conn!==false ) {
      
      $query_text="";      
      $query_text.='use &amocrm_database_name&;';
      $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);      
      $db_status=$db_conn->query($query_text);
      
      $query_text="";      
      $query_text.="SET NAMES 'utf8';";
      $db_status=$db_conn->query($query_text);
      
      $query_text="";
      $query_text.=" select ";
      $query_text.=  " calls.uniqueid, ";
      $query_text.=  " calls.user_id, ";     
      $query_text.=  " calls.client_phone, ";
      $query_text.=  " calls.user_name, ";      
      $query_text.=  " calls.lead_id, ";
      $query_text.=  " calls.file_path, ";
      $query_text.=  " calls.client_name, ";      
      $query_text.=  " calls.new_client, ";      
      $query_text.=  " calls.new_lead, ";
      $query_text.=  " calls.outcoming, ";
      $query_text.=  " calls.missed ";
      $query_text.=" from ";
      $query_text.= " calls as calls ";
      $query_text.=" where ";
      $query_text.= " calls.date>='&date_start&' and calls.user_id='&user_id&'";
      $query_text.=" order by ";
      $query_text.= " calls.date desc ";
      $query_text.=" limit 1; ";
      
      $start_date_time=date('Y-m-d H:i:s', $current_time-5*60);
      $query_text=set_parameter('date_start', $start_date_time, $query_text);
      
      $query_text=set_parameter('user_id', $user_id, $query_text);
      
      $db_result=$db_conn->query($query_text);
      if( $db_result===false ) {
          
         $result_message=$db_result->error;
         write_log('Request to database is failed: '.$result_message, $amocrm_log_file, 'QUERY');
         
      }
      else {
	 

    	 $result_row_array=array();
    	 while($row = $db_result->fetch_assoc()) {
    	     
    	    $result_row_array["client_phone"]=strVal($row["client_phone"]);
    	    $result_row_array["user_name"]=strVal($row["user_name"]);
    	    $result_row_array["lead_id"]=strVal($row["lead_id"]);
    	    $result_row_array["record_link"]=strVal($row["file_path"]);
    	    $result_row_array["client_name"]=strVal($row["client_name"]);
    	    $result_row_array["new_client"]=strVal($row["new_client"]);
    	    $result_row_array["new_lead"]=strVal($row["new_lead"]);
    	    $result_row_array["outcoming"]=strVal($row["outcoming"]);
    	    
    	    $selected_uniqueid=strVal($row["uniqueid"]);
    	    $selected_user_id=strVal($row["user_id"]);	    
    	    
            $result_row_array["from"]='';
            $result_row_array["to"]='';
            if( strVal($row["outcoming"])==='1' ) {
               $result_row_array["from"].=$result_row_array["user_name"];
    
               $result_row_array["to"].=$result_row_array["client_name"];
    
               $client_name_from_request=remove_symbols($result_row_array["client_name"]);
               if( strlen($client_name_from_request)<10 ) $result_row_array["to"].=' ('.$result_row_array["client_phone"].')';
            }
            else {
               $result_row_array["from"].=$result_row_array["client_name"];
    
               $client_name_from_request=remove_symbols($result_row_array["client_name"]);
               if( strlen($client_name_from_request)<10 ) $result_row_array["from"].=' ('.$result_row_array["client_phone"].')';
    
               if( strVal($row["missed"])==='1' ) {
                   $result_row_array["to"]='пропущен';
               }
               else {
                   $result_row_array["to"].=$result_row_array["user_name"];
               }
            }
    
            $client_status_str='';
            $new_client_str=', перв.';
            if( strlen($result_row_array["new_client"])===1
                && strVal($result_row_array["new_client"])==='0' ) {
    
               $new_client_str=', повт.';
            }
    
            $client_status_str.=$new_client_str;
    
            $new_lead_str=', новая сделка';
            if( strlen($result_row_array["new_lead"])===1
                && strVal($result_row_array["new_lead"])==='0' ) {
    
               $new_lead_str=', продолж. перег.';
            }
    
            $client_status_str.=$new_lead_str;
    
            $result_row_array["to"].=$client_status_str;
            
    	    break;
    	 }
    	 
    	 $result=json_encode($result_row_array);    
	 
      }
      
   }
  
   if( count($result_row_array)>0 ) {
       
       write_log('Result='.$result, $amocrm_log_file, 'QUERY');
       
       // try lock amocrm
       $lock_priority=10;
       $min_time_from_last_lock_sec=0;
       if( $amocrm_sleep_time_after_request_microsec>0 ) $min_time_from_last_lock_sec=$amocrm_sleep_time_after_request_microsec/1000000;
       
       $db_conn=new mysqli($amocrm_database_host, $amocrm_database_user, $amocrm_database_password, $amocrm_database_name);
       
       $lock_status=false;
       if( isset($db_conn) ) {
           $lock_status=lock_database($db_conn, '', $min_time_from_last_lock_sec, 0.01, 10, $lock_priority, 1, $min_time_from_last_lock_sec);
           
           if( $lock_status===true ) {
               
               unlock_database($db_conn, '');
               
               // remove record
               if( !is_null($selected_uniqueid)
                   && !is_null($selected_user_id) ) {
                       
                   $query_text="";
                   $query_text.=" delete from calls ";
                   $query_text.=" where ";
                   $query_text.=" calls.uniqueid='&uniqueid&' and calls.user_id='&user_id&' ";
                   
                   $query_text=set_parameter('uniqueid', $selected_uniqueid, $query_text);
                   $query_text=set_parameter('user_id', $selected_user_id, $query_text);
                   
                   $db_status=$db_conn->query($query_text);
               }
                              
           }

       }
       
       if( $lock_status===false ) $result=json_encode(array());

   }

   if( isset($db_conn) ) {
       $db_conn->close();
   }
   
   echo $result;


function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}   

?>