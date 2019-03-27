<?php

	require_once('5c_files_lib.php');

//  input parameters (to change)

    // Common
    $dir_path='/callrecord/';
    $time_interval_plus=1;
    $time_interval_minus=0.1;   
    $data_from_database=false;
    
    // Data from file system
    $diff_current_zone_GMT=0;
    $coeff_size_sec_wav=0.00006252;
    $coeff_size_sec_mp3=0.0002493;    
    
    // Data from database and file system
    $conn_dsn='';
    $conn_user='';
    $conn_pass='';
    
    header("Expires: 0");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    date_default_timezone_set ('Etc/GMT-3');

    error_reporting(E_ALL);
    ini_set('display_errors', 1); 
        
    @$uniqueid=$_GET['startparam1'];
    @$caller_number=$_GET['callernumber'];
    @$min_call_duration=$_GET['startparam2'];
    @$info_type=$_GET['infotype'];
    @$record_index=$_GET['startparam3'];

    if( !isset($info_type) ) {
	$info_type='description';
    }    

    if( !isset($record_index) ) {
	$record_index='0';
    }
    
    if( !isset($min_call_duration) ) {
	$min_call_duration='0';
    }


    $uniqueid_point=strpos($uniqueid, '.');
    if( strlen($uniqueid)!==10 && $uniqueid_point!==10 ) {
		echo 'Bad format of uniqueid';
		exit;
    }
    
    $uniqueid=substr($uniqueid, 0, 10);
    $uniqueid_num=(int)$uniqueid;
    $uniqueid_num_low=$uniqueid_num-$time_interval_minus;
    $uniqueid_num_upper=$uniqueid_num+$time_interval_plus;
    
    $uniqueid_low=number_format($uniqueid_num_low, 0, '.', '');
    $uniqueid_upper=number_format($uniqueid_num_upper, 0, '.', '');    

    
//  treat parameters
    $data=Array();
    if( $data_from_database ) {
		$data=get_data_from_database();
    }
    else {
		$data=get_data_from_filesystem();	
    }
    
  
    // Store result
    $recordfiles=Array();
    $result='';
    foreach($data as $cur_line) {
    
		if( is_array($cur_line) ) { 
		
			if( $info_type=='description' ) $result.="<?xml version=\"1.0\" encoding=\"UTF-8\"?><Data>";	    
			
			foreach($cur_line as $key=>$value) {
			
				if( $info_type=='description' ) $result.='<'.$key.'>'.number_format($value, 0, '.', '').'</'.$key.'>';		
				
				if( $info_type=='file' && $key=='recordingfile' ) {
					$filename=$value;
					$handle = fopen($filename, "r");
					$contents = fread($handle, filesize($filename));
					fclose($handle);
					$result=$contents;
				}
			}
			
			if( $info_type=='description' ) $result.="</Data>";
		}    
    }
    
    echo $result;

    
// Data from filesystem     
function get_data_from_filesystem() {

    // Common
    global $dir_path;
    global $time_interval;
    global $data_from_database;
    
    // Data from file system
    global $diff_current_zone_GMT;
    global $coeff_size_sec_mp3;
    global $coeff_size_sec_wav;    

    global $uniqueid;
    global $caller_number;
    global $min_call_duration;
    global $info_type;
    global $record_index;   
 
    global $uniqueid;
    global $uniqueid_num;
    global $uniqueid_num_low;
    global $uniqueid_num_upper;
    
    global $uniqueid_low;
    global $uniqueid_upper;

    // From file structure
    if( strlen($dir_path)==0 ) {
		echo 'Path to directory is not defined!';
		exit;
    }
    
    $len_path_dir=strlen($dir_path);
    if( substr($dir_path, $len_path_dir-1, 1)!=='/' ) {
		$dir_path.='/';
    }
      
    $current_date=date('Ymd', time());	
    $current_time=date('His', time());
    
    $file_date=date('Ymd', $uniqueid_num+$diff_current_zone_GMT*3600);
	$file_year=date('Y', $uniqueid_num+$diff_current_zone_GMT*3600);
	$file_month=date('m', $uniqueid_num+$diff_current_zone_GMT*3600);
	$file_day=date('d', $uniqueid_num+$diff_current_zone_GMT*3600);	
	
    $dir_search=$dir_path.$file_year.'/'.$file_month.'/'.$file_day.'/';
	$found_files=select_files($dir_search, "", 2);
	
    $dir_content=Array();
    $loc_counter=0;	
    while( list($key, $value)=each($found_files) ) {

		$loc_counter+=1;

		$file_uniqueid=get_uniqueid($value['name']);
		if($file_uniqueid!=='') {

			if( key_exists($file_uniqueid, $dir_content) ) {
				$loc_suffix=sprintf('%d', $loc_counter);
				$dir_content[$file_uniqueid.$loc_suffix]=$key;
			}
			else {
				$dir_content[$file_uniqueid]=$key;
			}	    
		}
    }
        
    ksort($dir_content);

    $selected_files=Array();
    foreach($dir_content as $key=>$current_file) {

		if( ((double)$key)>=$uniqueid_num_low && ((double)$key)<=$uniqueid_num_upper ) {
			$selected_files[]=$current_file;
		}
    }     
    
    $result_columns=Array();
    if( $info_type=='description' ) {
    
		$file_counter=0;
		$total_duration=0;
		foreach($selected_files as $current_file) {
			$full_filename=$current_file;
			
			$file_extension='';
			$file_name_length=strlen($current_file);
			if(  $file_name_length>3 ) {
				$file_extension=substr($current_file, $file_name_length-3, 3);
			}
					
			$file_coeff_size_sec=0;
			if( strcasecmp($file_extension, 'mp3')==0 ) {
				$file_coeff_size_sec=$coeff_size_sec_mp3;
			}
			elseif( strcasecmp($file_extension, 'wav')==0 ) {
				$file_coeff_size_sec=$coeff_size_sec_wav;
			}
			
			if( file_exists($full_filename) ) {		
				$file_counter+=1;
				$total_duration+=round( filesize($full_filename)*$file_coeff_size_sec) ;
			}	    
		}
		
		$result_columns['NumberOfRecords']=$file_counter;
		$result_columns['CallDuration']=$total_duration;		
    }
    
    $record_index_num=(int)($record_index);
    if( $info_type=='file' && count($selected_files)>=$record_index_num && $record_index_num>0) {
		$result_columns['recordingfile']=$selected_files[$record_index_num-1];
    }
    
    $data[]=$result_columns;
    
    return($data);
}
 
