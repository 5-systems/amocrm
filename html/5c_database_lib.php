<?php

  // version 30.10.2017

  require_once('5c_files_lib.php');  
  require_once('5c_std_lib.php');


function mysql_delete_rows($db_conn, $database_name, $table, $select_condition, $log_file='') {

   $result=false;

   if( $db_conn===false ) {
      write_log('Connection to database is not valid ', $log_file, 'MYSQL_DELETE');
      return($result);
   }
   
   if( strlen(Trim($select_condition))===0 ) {
      write_log('Select condition is not valid ', $log_file, 'MYSQL_DELETE');
      return($result);      
   }   
   
   
   $query_text="";
   $query_text.="use &database_name&;";
   
   template_set_parameter('database_name', $database_name, $query_text);
   
   $db_status=mysql_query($query_text, $db_conn);
   if( $db_status===false ) {
      write_log('Cannot select database: '.mysql_error($db_conn), $log_file, 'MYSQL_DELETE');
      return($result);      
   }
	 
   $query_text="";
   $query_text.="delete from calls where &select_condition&;";
   
   template_set_parameter('select_condition', $select_condition, $query_text);      
   
   $db_status=mysql_query($query_text, $db_conn);
   if( $db_status===false ) {
      write_log('Cannot delete records from database: '.mysql_error($db_conn), $log_file, 'MYSQL_DELETE'); 
      return($result);
   }
   
   $result=true;
   return($result);

}  
  
?>  