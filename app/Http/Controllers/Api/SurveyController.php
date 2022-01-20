<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surveys;
use App\Models\AssignSurvey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{

    public function index()
    {
        //$surveys = Surveys::paginate(20);
        $surveys = Surveys::get();
        return response()->json($surveys);
    }

    public function show($id)
    {
        $survey = Surveys::find($id);
        return response()->json($survey);
    }

    public function update(Request $request, $id)
    {
        Surveys::whereId($id)->update($request->all());
    }

    public function destroy($id)
    {
        //
    }

    public function getSurveyDetail($id)
    {

        $survey = Surveys::where('surveyId', $id)->get();
        return response()->json($survey);
    }

    public function checkSurvey($id)
    {
        $survey = Surveys::where('surveyId', $id)->first();
        if ($survey) {
            return response("true");
        } else {
            return response("false");
        }
    }

    public function getSurveyPriorityMax()
    {
        $priority = Surveys::max('priority');
        return response()->json($priority);
    }

    public function getSurveySend($id)
    {
        $survey = Surveys::where('surveyId', $id)->first();
        return $survey->numSent;
    }
}
