<?php

namespace App\Http\Utils;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SurveyMonkey
 *
 * @author hideoaki
 */
class SmsSender {
    
    // static $API_URL = 'http://113.53.239.252:8080/SmsClickNext/rest/sms/received';
    // static $API_URL = 'http://localhost:8080/SmsClickNext/rest/sms/received';
    static $API_URL = 'http://113.53.239.252:8080/SmsClickNext/rest/sms/received';

    public $senderName = "TLDAS";
    public $mobileNo = null;
    public $campaign = "TLSURVEY_AGENT";
    public $smsMessage = "ทดสอบ ClickNex 1234 GET";
    public $sendPriority = "1";

    public function sendByGet(){
        if(empty($this->mobileNo)) {
            return array(
                'status' => 'error',
                'message' => 'undefined mobile no');
        }

        $url = SmsSender::$API_URL;

        $data = array('sender_name' =>  $this->senderName,
                        'sms_campaign' => $this->campaign , 
                        'mobile_no' => $this->mobileNo , 
                        'sms_message' => $this->smsMessage , 
                        'send_priority'=> $this->sendPriority);

        $data_string = json_encode($data);

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string)));

        $result = curl_exec($ch);

        return $result;
    }


    
	

}