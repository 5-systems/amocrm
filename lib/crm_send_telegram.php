<?php
  // sleep(10);
   require_once('5c_files_lib.php');
	date_default_timezone_set('Etc/GMT-3');

	$logFile = '/var/log/5-systems/telegram.log';

	$callId = "";
	$clientPhone = "";
   $destPhone = "";
	
   if (isset($argv)) {
	   $callId = $argv[1];
	   $clientPhone = $argv[2];
      $destPhone = $argv[3];
   }

   write_log($argv, $logFile);  
  
   require_once("get_files.php");

   $text = urlencode("Вы приняли звнок с Avito. \nДата/время звонка: " . date('d-m-Y H:i:s') . "\nНомер абонента: " . $clientPhone);
 
   $chatId = -195505531;
   $botUrl = "https://api.telegram.org/bot311473296:AAFNAUl4BM_aKLcsq_2kyBT39NEMkuzvPE8/";

   $url = $botUrl . "sendMessage?chat_id=" . $chatId . "&text=" . $text;

   $ch = curl_init(); 
   curl_setopt($ch, CURLOPT_URL, $url); 
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
   $output = curl_exec($ch);
   
   if (count($audioFiles) >= 1) {
      $audioFile = $audioFiles[count($audioFiles) - 1]['name'];

    /*  $fileMp3 = "/var/spool/asterisk/monitor/avito/" . str_replace(".wav", ".mp3", basename($audioFile));
      if (!file_exists($fileMp3)) {
         convert_wav_to_mp3($audioFile, $fileMp3);
      }
      */
      $fileMp3 = $audioFile;
      if (file_exists($fileMp3)) {
                
         $url = $botUrl . "sendVoice";

         $postFields = array(
             'chat_id'   => $chatId,
             'voice'     => new CURLFile(realpath($fileMp3))
         );

         echo "path=" . realpath($fileMp3);
         $ch = curl_init(); 
         curl_setopt($ch, CURLOPT_HTTPHEADER, array(
             "Content-Type:multipart/form-data"
         ));
         curl_setopt($ch, CURLOPT_URL, $url); 
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
         curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); 
         $output = curl_exec($ch);
      } else {
         write_log("File ". $fileMp3 . " not exists.", $logFile);  
      }
      
  } else {
      write_log("No audio files for call ". $callId, $logFile); 
   }
   

