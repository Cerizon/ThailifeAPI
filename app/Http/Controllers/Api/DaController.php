<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaController extends Controller
{

    public function getSurveyByIdTL(Request $request)
    {
        $accessAuth      = $request->accessAuth;
        $sellId          = $request->sellId;        

        $configAuthToken = config('token')['Authorization_Token'];

        if ($accessAuth !== $configAuthToken || empty($accessAuth) || empty($sellId)) {
            return response()->json([
                "error"  => "invalid authentication!! or required field",
                "status" => "401",
            ], 401);
        } else {
            if (strlen($sellId) == 13) {
                $url    = 'http://da.thailife.com:8080/DaOperation/rest/public/findagent';
                $fields = [
                    "perid"    => $sellId,
                    "position" => "",
                    "agerange" => "20-90",
                ];
                $postvars = http_build_query($fields);
                $ch       = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = json_decode(curl_exec($ch));
                curl_close($ch);

                if ($result->totalRecord != 0) {
                    $sellId = $result->data[0]->perid;
                }
            }        

            $surveys = DB::table('employee_survey as es')
                ->where('es.employeeId', $sellId)
                ->join('surveys as s', 's.surveyId', '=', 'es.surveyId')
                ->join('survey_available_day as sad', 'es.surveyId', '=', 'sad.surveyId')
                ->where(['s.available' => '1', 's.isPublic' => '0'])
                ->where('s.start_date', '<=', date('Y-m-d'))
                ->where('s.end_date', '>=', date('Y-m-d'))
                ->get();                             

            $surveysPublic = DB::table('employee_survey as es')
                ->where('es.employeeId', $sellId)
                ->join('surveys as s', 's.surveyId', '=', 'es.surveyId')
                ->join('survey_available_day as sad', 'es.surveyId', '=', 'sad.surveyId')
                ->where(['s.available' => '1', 's.isPublic' => '1'])
                ->where('s.start_date', '<=', date('Y-m-d'))
                ->where('s.end_date', '>=', date('Y-m-d'))
                ->get();            

            // Old
            // $surveys = DB::table('employee_survey as emp_sur')
            //     ->where('employeeId', $sellId)
            //     ->join('surveys', 'surveys.surveyId', '=', 'emp_sur.surveyId')
            //     ->join('survey_available_day as sad', 'emp_sur.surveyId', '=', 'sad.surveyId')
            //     ->where('surveys.available = 1 AND surveys.isPublic = 0 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
            //     ->get();            
            // $surveysPublic = DB::table('surveys')
            //     ->join('survey_available_day as sad', 'surveys.surveyId', '=', 'sad.surveyId')
            //     ->where('surveys.available = 1 AND surveys.isPublic = 1 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
            //     ->get();

            $surveysPublicDetail = DB::table('employee_survey as emp_sur')
                ->where('employeeId', $sellId)->get();

            $surveyPublicMap = [];

            foreach ($surveysPublicDetail as $s) {
                $surveyPublicMap[$s->surveyId] = $s;
            }

            $allSurveys = [];

            foreach ($surveys as $s) {
                /// Filter out the multiple and already submit survey
                if ($s->multiple_answer == 0 && $s->status) {} else {
                    $allSurveys[] = $s;
                }
            }

            foreach ($surveysPublic as $s) {
                if (isset($surveyPublicMap[$s->surveyId]) && $surveyPublicMap[$s->surveyId]->status && $s->multiple_answer == 0) {} else {
                    $allSurveys[] = $s;
                }
            }

            $responses = $this->transformSurveyResponse($allSurveys, false, $request);

            return response()->json($responses);
        }
    }    

    private function transformSurveyResponse($results, $onlySurvey = false, $request)
    {
        $newResults    = [];
        $surveyDetails = [];
        foreach ($results as $r) {
            $n        = [];
            $surveyId = $r->surveyId;
            $detail   = array();
            if (!isset($newResults[$surveyId])) {
                $newResults[$surveyId] = [];
                if ($onlySurvey) {
                    $detail = [
                        'surveyIframeUrl' => $this->getSurveyEmbedUrl($r, $request),
                        'pdfUrl'          => $r->surveyPdfUrl,
                        'surveyName'      => $r->title,
                        'responseCount'   => $r->responseCount,
                    ];
                } else {
                    // var_dump($r); die();
                    if (empty($r->employeeId)) {
                        $r->employeeId = '';
                    }
                    if (empty($r->percentComplete)) {
                        $r->percentComplete = 0;
                    }
                    if (empty($r->status)) {
                        $r->status = 0;
                    }
                    $detail = [
                        'surveyIframeUrl' => $this->getSurveyEmbedUrl($r, $request),
                        'pdfUrl'          => $r->surveyPdfUrl,
                        'employeeId'      => $r->employeeId,
                        'surveyName'      => $r->title,
                        'percentComplete' => $r->percentComplete,
                        'status'          => $r->status,
                        'responseCount'   => $r->responseCount,
                        'createdDate'     => $r->created_at,
                        'completeDate'    => $r->completedAt,
                    ];
                }
                $surveyDetails[$surveyId] = $detail;
            }
            $n['availableDay']       = $r->day;
            $n['availableTime']      = [$r->openTime, $r->closeTime];
            $newResults[$surveyId][] = $n;
        }
        
        $outputArray = [];

        foreach ($newResults as $surveyId => $allTimes) {
            if ($surveyId == 'surveyId') {
                continue;
            }
            $output                       = [];
            $output['surveyId']           = $surveyId;
            $output['surveyAvailableDay'] = [];
            foreach ($allTimes as $time) {
                $eachTime                       = [];
                $eachTime['availableDay']       = $time['availableDay'];
                $eachTime['availableTime']      = $time['availableTime'];
                $output['surveyAvailableDay'][] = $eachTime;
            }
            $surveyDetail = $surveyDetails[$surveyId];
            foreach ($surveyDetail as $k => $v) {
                $output[$k] = $v;
            }
            $outputArray[] = $output;
        }

        return $outputArray;
    }

    private function getSurveyEmbedUrl($survey)
    {
        return config('Front.URL') . '/embed/' . $survey->surveyId . "?employee_id=[employee_id_value]&lname=[lname]&fname=[fname]&depart=[depart]";        
    }

}