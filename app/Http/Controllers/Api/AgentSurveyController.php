<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentsSurvey;
use Illuminate\Http\Request;

class AgentSurveyController extends Controller
{
    public function index()
    {
        $admins = AgentsSurvey::all();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $id = AgentsSurvey::create($request->all())->id;

        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function show($id)
    {
        $admin = AgentsSurvey::find($id);
        return response()->json($admin);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        AgentsSurvey::where('id', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function check($surveyId, $agentIdenNumber)
    {
        $surveyMatch = AgentsSurvey::where("surveyId", $surveyId)->where("agentIdenNumber", $agentIdenNumber)->get();

        if (count($surveyMatch) == 0) {
            return null;
        } else {
            return response()->json($surveyMatch);
        }
    }

}