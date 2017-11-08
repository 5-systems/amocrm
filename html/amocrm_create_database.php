<?php
   
   require_once('amocrm_settings.php');
   
   date_default_timezone_set('Etc/GMT-3');      
   
   $db_host=$amocrm_database_host;
   if( strlen($amocrm_database_port)>0 ) $db_host.=':'.$amocrm_database_port;
   
   $db_conn=mysql_connect($db_host, $amocrm_database_root_user, $amocrm_database_root_password);
   if( $db_conn===false ) {
      exit('Connection to database is failed: '.mysql_error());    
   } 

   // Create database
   $query_text="";      
   $query_text.="create database &amocrm_database_name&;";
   $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);      
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot create database: '.mysql_error());  
   }
   
   // Select database
   $query_text="";      
   $query_text.="use &amocrm_database_name&;";
   $query_text=set_parameter('amocrm_database_name', $amocrm_database_name, $query_text);      
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot select database: '.mysql_error());  
   }   
   
   // Create table
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
   $query_text.="   file_path text NOT NULL DEFAULT '',";
	 
   $query_text.="   INDEX calls_date_index USING BTREE (date),";
   $query_text.="   INDEX calls_uniqueid_index USING BTREE (uniqueid),";
   $query_text.="   INDEX calls_user_id_index USING BTREE (user_id)";
      
   $query_text.=") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

   $query_text=set_parameter('table_name', 'calls', $query_text);   
   
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot create table: '.mysql_error());  
   }
   
   // Create user
   $query_text="";
   $query_text.="create user '&amocrmuser&' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot create user: '.mysql_error());  
   }
   
   // Add grants
   $asteriskuser_exists=false;
   $query_text="";
   $query_text.="select COUNT(*) as count_num FROM mysql.user as users where users.User='root';";
   $db_status=mysql_query($query_text);
   if(  $db_status===false ) {
      exit('Cannot select users: '.mysql_error());   
   }
   else {

      while($row = mysql_fetch_assoc($db_status)) {
	 if( $row['count_num']>0 ) $asteriskuser_exists=true;    
      }
   
   }
   
   if( $asteriskuser_exists===true ) {
 
      $query_text="";
      $query_text.="grant all privileges on `amocrm_phonestation`.* to '&asteriskuser&'@'localhost' identified by '&asterisk_password&';";
      $query_text=set_parameter('asteriskuser', $amocrm_database_asterisk_user, $query_text);
      $query_text=set_parameter('asterisk_password', $amocrm_database_asterisk_password, $query_text);
      
      $db_status=mysql_query($query_text);
      if( $db_status===false ) {
	 exit("Cannot add all privileges to 'asteriskuser'@'localhost' : ".mysql_error());  
      }
 
      $query_text="";
      $query_text.="grant all privileges on `amocrm_phonestation`.* to '&asteriskuser&'@'%' identified by '&asterisk_password&';";
      $query_text=set_parameter('asteriskuser', $amocrm_database_asterisk_user, $query_text);
      $query_text=set_parameter('asterisk_password', $amocrm_database_asterisk_password, $query_text);
      
      $db_status=mysql_query($query_text);
      if( $db_status===false ) {
	 exit("Cannot add all privileges to 'asteriskuser'@'%' : ".mysql_error());  
      }
      
   }
   
   $query_text="";
   $query_text.="grant all privileges on `amocrm_phonestation`.* to '&amocrmuser&'@'localhost' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);
   
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot add all privileges to amocrmuser: '.mysql_error());  
   }
   
   $query_text="";
   $query_text.="grant usage on *.* to '&amocrmuser&'@'localhost' identified by '&amocrmuser_password&';";
   $query_text=set_parameter('amocrmuser', $amocrm_database_user, $query_text);
   $query_text=set_parameter('amocrmuser_password', $amocrm_database_password, $query_text);   
   
   $db_status=mysql_query($query_text);
   if( $db_status===false ) {
      exit('Cannot add usage privilege to amocrmuser: '.mysql_error());  
   }
   
function set_parameter($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $template);
    return($function_result);
}   
   
?>
