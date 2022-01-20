<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentsMember;
use App\Models\EmployeeSurvey;
use Facade\FlareClient\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AgentsSurvey;

class AgentsMemberController extends Controller
{
    public function AgentDetail($id)
    {
        $data = AgentsMember::where("identicstionNumber", $id)->get();
        return response()->json($data);
    }

    public function getByDepart($depart)
    {
        $data = AgentsMember::where("department", $depart)->get();
        return response()->json($data);
    }

    public function getSurvey($id)
    {
        // Get all survey fo agent
        $sql = "SELECT * FROM agents_survey agentSurvey
            inner join surveys survey on 'agentSurvey.surveyId' = 'survey.surveyId'
            inner join agents_member agents_member on 'agentSurvey.agentIdenNumber' = 'agents_member.identicstionNumber'
            where 'agentIdenNumber' = '" . $id . "' and available = 1 and 'survey.end_date' > '" . date("Y-m-d H:i:s") . "'";

        $survey = DB::select(DB::raw($sql));
        // $survey = AgentsSurvey
        //     ::join('surveys', 'agents_survey.surveyId', '=', 'surveys.surveyId')
        //     ->join('agents_member', 'agents_survey.agentIdenNumber', '=', 'agents_member.identicstionNumber')
        //     ->where('agentIdenNumber', '=', $id)
        //     ->where('available', '=', 1)
        //     ->where('end_date', '>', date("Y-m-d H:i:s"))
        //     ->get();
        // return Response($survey);
        // return response()->json($survey);
    }



    public function SearchAgent($idenNumber = null)
    {
        if ($idenNumber != null) {
            $matchAgents = AgentsMember::select('id', 'identicstionNumber', 'firstName', 'lastName', 'department', 'phoneNumber', 'createDate')->where('identicstionNumber', $idenNumber)->get();
        } else {

            $matchAgents = AgentsMember::select('id', 'identicstionNumber', 'firstName', 'lastName', 'department', 'phoneNumber', 'createDate')->get();
        }

        if (count($matchAgents) > 0) {
            return json_encode(array(
                'status' => 'success',
                'data'   => json_encode($matchAgents),
                'lenght' => count($matchAgents),
            ));
        } else {
            return json_encode(array(
                'status'       => 'notFound',
                'dataNotMatch' => (empty($idenNumber)) ? false : true,
            ));
        }
    }

    public function RegisterAgent(Request $request)
    {
        if (!AgentsMember::where('identicstionNumber', $request->identicstionNumber)->exists()) {
            $agent                     = new AgentsMember();
            $agent->identicstionNumber = $request->identicstionNumber;
            $agent->password           = $request->password;
            $agent->firstName          = $request->firstName;
            $agent->lastName           = $request->lastName;
            $agent->department         = $request->department;
            $agent->phoneNumber        = $request->phoneNumber;
            $agent->rawPassword        = $request->rawPassword;
            $agent->save();
            return response()->json([
                'status' => 'success',
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
            ], 404);
        }
    }

    public function UpdatePassword(Request $request)
    {
        $status = AgentsMember::where('identicstionNumber', $request->identicstionNumber)->update($request->all());

        if ($status) {
            return response()->json([
                "message" => "Update complate",
            ], 200);
        } else {
            return response()->json([
                "message" => "Update error",
            ], 200);
        }
    }

    public function SurveyList($id)
    {
        $sql = "SELECT * FROM agents_survey agentSurvey
        inner join surveys survey on agentSurvey.surveyId = survey.surveyId
        inner join agents_member agents_member on agentSurvey.agentIdenNumber = agents_member.identicstionNumber
        where agentIdenNumber = '" . $id . "' and available = 1 and survey.end_date > date(Now())";

        $survey = DB::select(DB::raw($sql));

        return json_encode($survey);
    }

    public function EmpSurvey($sid, $empid)
    {
        $data = EmployeeSurvey::where(['surveyId' => $sid, 'employeeId' => $empid])->get();
        return json_encode($data);
    }
}
