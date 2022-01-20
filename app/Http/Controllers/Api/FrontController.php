<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\AssignSeller;
use App\Models\Branch;
use App\Models\EmployeeSurvey;
use App\Models\Position;
use App\Models\Surveys;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FrontController extends Controller
{
    public function getSurveyData($url)
    {
        $survey = Surveys::where('survey_url', $url)
            ->where('surveys.start_date', '<=', Carbon::now())
            ->where('surveys.end_date', '>=', Carbon::now())
            ->orderBy('start_date', 'desc')
            ->first();

        if ($survey) {
            return response()->json($survey);
        } else {
            return response()->json("not found");
        }
    }

    public function showHistory($employeeId, $month = null, $year = null)
    {
        $datas = null;

        if (empty($employeeId)) {
            return "ผิดพลาด";
        }

        if (empty($month) || empty($year)) {
            $month = date('m');
            $year  = date('Y') + 543;
        }

        if (!empty($employeeId) && !empty($month) && !empty($year)) {
            $start_date = Carbon::createFromDate(($year - 543), $month, 1)->startOfMonth();
            $end_date   = (clone $start_date)->endOfMonth();

            $datas = DB::table('employee_survey')
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
        }

        return response()->json($datas);
    }

    public function empSurveys($employeeId) {                
        $datas = DB::table('employee_survey')
            ->select('surveys.*', 'employeeId')
            ->where('employeeId', $employeeId)
            ->join('surveys', 'employee_survey.surveyId', '=', 'surveys.surveyId')
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 0)
            ->where('surveys.multiple_answer', 1);
            // ->where('surveys.start_date', '<=', Carbon::now())
            // ->where('surveys.end_date', '>=', Carbon::now());            

        Log::info($datas->toSql(), $datas->getBindings());        

        return response()->json($datas);
    }

    public function empSurveysNotmulti($employeeId) {
        $datas = DB::table('employee_survey')
            ->select('surveys.*', 'employeeId')
            ->where('employeeId', $employeeId)
            ->join('surveys', 'employee_survey.surveyId', '=', 'surveys.surveyId')
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 0)
            ->where('surveys.multiple_answer', 0)
            ->where('employee_survey.status', 0);
            // ->where('surveys.start_date', '<=', Carbon::now())
            // ->where('surveys.end_date', '>=', Carbon::now())            
        
        return response()->json($datas);
    }

    public function publicSurveys() {
        $datas = DB::table('surveys')
            ->select(DB::raw('surveys.*, 1 as employeeId'))
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 1)
            ->where('surveys.multiple_answer', 1);
            // ->where('surveys.start_date', '<=', Carbon::now())
            // ->where('surveys.end_date', '>=', Carbon::now())            
        return response()->json($datas);
    }

    public function publicSurveysNotmulti() {
        $datas = DB::table('surveys')
            ->select(DB::raw('surveys.*'))
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 1)
            ->where('surveys.multiple_answer', 0);
            // ->where('surveys.start_date', '<=', Carbon::now())
            // ->where('surveys.end_date', '>=', Carbon::now())            
        return response()->json($datas);
    }

    public function findAgent($employeeId, $surveyId)
    {
        // เป็นเลขบัตรประชาชน
        if (strlen($employeeId) == 13) {
            $url    = 'http://da.thailife.com:8080/DaOperation/rest/public/findagent';
            $fields = [
                "perid"    => $employeeId,
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
                $employeeId = $result->data[0]->perid;
            }
        }

        $xml  = file_get_contents("http://da.thailife.com:8080/CaOperationTest/rest/member/saleinfo/${employeeId}");
        $data = json_decode($xml, true);

        $employee_survey = EmployeeSurvey::where('surveyId', $surveyId)->where('employeeId', $employeeId)->first();

        $survey_detail = Surveys::where('surveyId', $surveyId)->first();

        if ($employee_survey == null) {

            $assign_sellers = AssignSeller::where('survey_id', $surveyId)->get();

            foreach ($assign_sellers as $key => $value) {
                $assign_data = json_decode($value->query);

                $fields['perid'] = $employeeId;

                $fields = [];
                if (!empty($assign_data->position)) {
                    $fields['position'] = $assign_data->position;
                }
                if (!empty($assign_data->agerange)) {
                    $fields['agerange'] = $assign_data->agerange;
                }
                if (!empty($assign_data->brancode)) {
                    $fields['brancode'] = $assign_data->brancode;
                }

                $url      = 'http://da.thailife.com:8080/DaOperation/rest/public/findagentdetail';
                $postvars = http_build_query($fields);
                $ch       = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result = json_decode(curl_exec($ch));
                curl_close($ch);
                // dd($result);
                if ($result->totalRecord != 0) {
                    $user_data = $result->data[0];
                    // $user_data->strName    = DB::table("position")->where('value', $user_data->strid)->first()->position;
                    // $user_data->branchName = DB::table("branch")->where('code', (int) $user_data->branCode)->first()->branch;
                    $user_data->strName    = Position::where('value', $user_data->strid)->first()->position;
                    $user_data->branchName = Branch::where('code', (int) $user_data->branCode)->first()->branch;

                    return response()->json([
                        "status" => "found",
                        "data"   => $user_data,
                    ], 200);
                }
            }
        } else if ($employee_survey->status == 1 && $survey_detail->multiple_answer == 0) {
            return response()->json([
                "status" => "notallow",
            ], 200);
        } else if ($employee_survey->status == 0 || $survey_detail->multiple_answer == 1) {
            $url    = 'http://da.thailife.com:8080/DaOperation/rest/public/findagentdetail';
            $fields = array(
                'perid'    => $employeeId,
                'position' => "",
                'agerange' => "",
                'brancode' => "",
            );
            $postvars = http_build_query($fields);
            $ch       = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($fields));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            $result = json_decode($result);
            curl_close($ch);
            if ($result->totalRecord != 0) {
                $user_data = $result->data[0];
                // $user_data->strName    = DB::table("position")->where('value', $user_data->strid)->first()->position;
                // $user_data->branchName = DB::table("branch")->where('code', (int) $user_data->branCode)->first()->branch;
                $user_data->strName    = Position::where('value', $user_data->strid)->first()->position;
                $user_data->branchName = Branch::where('code', (int) $user_data->branCode)->first()->branch;

                return response()->json([
                    "status" => "found",
                    "data"   => $user_data,
                ], 200);
            }
        }

        return response()->json([
            "status" => "notfound",
            "data"   => [
                "employee_survey" => $employee_survey,
            ],
        ], 200);

    }

}