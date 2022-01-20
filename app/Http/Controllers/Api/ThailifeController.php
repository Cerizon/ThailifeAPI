<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ThailifeController extends Controller
{    

    public function findagent(Request $request)    
    {    
        $url    = 'http://da.thailife.com:8080/DaOperation/rest/public/findagent';   

        $fields = [
            "dna" => $request->dna,
            "perid" => $request->perid,            
            "agerange" => $request->agerange,
            "brancode" => $request->brancode,            
        ];
        
        if ($request->input('position')) {            
            $fields = $fields + ["position" => $request->position];          
        } else {            
            $fields = $fields + ["position" => ""];
        }        

        $curl = curl_init($url);        
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTREDIR, 3);        
        $result = curl_exec($curl);
        curl_close($curl);

        return json_decode($result,true);
    }

    public function AgentData(Request $request)
    {
        $data = ['idNo' => $request->idNo];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://tl.thailife.com:8083/SalesOverrideGeteWate/rest/ulink/getagentforulinkapp");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_encode($result);
    }
    
}