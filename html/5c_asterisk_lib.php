<?php

// version 18.09.2017

date_default_timezone_set('Etc/GMT-3');

// Connector to asterisk phone server
// Contains methods to request asterisk server and get reply
class asterisk_connector {

	public $host;
	public $port;
	public $login;	
	public $password;	
	public $connection;
	
	public function __construct($param_host=NULL, $param_port=NULL, $param_login=NULL, $param_password=NULL) {
		$result=true;
		
		if( isset($param_host) ) {
			$this->host=$param_host;
		}
		else {
			$this->host='';
		}
		
		if( isset($param_port) ) {
			$this->port=$param_port;
		}
		else {
			$this->port='';
		}
		
		if( isset($param_login) ) {
			$this->login=$param_login;
		}
		else {
			$this->login='';
		}
		
		if( isset($param_password) ) {
			$this->password=$param_password;
		}
		else {
			$this->password='';
		}
		
		return($result);
	}
	
	public function __connect() {
		$result=true;
		
		$sconn = fsockopen($this->host, intval($this->port));

		if (!$sconn) $result=false; 
		
		$this->connection=$sconn;
		
		return($result);
	}
	
	public function __close_connection() {
		$result=true;
		
		if( isset($this->connection) ) fclose($this->connection); 
		
		return($result);
	}
	
	public function __submit_string($param_string=NULL, $pause=0) {
		$result=true;
		
		if( isset($this->connection) ) {
		
			if( isset($param_string) && is_string($param_string) ) {
				fputs($this->connection, $param_string."\r\n");
			}
			else {
				fputs($this->connection, "\r\n");
			}
			
			fflush($this->connection);
			
			if( is_numeric($pause) && $pause>0 ) usleep($pause);
		}
		else {
			echo 'No connection!';
			$result=false;
		}
		
		return($result);
	}
	
	public function __get_reply() {
		$result='';
		
		fflush($this->connection);
		
		while( !feof($this->connection) ) {
			$result=$result.fgets($this->connection);
		}
		
		return($result);
	}
	
}




?>
