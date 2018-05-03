<?php

  header('Status: 200 Ok');

  require_once('5c_files_lib.php');
  require_once('5c_http_lib.php');
  require_once('settings.php');

  $url='http://your_ip/amocrm_lead_on_responsible_change.php';

  @$data=json_encode($_REQUEST);

  $body='';
  $body=file_get_contents("php://input");

  write_log($body, $log_file, '');

  $headers=array('Content-Type:application/x-www-form-urlencoded; charset=utf-8');

  $result='';
  $result=request_POST($url, $body, $log_file, $headers);

  write_log('result='.$result, $log_file, '');

  echo $result;

?>
