<?php

$url    = 'http://da.thailife.com:8080/DaOperation/rest/public/findagent';
$fields = [
    //"perid"    => "",
    "position" => "",    
    "agerange" => "20-30",
    //"position" => "",
    //"brancode" => "",
];
     
$curl = curl_init($url);        
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTREDIR, 3);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$result = curl_exec($curl);
curl_close($curl);

print("<pre>" . print_r($result, true) . "</pre>");