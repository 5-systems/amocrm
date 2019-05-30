<?php

  // version 30.11.2018

  header('Access-Control-Allow-Origin: *');
  include_once('5c_files_lib.php');

  function request_POST($url, $parameters, $log_path="", $headers="", $timeout=60, &$response_header=null) {

  $result=false;
 
  $record=Array();
  $record['url']=$url;
  
  if( is_array($parameters) ) {
  
    reset($parameters);
    while(list($key, $value)=each($parameters)) {
      $record[$key]=$value;   
    }
  
  }
  else {
    $record['parameters']=strval($parameters);
  }
  
  if( strlen($log_path)>0 ) write_log($record, $log_path);
       
  $curl = curl_init();
  
  if( !is_array($headers) && strlen($headers)===0 ) {

     $headers=array();     
     if( is_array($parameters) ) { 
        $headers[] = "Content-Type: multipart/form-data";
     }
     else {
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
     }
     
  }
  
  if( is_string($timeout)
      && is_numeric($timeout) ) {
      
     $timeout=floatVal($timeout);
  }
  elseif( !is_numeric($timeout) ) {
     $timeout=60;
  }
  
  // Request
  curl_setopt($curl, CURLOPT_URL, $url);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_COOKIESESSION, false);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);

  $result=curl_exec($curl);

  if( isset($response_header) ) {
      $response_header=get_headers($url);
  }
  
  return($result);
}


function request_GET($url, $parameters, $log_path="", $coockie_path="", $headers="", $timeout=60, &$response_header=null) {

  $result=false;

  $url_used=$url;  
  
  $record=Array();
  $record['url']=$url;

  $parameters_separator_found=false;
  $separator_position=strpos($url, '?');
  if( $separator_position!==false ) $parameters_separator_found=true;  
  
  if( is_array($parameters) ) {
  
    reset($parameters);
    $cycle_index=0;
    while(list($key, $value)=each($parameters)) {
      $record[$key]=$value;

      $prefix='&';
      if( $cycle_index===0 && $parameters_separator_found===false ) $prefix='?';

      $url_used.=$prefix.$key.'='.$value;

      
      $cycle_index++;      
    }
  
  }
  else {
    $record['parameters']=strval($parameters);
    
    if( $parameters_separator_found ) {
      $url_used.='&'.strval($parameters);
    }
    else {
      $url_used.='?'.strval($parameters);     
    }    
    
  }
  
  if( strlen($log_path)>0 ) write_log($record, $log_path);
       
  $curl = curl_init();
  
  if( !is_array($headers) && strlen($headers)===0 ) {
    $headers=array();
    $headers[]="Content-Type: application/x-www-form-urlencoded";
  }
  
  if( is_string($timeout)
     && is_numeric($timeout) ) {
        
     $timeout=floatVal($timeout);
  }
  elseif( !is_numeric($timeout) ) {
     $timeout=60;
  }
  
  // Request
  curl_setopt($curl, CURLOPT_URL, $url_used);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($curl, CURLOPT_HEADER, false);
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($curl, CURLOPT_COOKIESESSION, false);
  curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($curl, CURLOPT_HTTPGET, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);

  $result=curl_exec($curl);

  if( isset($response_header) ) {  
      $response_header=get_headers($url_used);
  }
  
  return($result);
}

?>
