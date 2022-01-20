<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AllSurveyController extends Controller
{

    // public function getAllSurveys($employee_id, $fname, $lname, $depart)
    public function getAllSurveys(Request $req)
    {  
        $employee_id = $req->employee_id;
        $fname = $req->fname;
        $lname = $req->lname;
        $depart = $req->depart;

        // Select survey by employee_id
        $empSurveys = DB::table('employee_survey')
            ->select('surveys.*', 'employeeId')            
            ->where('employeeId', $employee_id)            
            ->join('surveys', 'employee_survey.surveyId', '=', 'surveys.surveyId')        
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 0)
            ->where('surveys.start_date', '<=', Carbon::now())
            ->where('surveys.end_date', '>=', Carbon::now());        

        $empSurveys_notmulti = DB::table('employee_survey')
            ->select('surveys.*', 'employeeId')
            ->where('employeeId', $employee_id)
            ->join('surveys', 'employee_survey.surveyId', '=', 'surveys.surveyId')            
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 0)
            ->where('surveys.multiple_answer', 0)
            ->where('employee_survey.status', 0)
            ->where('surveys.start_date', '<=', Carbon::now())
            ->where('surveys.end_date', '>=', Carbon::now());

        // Select public Survey
        $publicSurveys = DB::table('surveys')            
            ->select('surveys.*')
            ->addSelect(DB::raw("'employeeId' as employeeId"))            
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 1)
            ->where('surveys.multiple_answer', 1)
            ->where('surveys.start_date', '<=', Carbon::now())
            ->where('surveys.end_date', '>=', Carbon::now());
   
        // dd($publicSurveys->get());   
        
        $surveyData = $empSurveys
            ->union($empSurveys_notmulti)
            ->union($publicSurveys)
            ->orderBy("priority", "desc")
            ->get();

        foreach ($surveyData as $key => $survey) {
            // $surveyData[$key]->embed = route('api.embed', ['id' => $survey->surveyId]) . "?employee_id=${employeeData["employee_id"]}&lname=${employeeData["lname"]}&fname=${employeeData["fname"]}&depart=${employeeData["depart"]}";
            $surveyData[$key]->embed = config('Front.URL') . "/embed/" . $survey->surveyId . "?employee_id=$employee_id&fname=$fname&lname=$lname&depart=$depart";
        }

        $publicSurveys_notmulti = DB::table('surveys')
            ->select(DB::raw('surveys.*'))
            ->where('surveys.available', 1)
            ->where('surveys.isPublic', 1)
            ->where('surveys.multiple_answer', 0)
            ->where('surveys.start_date', '<=', Carbon::now())
            ->where('surveys.end_date', '>=', Carbon::now())
            ->get();

        foreach ($publicSurveys_notmulti as $key => $survey) {
            if ($survey) {
                $emp_survey = DB::table('employee_survey')->where('surveyId', $survey->surveyId)->where('employeeId', $employee_id)->get();
                if ($emp_survey->count() == 0) {
                    // $survey->embed = route('api.embed', ['id' => $survey->surveyId]) . "?employee_id=${employeeData["employee_id"]}&lname=${employeeData["lname"]}&fname=${employeeData["fname"]}&depart=${employeeData["depart"]}";
                    $survey->embed = config('Front.URL') . "/embed/" . $survey->surveyId . "?employee_id=$employee_id&lname=$lname&fname=$fname&depart=$depart";
                    $surveyData[]  = $survey;
                } elseif ($emp_survey[0]->status == 0) {
                    // $survey->embed = route('api.embed', ['id' => $survey->surveyId]) . "?employee_id=${employeeData["employee_id"]}&lname=${employeeData["lname"]}&fname=${employeeData["fname"]}&depart=${employeeData["depart"]}";
                    $survey->embed = config('Front.URL') . "/embed/" . "?employee_id=$employee_id&lname=$lname&fname=$fname&depart=$depart";
                    $surveyData[]  = $survey;
                }
            }
        }
        
        return response($surveyData);
    }
}