function select_function($select_function_type, $file_path, $file_attributes) {
	$result=true;

	if( $file_attributes['directory']==true || $file_attributes['size']<60 ) $result=false;
	
	return($result);
}  

// Data from database
function get_data_from_database() {

    // Common
    global $dir_path;
    global $time_interval;
    global $data_from_database;
    
    // Data from file system
    global $conn_dsn;
    global $conn_user;
    global $conn_pass;    

    global $uniqueid;
    global $caller_number;
    global $min_call_duration;
    global $info_type;
    global $record_index;   
 
    global $uniqueid;
    global $uniqueid_num;
    global $uniqueid_num_low;
    global $uniqueid_num_upper;
    
    global $uniqueid_low;
    global $uniqueid_upper;

    $caller_number=remove_symbols($caller_number);
    $number_len=strlen($caller_number);
    if( $number_len>10 ) $caller_number=substr($caller_number, $number_len-10, 10);
    if( $number_len==0 ) exit;
    
  
   $db_connection=odbc_connect($conn_dsn, $conn_user, $conn_pass);
   if( $db_connection===false ) {
	exit;
   }
 
   // Query
   $qry =  "USE asteriskcdrdb";
   $result = odbc_exec($db_connection,$qry);

   // Get Result
   if( $info_type=='description' ) {
	$qry = " SELECT COUNT(*) as NumberOfRecords, ";
	$qry .= "       SUM(cdr.billsec) as CallDuration ";
	$qry .= " from cdr ";
	$qry .= " where cdr.uniqueid>='".$uniqueid_low."' and cdr.uniqueid<='".$uniqueid_upper."' and cdr.src LIKE '%".$caller_number."' and cdr.billsec>=".$min_call_duration;
	$qry .= " order by cdr.calldate asc, cdr.uniqueid asc, cdr.dst asc, cdr.accountcode asc";
    }
    elseif( $info_type=='file' ) {
	$qry = " SET @record_index=0 ";
	$result = odbc_exec($db_connection, $qry);
	$qry = " SELECT common.recordingfile as recordingfile from ";
	$qry .= " (SELECT cdr.recordingfile as recordingfile, ";
	$qry .= "       (@record_index:=@record_index + 1) as RecordIndex ";
	$qry .= " from cdr ";
	$qry .= " where cdr.uniqueid>='".$uniqueid_low."' and cdr.uniqueid<='".$uniqueid_upper."' and cdr.src LIKE '%".$caller_number."' and cdr.billsec>=".$min_call_duration;
	$qry .= " order by cdr.calldate asc, cdr.uniqueid asc, cdr.dst asc, cdr.accountcode asc";
	$qry .= " ) as common ";
	$qry .= " where common.RecordIndex=".$record_index;
    }
      
    $result = odbc_exec($db_connection, $qry);

   // Get Data From Result
   while ($data[] = odbc_fetch_array($result));

   // Free Result
   odbc_free_result($result);

   // Close Connection
   odbc_close($db_connection);
   
   return($data);
}
    
    
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
   
    $second_point=-1;
    for( $i=$first_point+1; $i<$filename_len; $i++ ) {
        if( !ctype_digit( substr($filename, $i, 1) ) ) {
                $second_point=$i;
                break;
        }
    }

    if( $second_point!==-1 && ($second_point-$first_point)<10 ) {

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
 
        
    $result=(double)$file_uniqueid;
    $result=number_format($result, 10, '.', '');
    
    return($result);
}

?>
