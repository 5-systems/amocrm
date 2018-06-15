<?php

// version 24.05.2018
include_once('5c_std_lib.php');

date_default_timezone_set('Etc/GMT-3');
ini_set("default_socket_timeout", 600);

// Define select finction in your file
//function select_function($select_function_type, $file_path, $file_attributes) {
//	return(true);
//} 


function select_files($search_dir, $select_function_type="", $maximum_recursion_level=10, $maximum_number_selected_files=10000) {

   $recursion_level=0;
   $number_selected_files=0;
   $selected_files=Array();
   
   $dir_path=$search_dir;
   if( is_string($search_dir) ) {
		$dir_path=Array();
		$dir_path[]=$search_dir;
   }
   
   treat_current_path($selected_files, $dir_path, $select_function_type, $recursion_level, $maximum_recursion_level, $number_selected_files, $maximum_number_selected_files);
   
   return($selected_files);
 }

function treat_current_path(&$selected_files, &$dir_path, $select_function_type, $recursion_level, $maximum_recursion_level, &$counter=NULL, &$maximum_number_selected_files) {

	if( count($dir_path)==0 ) {
		return;
	}
	
	$recursion_level+=1;
	
	if( $recursion_level>$maximum_recursion_level ) {
		return;
	}

	foreach($dir_path as $cur_dir) {

		$internal_dir_path=Array();
		
		if ( strlen($cur_dir)>0 && substr($cur_dir, -1)!=='/' && substr($cur_dir, -1)!=='\\' ) {
			$cur_dir.='/';
		}
	
		@ $hdir=opendir($cur_dir);
		if( !$hdir ) {
			continue;
		}
			
		while (false !== ($cur_file = readdir($hdir))) {
			
			$file_attributes=stat($cur_dir.$cur_file);
			if( $file_attributes===false ) {
				$file_attributes=Array();
			}
			
			$file_exten='';
			$file_exten_pos=strrpos($cur_file, '.');
			if( $file_exten_pos!==false && $file_exten_pos<(strlen($cur_file)-1) ) {
			    $file_exten=substr($cur_file, $file_exten_pos+1);
			}
			
			$file_attributes['name']=$cur_file;
			$file_attributes['dir']=$cur_dir;
			$file_attributes['exten']=$file_exten;
			
			if( is_dir($cur_dir.$cur_file) && substr($cur_file, 0, 1)!=='.' ) {
				$internal_dir_path[]=$cur_dir.$cur_file;
				$file_attributes['directory']=true;
			}
			elseif( is_file($cur_dir.$cur_file) ) {
				$file_attributes['directory']=false;
			}
			else {
				continue;
			}
			
			if( select_function($select_function_type, $cur_dir.$cur_file, $file_attributes) ) {

				if( count($selected_files)>=$maximum_number_selected_files ) {
					return;
				}

				$selected_files[$cur_dir.$cur_file]=$file_attributes;
			}			
					
		}
				
		sort($internal_dir_path);
		foreach( $internal_dir_path as $cur_dir_path ) {
			$subdirectory=Array();
			$subdirectory[]=$cur_dir_path;
			treat_current_path($selected_files, $subdirectory, $select_function_type, $recursion_level, $maximum_recursion_level, $counter, $maximum_number_selected_files);
		}
				
	}
	
	return;
}

function write_log($message, $log_path, $prefix='') {

	$function_result=false;
	
	if( strlen($log_path)===0 ) return($function_result);
	
	if( is_string($message) ) {
		$message_tmp=$message;
		$message=Array();
		$message['log']=$message_tmp;
	}
	
	@ $fp = fopen($log_path, 'a');
	if ($fp) {

		if( count($message)===1 && array_key_exists('log', $message) && $message['log']==='blank_line' ) {
			$message_text='';
		}
		else{
			$message_text='';
			if( strlen($prefix)>0 ) $message_text.=$prefix.' ';
			
			$message_text.=date('Ymd H:i:s').':';
			foreach($message as $current_parameter => $current_value) {
			    $message_text.=' '.$current_parameter.': '.print_r($current_value, true);
			}
		}

		if( function_exists('html_to_utf8') ) $message_text=html_to_utf8($message_text);		
		
		$message_text.=PHP_EOL;
		
		fwrite($fp, $message_text);
		fflush($fp);
		fclose($fp); 			
	}
}

