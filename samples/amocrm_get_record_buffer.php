<?php

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
  
   require_once('5c_files_lib.php');
   require_once('5c_http_lib.php');
   require_once('5c_std_lib.php');
   
   @$linkedid=$_REQUEST['id'];
   
   if( !isset($linkedid) ) $linkedid='';
   
   $result='';
   
   if( strlen($linkedid)===0 ) {
      write_log('id is not found ', $amocrm_log_file, 'GET_REC_B');
      exit($result);
   }
   
   // get data records from database
   $files_array=array();
   
   $db_conn=new mysqli($crm_linkedid_host, $crm_linkedid_user, $crm_linkedid_password, $crm_linkedid_database_name);
   
   if ( strlen($db_conn->connect_error)>0 ) {
      $result_message=$db_conn->connect_error;
      write_log('Connection to database is failed: '.$result_message, $amocrm_log_file, 'GET_REC_B');     
   }   

   $query_text="";
   $query_text.="use &database_name&;";
   
   template_set_parameter('database_name', $crm_linkedid_database_name, $query_text);
   $query_status=$db_conn->query($query_text);
   if( $query_status===false ) {
      write_log('Cannot select database: '.mysql_error($db_conn), $amocrm_log_file, 'GET_REC_B');
      return($result);      
   }
	 
   $query_text="";
   $query_text.="select filename from cdr where &select_condition& order by uniqueid asc;";
   
   $select_condition="linkedid='".$linkedid."' and LENGTH(coalesce(filename, ''))>0 ";
   template_set_parameter('select_condition', $select_condition, $query_text);
   template_set_parameter('table', 'cdr', $query_text); 
   
   $query_result=$db_conn->query($query_text);        
   if( $query_result!==false ) {
     
      while ($row = $query_result->fetch_assoc()) {
         $files_array[]=$row['filename']; 
      } 
 
   }
   else {
     
      write_log('Cannot select uniqueid from database ', $amocrm_log_file, 'GET_REC_B');

   }
   
   $uniqueid_array=array();
   if( count($files_array)>0 ) {
      
      reset($files_array);
      while( list($key, $value)=each($files_array) ) {
         $uniqueid=get_uniqueid($value);
         
         if( strlen($uniqueid)>=10 ) {
            $uniqueid_array[$value]=$uniqueid;
         }
      }
      
   }
   
   $url='http://127.0.0.1:80/amocrm/amocrm_get_record.php';
   $tmp_files_dir='/var/tmp/';
   
   $headers=array();
   $headers[]='Content-Type:application/x-www-form-urlencoded; charset=utf-8';
   
   if( count($uniqueid_array)>0 ) {
      
      $files_concat_array=array();
      $files_delete_array=array();
      
      $cycle_index=0;
      reset($uniqueid_array);
      while( list($key, $value)=each($uniqueid_array) ) {
         
         $parameters['id']=$value;
         $parameters['infotype']='file';
         $parameters['index']='0';
         
         if( array_key_exists('param_login', $_REQUEST) ) {
            $parameters['param_login']=$_REQUEST['param_login'];
         }
         
         $return_headers=array();
         $record_file=request_GET($url, $parameters, $amocrm_log_file, null, $headers, 60, $return_headers);
         
         if( count($uniqueid_array)===1 ) {
            
            while(list($key_2, $value_2)=each($return_headers)) {
               header($value_2);
            }   
            
            $result=$record_file;
         }
         else {
            
            $file_wav=false;
            while(list($key_2, $value_2)=each($return_headers)) {
               if( strpos(strtolower($value_2), 'content-type')!==false
                   && strpos(strtolower($value_2), 'wav')!==false ) {
                   
                   $file_wav=true;    
               }
            }
                        
            
            $filename=$tmp_files_dir.$key;
            
            if(file_exists($filename)) unlink($filename);
            
   			$handle = fopen($filename, "w+");
 
            $write_status = fwrite($handle, $record_file);
            fflush($handle);
            if($write_status!==false) {
               $files_delete_array[]=$filename;
               
               if( $file_wav===false ) {              
                  $files_concat_array[]=$filename;              
               }
               else {
                  
                  $filename_mp3=$filename;
                  if( strlen($filename_mp3)>3
                      && strtolower( substr($filename_mp3, -3) )==='wav' ) {
                         
                      $filename_mp3=substr($filename_mp3, 0, strlen($filename_mp3)-3).'mp3';   
                  }
                  else {
                      $filename_mp3.='.mp3';
                  }
                  
                  // convert to mp3
                  $shell_command='lame --cbr -b 32k '.$filename.' '.$filename_mp3.' 2>/dev/null';
                  $return_shell=exec_shell_command($shell_command, $amocrm_log_file, 'LAME_CONV');
                  if( $return_shell===0 ) {
                     $files_concat_array[]=$filename_mp3;
                     $files_delete_array[]=$filename_mp3;
                  }
                  
               }
            }
            
            fclose($handle);         
            
         }        
         
         $cycle_index+=1;
      }
   
      if( count($files_concat_array)>1 ) {
         $shell_command='cat ';
         
         reset($files_concat_array);
         while(list($key, $value)=each($files_concat_array)) {
            if( filesize($value)>0 ) {
               $shell_command.=$value.' ';
            }
         }
         
         $filename=$filename.'_concatenated.mp3';
         $shell_command.=' | lame --mp3input --tt "record" --tl "record" --ta "record" --cbr -b 32k - '.$filename;
         
         $return_shell=exec_shell_command($shell_command, $amocrm_log_file, 'LAME_CONV');
         if( $return_shell===0 ) {
             $handle = fopen($filename, "rb");
             rewind($handle);
             
             $result = fread($handle, filesize($filename));
             fclose($handle);
             
             $file_size=filesize($filename);
             
             header('Content-type: audio/mpeg');
             header('Accept-Ranges: bytes');
             header('Content-Length: '.sprintf("%d", $file_size));
             
             $files_delete_array[]=$filename;
         }
      }
      
      if( count($files_delete_array)>0 ) {
         
         reset($files_delete_array);
         while(list($key, $value)=each($files_delete_array)) {        
            unlink($value);
         }   
      
      }
      
   }   
   
   echo $result;
   

function get_uniqueid($filename) {
    
    $result='';
    $filename_len=strlen($filename);
    
    if( $filename_len==0 ) retun($result);
    
    $start_index=-1;
    $first_point=-1;
    for( $i=0; $i<$filename_len-10; $i++) {
		if( $filename[$i]=='1' && $filename[$i+10]=='.' ) {
			$start_index=$i;
			$first_point=$i+10;
			break;
		}
    }
        
    if( $start_index<0 ) return($result);
    
    $file_uniqueid=substr($filename, $start_index, 10);
    $uniqueid_len=strlen($file_uniqueid);
    for( $i=0; $i<$uniqueid_len; $i++ ) {
		if( !ctype_digit( $file_uniqueid[$i] ) ) return($result);   
    }   
    
    $second_point=strpos($filename, '.', $first_point+1);
    if( $second_point!==false && ($second_point-$first_point)<10 ) {
    
        for( $i=$first_point+1; $i<$second_point; $i++ ) {
			$ext_uniqueid=substr($filename, $i, 1);
	    
			if( $i==$first_point+1 ) $file_uniqueid.='.';
	    
			if( ctype_digit( $ext_uniqueid ) ) {
				$file_uniqueid.=$ext_uniqueid;	    
			}
			else {
				break;
			}
	    
		}    
    }
        
    $result=$file_uniqueid;
    
    return($result);
}   
   
?>
