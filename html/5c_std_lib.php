<?php

// version 24.05.2018

date_default_timezone_set ('Etc/GMT-3');

function remove_symbols($src_number) {

    $result='';
    $number_len=strlen($src_number);
    for($i=0; $i<$number_len; $i++) {
    
    $cur_symbol=substr($src_number, $i, 1);
    if( ctype_digit($cur_symbol) ) 
		$result.=$cur_symbol;
    }
    
    return($result);
}

function html_to_utf8($input_str) {
	$output_str=preg_replace_callback("/\\%u([A-Fa-f0-9]{4})/", "html_to_utf8_callback", $input_str);
        $output_str=preg_replace_callback("/\\\\u([A-Fa-f0-9]{4})/", "html_to_utf8_callback", $output_str);	
	return($output_str);
}

function html_to_utf8_callback($matches) {
   return( iconv('UCS-4LE','UTF-8',pack('V',hexdec('U'.$matches[0]))) );
}

function read_file($file_path) {

	$result='';
	if( file_exists($file_path) ) {
	
		$fp=fopen($file_path, 'r');
		if($fp) {
			$result=fread($fp, filesize($file_path));
		}	
	}
	
	return($result);
}

function decode_input_parameter($parameter) {

	if( is_string($parameter) ) {
		$result=urldecode(html_to_utf8($parameter));
	}
	elseif( is_array($parameter) ) {
		$result=Array();

		foreach($parameter as $element) {
			$result[]=urldecode(html_to_utf8($element));
		}

	}
	else {
		$result='';
	}

	return($result);
}

function convert_array_to_xml($input_data, $name) {

	$input_array=$input_data;
	
	$param='<?xml version="1.0" encoding="UTF-8"?>';
	$param.='<data><object type="array" name="'.$name.'">';

	if( !is_array($input_array) ) {
		$_str=$input_array;
		$input_array=Array($_str);
	}	

	foreach($input_array as $element) {
		$param.='<element>'.strval($element).'</element>';
	}
	
	$param.='</object></data>';	
	
	return($param);
}

function replace_special_base64($input_str) {

	$result=$input_str;
        $result=preg_replace("/[=]/", 'EQUALSSIGN', $result);
        $result=preg_replace("/[\\/]/", 'DIVIDESIGN', $result);		
        $result=preg_replace("/[\\+]/", 'PLUSSIGN', $result);
		
        return($result);
}

// Set parameter in a tamplate
function set_param($parameter, $value, $template) {
    $function_result=$template;
    $function_result=str_replace('@'.$parameter.'@@', $value, $template);
    return($function_result);
}

// Set parameter in query 
function template_set_parameter($parameter, $value, &$template) {
    $function_result=$template;
    $function_result=str_replace('&'.$parameter.'&', $value, $function_result);
    $template=$function_result;
    return($function_result);
}

?>