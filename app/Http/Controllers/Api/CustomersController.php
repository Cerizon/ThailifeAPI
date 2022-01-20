<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerSurveys;
use App\Models\SMStoform;
use App\Http\Utils\SmsSender;
use Illuminate\Support\Carbon;
use FFI\Exception;


class CustomersController extends Controller
{
    public function SendSms(Request $req)
    {
        $phone = $req->input('phone');
        $campaign = "THAILIFEDM";
        $link = md5($phone . Carbon::now()->timestamp . 'lifethai');
        $sms_sent = new SmsSender();
        $sms_sent->senderName = $campaign;
        $sms_sent->smsMessage = "กรุณายอมรับข้อตกลงผ่านลิงค์ด้านล่าง " . "http://frontendlife.online/pdpa/common/"  . $link;
        $sms_sent->mobileNo = $phone;

        $result_sms =  json_decode($sms_sent->sendByGet());
        $data = [
            'pid' => $result_sms->pid,
            'sms_campaign' => $campaign,
            'phone' =>  $result_sms->mobile_no,
            'link' => $link,
            'verify' => md5(
                $result_sms->pid . "thailife"
            )
        ];
        $result = $this->Save_sms($data);
        if ($result == 'success') {
            // $response = new Response("200, success");
            // return  $response->withCookie('clivert', $result_sms->pid, 1500);
            $result = json_encode(["code" => "200", "msg" => "success", "pid" => $result_sms->pid]);
        }
        return Response($result);
    }



    public function Search_sms(Request $req)
    {
        try {
            $query = SMStoform::where($req->input())->first();
            if (is_null($query)) {
                return Response('{ "msg" : "not_found"  }')->header("Content-Type", "application/json");
            }
            return Response('{ "data" : ' . json_encode($query) . ' , "msg" : "success" }')->header("Content-Type", "application/json");
        } catch (Exception $e) {
            return Response('{ "msg" : "not_know"  }')->header("Content-Type", "application/json");
        }
    }


    public function Save_sms($req)
    {
        $confirm = SMStoform::create([
            'pid' => $req["pid"],
            'sms_campaign' => $req["sms_campaign"],
            'phone' =>  $req["phone"],
            'link' => $req["link"],
            'verify' => md5($req["pid"] . "thailife"),
        ]);
        if (!$confirm) {
            return "fail";
        }
        return "success";
    }

    public function Save_customer(Request $req)
    {
        $profile = CustomerSurveys::create([
            'prefix' => $req->prefix,
            'surveyId' => $req->surveyId,
            'firstname' => $req->firstname,
            'lastname' => $req->lastname,
            'job' => $req->job,
            'telephone' => $req->telephone
        ]);
        if (!$profile) {
            return Response('{"code": "Failed" }', 200);
        }
        return Response('{"code": "Success" }', 200);
    }


    public function Update_sms(Request $req)
    {
        $query = $req->input();
        $sms = SMStoform::where($query)->first()->update(['checked' => True]);
        return Response('{"status":' . $sms . '}');
    }
}
