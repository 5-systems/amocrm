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
   
   $url='http://your_ip/amocrm_get_record.php';
   
   $data=http_build_query($_REQUEST, null, '&', PHP_QUERY_RFC3986);
   
   write_log($data, $log_file, 'GET_REC');
   
   $headers=array('Content-Type:application/x-www-form-urlencoded; charset=utf-8');
   
   $result='';
   $result=request_POST($url, $data, $log_file, $headers);
   
   $result_length=strlen(bin2hex($result))/2;
   
   write_log('result length='.strVal($result_length), $log_file, 'GET_REC');
   
   echo $result;

?>
