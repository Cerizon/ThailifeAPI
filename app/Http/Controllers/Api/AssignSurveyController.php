<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssignHistory;
use App\Models\AssignSeller;
use App\Models\AssignSurvey;
use App\Models\EmployeeSurvey;
use App\Models\SurveyAvailableDay;
use App\Models\Surveys;
use App\Models\customer;
use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;

class AssignSurveyController extends Controller
{

    public function assignHistory($id)
    {
        $histories = AssignHistory::where('survey_id', $id)->get();

        return response()->json($histories);
    }

    public function assignSurvey(Request $request)
    {
        $data = $request->all();
        $id = AssignSurvey::create($request->all());
        //dd($data,$id);
        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function assignSurveyUpdate(Request $request, $id)
    {
        AssignSurvey::where('surveyId', $id)->update($request->all());

        return response()->json([
            "message" => "Updated.",
        ], 200);
    }

    public function assignDoMoreSurvey(Request $request, $id)
    {
        AssignSurvey::where('surveyId', $id)->update($request->all());

        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function GetSurveyAvailableDay($id)
    {
        $survey_avaliable = SurveyAvailableDay::where('surveyId', $id)->get();

        return response()->json($survey_avaliable);
    }

    public function getEmployeeSurvey($id)
    {
        $emp_survey = EmployeeSurvey::where('surveyId', $id)->get();

        return response()->json($emp_survey);
    }

    public function getEmployeeSurveyCount($id)
    {
        $count = DB::table('employee_survey')
            ->select('employeeId')->where('surveyId', $id)->groupBy('employeeId')->get()->count();

        return $count;
    }

    public function getEmployeeSurveys(){
        $EmployeeSurvey = EmployeeSurvey::get();
        return response()->json($EmployeeSurvey);
    }


    public function insertEmployeeSurvey(Request $request)
    {

        EmployeeSurvey::create($request->all());

        return;
    }

    public function insertSurveyAvailableDay(Request $request)
    {
        SurveyAvailableDay::create($request->all());

        return;
    }

    public function insertAssignHistory(Request $request)
    {
        AssignHistory::create($request->all());

        return;
    }

    public function insertAssignSeller(Request $request)
    {
        $id = AssignSeller::create($request->all())->id;

        return;
    }

    public function deleteSurvey($id)
    {
        Surveys::where('surveyId', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function deleteEmployeeSurvey($id)
    {
        EmployeeSurvey::where('surveyId', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function deleteSurveyAvailableDay($id)
    {
        SurveyAvailableDay::where('surveyId', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function deleteAssignHistory($id)
    {
        AssignHistory::where('survey_id', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function deleteAgentSurvey($id)
    {
        AssignSeller::where('survey_id', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

}