function action_copy($files_to_copy, $dir_destination, $log_file) {
	
	$function_result=true;

	if( substr($dir_destination, -1, 1)!=='/' && substr($dir_destination, -1, 1)!=='\\' ) {
		$dir_destination.='/';
	}
	
	reset($files_to_copy);
	foreach($files_to_copy as $cur_file=>$cur_file_attributes) {
	
		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='start copy...';
			
			$message['copy '.$cur_file.' to '.$dir_destination]=$operation_status;
			write_log($message, $log_file);
		}	
	
		$copy_status=copy($cur_file, $dir_destination.$cur_file_attributes['name']);
		if( $copy_status===false ) {
			$function_result=false;
		}

		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='';
			if( $function_result ) {
				$operation_status='success';
			}
			else {
				$exception_array=error_get_last();
				$exception_message=$exception_array['message'];
				$operation_status='failed '.Trim($exception_message);			
			}
			
			$message['copy '.$cur_file.' to '.$dir_destination]=$operation_status;
			write_log($message, $log_file);
		}
		
	}
	
	return($function_result);
}

function action_delete($files_to_delete, $log_file='') {

	$function_result=true;
	if( is_string($files_to_delete) ) {
		$file_tmp=$files_to_delete;
		$files_to_delete=Array();
		$files_to_delete[]=$file_tmp;
	}
	
	reset($files_to_delete);
	foreach($files_to_delete as $cur_file=>$cur_file_attributes) {
	
		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='start delete...';
			
			$message['delete '.$cur_file]=$operation_status;
			write_log($message, $log_file);
		}
		
		$unlink_status=unlink($cur_file);
		if( $unlink_status===false ) {
			$function_result=false;
		}
		
		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='';
			if( $function_result ) {
				$operation_status='success';
			}
			else {
				$exception_array=error_get_last();
				$exception_message=$exception_array['message'];
				$operation_status='failed '.Trim($exception_message);			
			}
			
			$message['delete '.$cur_file]=$operation_status;
			write_log($message, $log_file);
		}
		
	}

	return($function_result);	
}

function action_mkdir($full_dir_name, $log_file, $mode='0777', $recursive=false) {
	$function_result=true;

	if( strlen($log_file)===0 ) {
		return($function_result);
	}
	
	if( substr($full_dir_name, -1, 1)!=='/' && substr($full_dir_name, -1, 1)!=='\\' ) {
		$full_dir_name.='/';
	}

	if( strlen($log_file)>0 ) {
	
		$message=Array();
		
		$operation_status='start make dir...';
		
		$message['mkdir '.$full_dir_name]=$operation_status;
		write_log($message, $log_file);
	}
	
	$function_result=mkdir($full_dir_name, $mode, $recursive);
	if( strlen($log_file)>0 ) {
	
		$message=Array();
		
		$operation_status='';
		if( $function_result ) {
			$operation_status='success';
		}
		else {
			$exception_array=error_get_last();
			$exception_message=$exception_array['message'];
			$operation_status='failed '.Trim($exception_message);		
		}
		
		$message['mkdir '.$full_dir_name]=$operation_status;
		write_log($message, $log_file);
	}
	
	return($function_result);
}

