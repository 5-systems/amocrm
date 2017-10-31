<?php
	
	// Settings
	$conn_user='root';
	$conn_pass='Reload_123';
	$conn_host='localhost';
	$conn_database='asteriskcdrdb';
	 
	date_default_timezone_set ('Etc/GMT-3');
	header('Content-Type: text/html; charset=utf-8');
	
	$mysqli = new mysqli($conn_host, $conn_user, $conn_pass, $conn_database);
	if( $mysqli->connect_error ) {
		exit('Connection failed');
	}	

	// Query
	$qry =  "USE asteriskcdrdb";
	$result = $mysqli->query($qry);
	if( $result===false ) {
		exit('Database not found');
	}

	$qry =  "CREATE TABLE crm_linkedid(linkedid NUMERIC(24, 10) PRIMARY  KEY)";
 	$result = $mysqli->query($qry);
	if( $result===false ) {
		echo 'Table is not created!';
	}
	else {
		echo 'Table is created!';
	}
	
?>
