<?php

	require_once('/var/lib/5-systems/5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	// Settings
	$search_dir=Array();
	$search_dir[]='/var/spool/asterisk/monitor/';

	$delete_converted_files=true;
	$maximum_number_treated_files=1000;
	$maximum_recursion_level=4;

	// Main program
	header("Expires: 0");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

//	error_reporting(E_ALL);
//	ini_set('display_errors', 1); 

	$files_array=select_files($search_dir, "", $maximum_recursion_level, $maximum_number_treated_files);
	while( list($file_path, $file_attributes)=each($files_array) ) {

		$file_wav=$file_path;
		$file_wav_exten=$file_attributes['exten'];
		$file_wav_exten_len=strlen( $file_wav_exten );
		$file_wav_name=$file_attributes['name'];
		$file_wav_name_len=strlen($file_wav_name);

		$file_mp3='';
		if( strlen($file_wav_name)>3 && $file_wav_exten_len==3 ) {
			$file_mp3=$file_attributes['dir'].substr( $file_wav_name, 0, $file_wav_name_len-3 ).'mp3';
		}

		if( strlen($file_wav)>0 && strlen($file_mp3)>0 ) {
			$conv_status=convert_wav_to_mp3($file_wav, $file_mp3);

			if( $conv_status===0 ) {
				$files_to_delete=Array();
				$files_to_delete[$file_wav]='';
				action_delete($files_to_delete);
			}	
		}
		
	}



function select_function($select_function_type, $file_path, $file_attributes) {
	
	$result=false;

	$extension=$file_attributes['exten'];
	$extension=strtolower($extension);

	if( $file_attributes['directory']===false &&  $extension=='wav' ) $result=true;

	return($result);
}

?>
