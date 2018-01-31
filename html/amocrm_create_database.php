<?php

   date_default_timezone_set('Etc/GMT-3'); 
   
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
  
   
   $db_host=$amocrm_database_host;
   
   $db_conn=new mysqli($db_host, $amocrm_database_root_user, $amocrm_database_root_password);
   if( !isset($db_conn) ) {
      exit('Connection to database is failed: '.$db_conn->connect_errno);    
   } 

   // Create database
   $query_text="";      
   $query_text.="create database &amocrm_database_name&;";
   $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);      

   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot create database: '.$db_conn->error);  
   }
   
   // Select database
   $query_text="";      
   $query_text.="use &amocrm_database_name&;";
   $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);      
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot select database: '.$db_conn->error);  
   }   
   
   // Create table calls
   $query_text="";      
   $query_text.="create table &table_name& (";
   $query_text.="   date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',";
   $query_text.="   uniqueid varchar(36) NOT NULL DEFAULT '',";
   $query_text.="   client_phone varchar(36) NOT NULL DEFAULT '',";
   $query_text.="   client_name varchar(255) NOT NULL DEFAULT '',";
   $query_text.="   user_phone varchar(36) NOT NULL DEFAULT '',";
   $query_text.="   user_id varchar(36) NOT NULL DEFAULT '',";   
   $query_text.="   user_name varchar(255) NOT NULL DEFAULT '',";   
   $query_text.="   lead_id varchar(36) NOT NULL DEFAULT '',";
   $query_text.="   new_client boolean NOT NULL DEFAULT false,";
   $query_text.="   new_lead boolean NOT NULL DEFAULT false,";
   $query_text.="   outcoming boolean NOT NULL DEFAULT false,";
   $query_text.="   missed boolean NOT NULL DEFAULT false,";
   $query_text.="   file_path text NOT NULL DEFAULT '',";
	 
   $query_text.="   INDEX calls_date_index USING BTREE (date),";
   $query_text.="   INDEX calls_uniqueid_index USING BTREE (uniqueid),";
   $query_text.="   INDEX calls_user_id_index USING BTREE (user_id)";
      
   $query_text.=") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

   $query_text=set_parameter('table_name', 'calls', $query_text);   
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot create table calls: '.$db_conn->error);  
   }

   // Create table locks
   $query_text="";
   $query_text.="create table &table_name& (";
   $query_text.="   queue_name varchar(100) NOT NULL DEFAULT '',";
   $query_text.="   id numeric(10,0) NOT NULL DEFAULT 0,";
   $query_text.="   time numeric(16,6) NOT NULL DEFAULT 0,";
   $query_text.="   PRIMARY KEY (queue_name, id)";
   $query_text.=") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

   $query_text=set_parameter('table_name', 'locks', $query_text);

   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot create table locks: '.$db_conn->error);
   }
   
   // Create table for queue
   $query_text="";
   $query_text.="create table &table_name& (";
   $query_text.="   queue_name varchar(100) NOT NULL DEFAULT '',";
   $query_text.="   pid numeric(10,0) NOT NULL DEFAULT 0,";
   $query_text.="   time numeric(16,6) NOT NULL DEFAULT 0,";
   $query_text.="   priority numeric(10,0) NOT NULL DEFAULT 0";
   $query_text.=") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
   
   $query_text=set_parameter('table_name', 'queue', $query_text);
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
       exit('Cannot create table queue: '.$db_conn->error);
   }
   
   // Create user
   $query_text="";
   $query_text.="create user '&amocrmuser&' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot create user: '.$db_conn->error);  
   }
   
   // Add grants
   $query_text="";
   $query_text.="grant all privileges on `amocrm_phonestation`.* to '&amocrmuser&'@'%' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot add all privileges to amocrmuser@ %: '.$db_conn->error);  
   }
   
   $query_text="";
   $query_text.="grant all privileges on `amocrm_phonestation`.* to '&amocrmuser&'@'localhost' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
       exit('Cannot add all privileges to amocrmuser@localhost: '.$db_conn->error);
   }
   
   $query_text="";
   $query_text.="grant usage on *.* to '&amocrmuser&'@'%' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);   
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
      exit('Cannot add usage privilege to amocrmuser@ %: '.$db_conn->error);  
   }
   
   $query_text="";
   $query_text.="grant usage on *.* to '&amocrmuser&'@'localhost' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=$db_conn->query($query_text);
   if( $db_status===false ) {
       exit('Cannot add usage privilege to amocrmuser@localhost: '.$db_conn->error);
   }
   
function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}   
   
?>