function action_rename($files_to_copy, $dir_destination, $log_file) {
	
	$function_result=true;

	if( substr($dir_destination, -1, 1)!=='/' && substr($dir_destination, -1, 1)!=='\\' ) {
		$dir_destination.='/';
	}
	
	reset($files_to_copy);
	foreach($files_to_copy as $cur_file=>$cur_file_attributes) {
	
		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='start move...';
			
			$message['move '.$cur_file.' to '.$dir_destination]=$operation_status;
			write_log($message, $log_file);
		}	
	
		$copy_status=rename($cur_file, $dir_destination.$cur_file_attributes['name']);
		if( $copy_status===false ) {
			$function_result=false;
		}

		if( strlen($log_file)>0 ) {
		
			$message=Array();
			
			$operation_status='';
			if( $function_result ) {
				$operation_status='success';
			}
			else {
				$exception_array=error_get_last();
				$exception_message=$exception_array['message'];
				$operation_status='failed '.Trim($exception_message);			
			}
			
			$message['move '.$cur_file.' to '.$dir_destination]=$operation_status;
			write_log($message, $log_file);
		}
		
	}
	
	return($function_result);
	
}

function action_ftp_put($files_to_copy, $dir_destination, $log_file, $mode="FTP_BINARY", $passive_mode=true, $ftp_timeout=600) {
	
	$function_result=true;

	if( substr($dir_destination, -1, 1)!=='/' && substr($dir_destination, -1, 1)!=='\\' ) {
		$dir_destination.='/';
	}
	
	// Setup ftp-connection
	$destination_params=parse_url($dir_destination);
	if( $destination_params===false ) {
		$function_result=false;	
	}
	
	$ftp_connection=Null;
	if( $function_result ) {
	
		$ftp_port=21;
		if( array_key_exists('port', $destination_params) ) {
			$ftp_port=$destination_params['port'];
		}
		
		$ftp_connection=ftp_connect($destination_params['host'], $ftp_port, $ftp_timeout);
		
		if( $ftp_connection===false ) {
			$function_result=false;
		}	
	}
	
	if( $function_result ) {	
		$ftp_login=ftp_login($ftp_connection, $destination_params['user'], $destination_params['pass']);
		
		if( $ftp_login===false ) {
			$function_result=false;
		}
	}
	
	if( $function_result ) {
		$current_mode=ftp_pasv($ftp_connection, $passive_mode);
		
		if( $current_mode!==$passive_mode ) $function_result=false;
	}
	
	if( strlen($log_file)>0 ) {
		$message=Array();
		
		$operation_status='';
		if( $function_result ) {
			$operation_status='success';
		}
		else {
			$exception_array=error_get_last();
			$exception_message=$exception_array['message'];
			$operation_status='failed '.Trim($exception_message);			
		}
		
		$message['connection to server '.$destination_params['host']]=$operation_status;
		write_log($message, $log_file);
	}	
		
	reset($files_to_copy);

	if( $function_result ) {
	
		foreach($files_to_copy as $cur_file=>$cur_file_attributes) {
		
			if( strlen($log_file)>0 ) {
			
				$message=Array();
				
				$operation_status='start ftp put...';
				
				$message['put '.$cur_file.' to '.$dir_destination]=$operation_status;
				write_log($message, $log_file);
			}	
			
			$put_mode=FTP_BINARY;
			if( $mode==="FTP_ASCII" ) $put_mode=FTP_ASCII;
			
			$put_status=ftp_put($ftp_connection, $destination_params['path'].$cur_file_attributes['name'], $cur_file, $put_mode);
			
			if( $put_status===false ) {
				$function_result=false;
			}

			if( strlen($log_file)>0 ) {
			
				$message=Array();
				
				$operation_status='';
				if( $function_result ) {
					$operation_status='success';
				}
				else {
					$exception_array=error_get_last();
					$exception_message=$exception_array['message'];
					$operation_status='failed '.Trim($exception_message);			
				}
				
				$message['put '.$cur_file.' to '.$dir_destination]=$operation_status;
				write_log($message, $log_file);
			}
			
		}
	
	}
	
	return($function_result);
}

