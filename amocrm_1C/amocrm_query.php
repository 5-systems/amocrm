<?php

   date_default_timezone_set('Etc/GMT-3');

   if( count($_REQUEST)===0 ) {

      if( count($argv)>1 ) $_REQUEST['param_login']=$argv[1];

   }
   
   $current_dir_path=getcwd();
   $current_dir_path=rtrim($current_dir_path, '/').'/';
   $amocrm_dir=$current_dir_path.'../amocrm/';
   
   $settigs_found=false;
   if( isset($_REQUEST['param_login'])
      && strlen($_REQUEST['param_login'])>0 ) {
      
      $settings_file_path=$amocrm_dir.'amocrm_settings_'.strVal($_REQUEST['param_login']).'.php';
      if( file_exists($settings_file_path) ) {
         require_once($settings_file_path);
         $settigs_found=true;
      }
   }
   
   if( $settigs_found===false ) {
      require_once($amocrm_dir.'amocrm_settings.php');
   }
   
   require_once($amocrm_dir.'5c_amocrm_lib.php');
   
   @$method=$_REQUEST['method'];
   
   if(!isset($method)) $method='';
   
   
   write_log('blank_line', $amocrm_log_file, 'AMO_QUERY');
   write_log($_REQUEST, $amocrm_log_file, 'AMO_QUERY');
   
   
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
      $http_requester->{'lock_priority'}=30;
      $http_requester->{'max_wait_time_for_lock_sec'}=30;
   }
   
   
   $result=array('result'=>'failed', 'error'=>'не определена');
   
   $common_fields=array();
   $custom_fields=array();

   reset($_REQUEST);
   
   while( list($key, $value)=each($_REQUEST) ) {
      
      if( is_array($value)
          && array_key_exists('type', $value)
          && array_key_exists('value', $value) ) {
      
          if( $value['type']==='custom' ) {
             $custom_fields[]=array('id'=>$key, 'values'=>array(array("value"=>$value['value'])));
          }
          elseif( $value['type']==='common' ) {
             $common_fields[$key]=$value['value'];
          }
             
      }
      
   }
   
   $lead_fields=array_merge($common_fields, array('custom_fields'=>$custom_fields));
   
   
   if( $method==='change_status' ) {
      
      change_status($lead_fields, $http_requester, $result);
      
   }
   elseif( $method==='create_task' ) {
      
      create_task($lead_fields, $http_requester, $result);
      
   }
   else {
      
      $result['error']='method '.$method.' не найден ';
      
   }
   
   $return_result=json_encode($result);
   
   echo $return_result;
   
   write_log('finish ', $amocrm_log_file, 'AMO_QUERY');

   
function change_status($lead_fields, $http_requester, &$result_array=array()) {
   
   $result=false;
   
   $update_status=false;
   if( array_key_exists('id', $lead_fields)
      && is_numeric($lead_fields['id'])
      && intVal($lead_fields['id'])>0 ) {
         
      $lead_id=strVal($lead_fields['id']);
      $parameters=array('id'=>$lead_id);
      $updated_fields=array(intVal($lead_id)=>$lead_fields);
      
      $update_status=update_leads_info($parameters, $updated_fields, $http_requester);
      if( $update_status===false ) {
         $result_array['error']='Ошибка запроса к амоЦРМ ';
      }
      
   }
   else {
      $result_array['error']='Идентификатор сделки не найден ';
   }
   
   if( $update_status===true ) {
      $result_array=array('result'=>'success');
   }
   
   $result=$update_status;
   
   return($result);
   
}


function create_task($lead_fields, $http_requester, &$result_array=array()) {
   
   $result=false;
   
   return($result);
   
}
   
?>
