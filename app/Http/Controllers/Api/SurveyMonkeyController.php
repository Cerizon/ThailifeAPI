<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Utils\SurveyMonkey;
use Illuminate\Http\Request;
use App\Models\LogSurvey;

class SurveyMonkeyController extends Controller
{    

    public function getSurvey()
    {
        $smk = new SurveyMonkey();
        return $smk->getSurveys();
    }

    public function WebHook($id)
    {
        $smk = new SurveyMonkey();        
        return $smk->subscribeWebhookResponseComplete($id);
    }

    public function LogCreateSurvey(Request $request)
    {
        $log           = new LogSurvey();
        $log->response = $request->response;
        $log->ddate    = date("Y-m-d");
        $log->save();

        return;
    }
    
}