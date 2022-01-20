<?php

namespace App\Http\Controllers;

use App\Http\Utils\ThailifeAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{

    static $CODE_SURVEY_NEW        = 1;
    static $CODE_SURVEY_INCOMPLETE = 2;

    public function getSurveys($request)
    {
        $surveys = DB::table('employee_survey as emp_sur')
            ->join('surveys', 'surveys.surveyId', '=', 'emp_sur.surveyId')
            ->join('survey_available_day as sad', 'emp_sur.surveyId', '=', 'sad.surveyId')
            ->get();
        $responses = $this->transformSurveyResponse($surveys, false, $request);
        return $responses;
    }

    public function getSurveyByIdTL(Request $request)
    {
        $accessAuth      = $request->input('accessAuth');
        $sellId          = $request->input('sellId');
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

            $surveys = DB::table('employee_survey as emp_sur')
                ->where('employeeId', $sellId)
                ->join('surveys', 'surveys.surveyId', '=', 'emp_sur.surveyId')
                ->join('survey_available_day as sad', 'emp_sur.surveyId', '=', 'sad.surveyId')
                ->whereRaw('surveys.available = 1 AND surveys.isPublic = 0 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
                ->get();

            $surveysPublic = DB::table('surveys')
                ->join('survey_available_day as sad', 'surveys.surveyId', '=', 'sad.surveyId')
                ->whereRaw('surveys.available = 1 AND surveys.isPublic = 1 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
                ->get();

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

    public function getSurveyById(Request $request, $id = null)
    {
        if ($id == null) {
            $accessAuth      = $request->input('accessAuth');
            $sellId          = $request->input('sellId');
            $configAuthToken = config('token')['Authorization_Token'];
            if ($accessAuth !== $configAuthToken || empty($accessAuth) || empty($sellId)) {
                return response()->json([
                    "error"  => "invalid authentication!! or required field",
                    "status" => "401",
                ], 401);
            } else {
                $surveys = DB::table('employee_survey as emp_sur')
                    ->where('employeeId', $sellId)
                    ->join('surveys', 'surveys.surveyId', '=', 'emp_sur.surveyId')
                    ->join('survey_available_day as sad', 'emp_sur.surveyId', '=', 'sad.surveyId')
                    ->get();
                $responses = $this->transformSurveyResponse($surveys, false, $request);
                return response()->json($responses);
            }
        } else if ($id !== null) {
            $surveys = DB::table('surveys as s')
                ->where('s.surveyId', $id)
                ->join('survey_available_day as sad', 'sad.surveyId', '=', 's.surveyId')
                ->get();
            $responses = $this->transformSurveyResponse($surveys, true, $request);
            return $responses;
        }
        return null;
    }

    public function getSeller(Request $request)
    {
        var_dump($request->input());
        die();
        $res = $this->FindSellFromFilter($request->input('filter'), $request->input('value'));
        return response()->json($res);
    }

    private function FindSellFromFilter($filter, $value = [])
    {
        $tl   = new ThailifeAPI(); // connect thai-life api
        $sell = null;

        if ($filter == 'all_da') {
            $sell = $tl->getAllSell();
        } elseif ($filter == 'member_dna') {
            $sell = $tl->getAllMember();
        }
        if ($sell->totalRecord > 0) {
            return ['status' => true, 'sells' => $sell];
        }
        // Not Found Sell
        return ['status' => false, 'found' => $sell->totalRecord];
    }

    public function getAlertNewSurvey(Request $request)
    {
        $accessAuth      = $request->input('accessAuth');
        $configAuthToken = config('token')['Authorization_Token'];
        $sellId          = $request->input('sellId');
        if ($accessAuth !== $configAuthToken || empty($accessAuth) || empty($sellId)) {
            return response()->json([
                "error"  => "invalid authentication!! or required field",
                "status" => "401",
            ], 401);
        } else {
            $surveys = DB::table('employee_survey as emp_sur')
                ->where([
                    ['employeeId', '=', $sellId],
                    ['emp_sur.completedAt', '=', null],
                    ['status', '=', 0],
                    ['is_alert', '=', 1],
                ])
                ->join('surveys', 'surveys.surveyId', '=', 'emp_sur.surveyId')
                ->whereRaw('surveys.available = 1 AND surveys.isPublic = 0 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
                ->get();
            $surveysPublic = DB::table('surveys')
                ->whereRaw('surveys.available = 1 AND surveys.isPublic = 1 AND surveys.start_date <= NOW() AND surveys.end_date >= NOW()')
                ->get();
            $allSurveys = [];
            foreach ($surveys as $s) {
                $allSurveys[] = $s;
            }
            foreach ($surveysPublic as $s) {
                $allSurveys[] = $s;
            }
            $responses = $this->transformSurveyAlertResponse($allSurveys, $request);
            return response()->json($responses);
        }
    }

    public function setNoAlert(Request $request)
    {
        $accessAuth      = $request->input('accessAuth');
        $configAuthToken = config('token')['Authorization_Token'];
        $sellId          = $request->input('sellId');
        $surveyId        = $request->input('surveyId');
        if ($accessAuth !== $configAuthToken) {
            return response()->json([
                "error"  => "invalid authentication!! or required field",
                "status" => "401",
            ], 401);
        } else {
            $update = DB::table('employee_survey')
                ->where([
                    ['employeeId', '=', $sellId],
                    ['surveyId', '=', $surveyId],
                ])
                ->update(['is_alert' => 0]);
            if (!$update) {
                return response()->json(["status" => "Nothing to update!!"]);
            }
            return response()->json(["status" => "Ignore this surrvey successful!!"]);
        }
    }
    
    private function getSurveyEmbedUrl($survey, Request $request)
    {
        $url  = route('api.embed', ['id' => $survey->surveyId]) . "?employee_id=[employee_id_value]&lname=[lname]&fname=[fname]&depart=[depart]";
        $url2 = str_replace("http://", "https://", $url);
        return $url2;
    }

    private function transformSurveyAlertResponse($surveys, $request)
    {
        $responses = [];
        foreach ($surveys as $survey) {
            $res = [
                'surveyId'        => $survey->surveyId,
                'surveyName'      => $survey->title,
                'surveyIframeUrl' => $this->getSurveyEmbedUrl($survey, $request),
                'pdfUrl'          => $survey->surveyPdfUrl,
            ];
            $message = '';
            $code    = 0;
            if (empty($survey->percentComplete)) {
                $survey->percentComplete = 0;
            }
            if ($survey->percentComplete == 0) {
                $code    = ApiController::$CODE_SURVEY_NEW;
                $message = 'New survey';
            }
            if ($survey->percentComplete > 0 && $survey->percentComplete < 100) {
                $code    = ApiController::$CODE_SURVEY_INCOMPLETE;
                $message = 'Not finished survey';
            }
            $res['code'] = $code;
            $res['msg']  = $message;
            $responses[] = $res;
        }
        return $responses;
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
        // then we generate the new array for output
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

    public function getHistory(Request $request)
    {
        $employeeId = $request->input('employeeId');
        $month      = $request->input('month');
        $year       = $request->input('year');

        $start_date = Carbon::createFromDate(($year - 543), $month, 1)->startOfMonth();
        $end_date   = (clone $start_date)->endOfMonth();

        $data = DB::table('employee_survey')
            ->selectRaw('title, start_date, end_date, status')
            ->where('employeeId', $employeeId)
            ->whereDate('employee_survey.created_at', '>=', $start_date)
            ->whereDate('employee_survey.created_at', '<=', $end_date)
            ->join('surveys', 'surveys.surveyId', '=', 'employee_survey.surveyId')
            ->where(function ($query) {
                $query->where('surveys.available', 0)
                    ->orWhere('surveys.end_date', '<=', Carbon::now());
            })
            ->orderBy('employee_survey.created_at')
            ->get();

        return $data;
    }

    public function updateSurveyPriority(Request $request)
    {
        $total = DB::table('surveys')->get()->count();
        foreach ($request->updateData as $key => $value) {
            $survey = DB::table('surveys')->where('id', $value['id'])->update([
                'priority' => ($total - $value['order'] + 1),
            ]);
        }

        return "";
    }
}