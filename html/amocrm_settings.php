<?php

  // Files
  $amocrm_coockie_file='cookie.txt';
  $amocrm_log_file='telephony_log.txt';
  $callback_log='callback.log';
  
  // Account
  $amocrm_USER_LOGIN='your@email.ru';
  $amocrm_USER_HASH='AMOCRM_HASH';
  $amocrm_account='youraccount';
  $amocrm_sleep_time_after_request_microsec=340000;

  // Phone station
  $phone_prefix='8';
  $host_phone_station='127.0.0.1';
  $port_phone_station='5038';
  $user_phone_station='admin';
  $password_phone_station='amp111';
  $timeout_phone_station="10";
  $url_records='http://yourip/monitor/';
  $url_get_record_phone_station='https://yourip/get_record_buffer.php';
  
  // Cron
  $records_shift_time_for_update_duration_in_hours_phone_station=0.0;
  $records_period_time_for_update_duration_in_hours_phone_station=1.0;
  $dir_records='/var/www/html/monitor/';
  $records_coeff_byte_to_sec_mp3_phone_station=0.000249303;
  $records_coeff_byte_to_sec_wav_phone_station=0.000062375;
  $write_log_cron=true;
  $clean_amocrm_phonestation_database=true;
  $update_call_duration=true;
  $clean_crm_linkedid_table=true;
  
  // Fields id
  $custom_field_user_amo_crm='1779045';
  $custom_field_user_phone='1779061';
  $custom_field_first_called_number="1781769";
  
  $custom_field_comment='1780723';
  $custom_field_web_site='1783029';

  $custom_field_do_not_create_lead='1779705';
  $custom_field_address_type='1775865';
  $custom_field_address_type_value_phone_call='4139615';
  $custom_field_address_type_value_string_phone_call='Входящий звонок';
  $custom_field_address_type_value_outcoming_call='4150987';
  $custom_field_address_type_value_string_outcoming_call='Исходящий звонок';
  $custom_field_address_type_value_missed_call='4139617';
  $custom_field_address_type_value_string_missed_call='Пропущенный звонок';
  $custom_field_address_type_value_site_call='4139619';
  $custom_field_address_type_value_string_site_call='Звонок с сайта';
  $custom_field_address_type_value_internet_shop_call='4139621';
  $custom_field_address_type_value_string_internet_shop_call='Интернет-магазин';
  $custom_field_address_type_value_online_chat_call='4139623';
  $custom_field_address_type_value_string_online_chat_call='Чат';

  $custom_field_phone_id="1133906";
  $custom_field_phone_enum="2591354";
  $custom_field_email_id="1133908";
  $custom_field_email_enum="2591366";
  
  $status_accepted_for_work='15037849';
  $status_accepted_for_work_pipeline_id='618805';
  $status_successful_realization='142';
  $status_canceled='143';
  
  $custom_field_user_pipeline_id='1783165';
  $custom_field_user_pipeline_status_id='1783167';
  
  $phone_prefix_presentation='+7';

  $amocrm_users=array();
  $amocrm_users[1509886]=array('id'=>'1509886', 'name'=>'Имя пользователя', 'user_phone'=>'100');
 
  // Database
  $amocrm_database_user='amocrmuser';
  $amocrm_database_password='pass_form_amocrmuser';
  $amocrm_database_host='127.0.0.1';
  $amocrm_database_port='3306';
  $amocrm_database_name='amocrm_phonestation';
  
  // Create database amocrm_phonestation
  $amocrm_database_root_user='root';
  $amocrm_database_root_password='pass_from_root';
  
  // Asterisk database asteriskcdrdb: clean table crm_linkedid
  $crm_linkedid_host='127.0.0.1';
  $crm_linkedid_port='3306';
  $crm_linkedid_database_name='asteriskcdrdb';
  $crm_linkedid_user='root';
  $crm_linkedid_password='pass_from_root';

  // Callback
  $queue='997';
  
  // Custom fields of client (filled optionally)
 
  // Add/delete comments
  $custom_field_company_number='1775853';
  
  // Responsible users by FirstCalledNumber
  //$responsible_users_by_first_called_number=array();
  //$responsible_users_by_first_called_number['vologda']='1509886';
  //$responsible_users_by_first_called_number['4955404614']='1509886';
  
  // Put custom fields id, which shoud be filled in leads
  // Names of fields should be the same, as keys in $_REQUEST
  $additional_custom_fields=array();

  //$_REQUEST['CREATED_BY_5SYSTEMS']='Да';
  //$additional_custom_fields['CREATED_BY_5SYSTEMS']=array('id'=>'1787433', 'element_type'=>'lead');
  
  // amocrm_1C integration
  $amocrm_1C_integration_used=false;
  $amocrm_1C_integration_web_service_url='http://127.0.0.1:8080/database_url/ws/wsamointegration.1cws?wsdl';
  $amocrm_1C_integration_web_service_login='WebService_user';
  $amocrm_1C_integration_web_service_password='00000';
  
  $amocrm_1C_integration_web_service_request_url='http://127.0.0.1:80/amocrm_1C/amocrm_1C_query.php';  
  $amocrm_1C_integration_web_service_request_timeout='4';
  
  $amocrm_1C_integration_contact_custom_field_client_code_1='1786761';
  $amocrm_1C_integration_contact_custom_field_client_name_1='1786763';
  $amocrm_1C_integration_contact_custom_field_client_code_2='1786765';
  $amocrm_1C_integration_contact_custom_field_client_name_2='1786767';
  $amocrm_1C_integration_contact_custom_field_client_code_3='1786769';
  $amocrm_1C_integration_contact_custom_field_client_name_3='1786771';
  $amocrm_1C_integration_contact_custom_field_principal_client='1786773';
  
  $amocrm_1C_integration_company_custom_field_client_code_1='1786775';
  $amocrm_1C_integration_company_custom_field_client_name_1='1786777';
  $amocrm_1C_integration_company_custom_field_client_code_2='1786779';
  $amocrm_1C_integration_company_custom_field_client_name_2='1786781';
  $amocrm_1C_integration_company_custom_field_client_code_3='1786783';
  $amocrm_1C_integration_company_custom_field_client_name_3='1786785';
  $amocrm_1C_integration_company_custom_field_principal_client='1786787';  
    
?>
