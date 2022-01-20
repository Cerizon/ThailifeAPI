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
class ThailifeAPI
{

    static $API_URL = 'http://da.thailife.com:8080/DaOperation/rest/public/findagent';
    
    static $fieldStructer = array(
        'dna' => '',
        'perid' => '',
        'position' => '',
        'agerange' => '',
        'brancode' => ''
    );

    public function getAllSell()
    {
        return $this->fetch(ThailifeAPI::$fieldStructer);
    }

    public function getAllMember()
    {
        return;
    }

    public function getSellById($id)
    {
        $field = ThailifeAPI::$fieldStructer;
        $field['perid'] = $id;
        return $this->fetch($field);
    }

    private function fetch($fields)
    {
        $requestHeaders = array(
            'Content-Type: application/x-www-form-urlencoded',
        );

        $field_str = '';
        foreach ($fields as $key => $value) {
            $field_str .= $key . '=' . $value . '&';
        }
        rtrim($field_str, '&');
        $url = ThailifeAPI::$API_URL;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $field_str);

        $result = curl_exec($curl);
        curl_close($curl);
        return json_decode($result);
    }
}