function convert_wav_to_mp3($wav_file, $mp3_file, $log_file='') {

        $output_arr=Array();
        $return_val=0;

	if( strlen($log_file)>0 ) {
		$message=Array();
		$operation_status='start convert...';
			
		$message['convert '.$wav_file]=$operation_status;
		write_log($message, $log_file);
	}

        exec('lame --cbr -b 32k '.$wav_file.' '.$mp3_file.' 2>/dev/null', $output_arr, $return_val);

	if( strlen($log_file)>0 ) {
		
		$message=Array();
			
		$operation_status='';
		if( $return_val===0 ) {
			$operation_status='success';
		}
		else {
			$exception_array=error_get_last();
			$exception_message=$exception_array['message'];
			$operation_status='failed '.Trim($exception_message);			
		}
			
		$message['convert '.$wav_file]=$operation_status;
		write_log($message, $log_file);
	}

        return($return_val);

}

function exec_shell_command($shell_command, $log_file='', $shell_command_presentation='') {

        $output_arr=Array();
        $return_val=0;

        if( strlen($log_file)>0 ) {
                $message=Array();
                $operation_status='start '.$shell_command.'...';
                if( strlen($shell_command_presentation)>0 ) $operation_status='start '.$shell_command_presentation.'...';

                $message['command ']=$operation_status;
                write_log($message, $log_file);
        }

        $loc_shell_command=$shell_command;
        $err_redirect_pos=strpos($loc_shell_command, '2>');
        
        if( $err_redirect_pos===false ) $loc_shell_command.=' 2>&1';
        
        exec($loc_shell_command, $output_arr, $return_val);

        if( strlen($log_file)>0 ) {

                $message=Array();

                $operation_status='';
                if( $return_val===0 ) {
                        $operation_status='success';
                }
                else {
                        $operation_status='failed:';

                        if( is_array($output_arr) ) {
                                reset($output_arr);
                                while(list($key, $value)=each($output_arr)) $operation_status.=' '.$value;
                        }

                }

                $message['command ']=$operation_status;
                write_log($message, $log_file);
        }

        return($return_val);
}

function get_file_info($file_path) {

   $result=array();
   
   // Extension
   $_file_exten='';
   $_file_exten_pos=strrpos($file_path, '.');
   if( $_file_exten_pos!==false && $_file_exten_pos<(strlen($file_path)-1) ) {
	 $_file_exten=substr($file_path, $_file_exten_pos+1);
   }
   
   $result['extension']=$_file_exten;
   
   // Other properties
   $_stat_properties=stat($file_path);
   if( is_array($_stat_properties) ) {
   
      reset($_stat_properties);
      while( list($key, $value)=each($_stat_properties) ) {
	 $result[$key]=$value;     
      }
   
   }

   return($result);
}

function select_files_linux($search_dir, $search_string, $select_function_type="") {

   $result=array();
   
   $output_array=array();
   $command_status=0;
   
   exec("find ".$search_dir." -name '*".$search_string."*' -print", $output_array, $command_status);
   
   if( $command_status===0 ) {
      
      reset($output_array);
      while( list($key, $value)=each($output_array) ) {
         
      	$file_attributes=stat($value);
			if( $file_attributes===false ) {
				$file_attributes=Array();
			}
			
			$path_parts = pathinfo($value);
						
			$file_attributes['name']=$path_parts['basename'];			
			$file_attributes['dir']=$path_parts['dirname'];
			$file_attributes['exten']=$path_parts['extension'];			
			
         if( is_dir($value) && substr($file_attributes['name'], 0, 1)!=='.' ) {
				$file_attributes['directory']=true;
			}
			elseif( is_file($value) ) {
				$file_attributes['directory']=false;
			}
			else {
				continue;
			}			
			
			if( select_function($select_function_type, $value, $file_attributes) ) {
			   $result[$value]=$file_attributes;
			}   
			
      }
      
   }
   
   
   return($result);
}

?>
