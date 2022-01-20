<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agents;
use App\Models\AgentsMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AgentController extends Controller
{

    public function index()
    {
        $agents = Agents::all();
        return response()->json($agents);
    }

    public function store(Request $request)
    {
        $id = Agents::create($request->all())->id;

        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        if (Agents::where('identicstionNumber', $id)->exists()) {
            $agent              = Agents::where('identicstionNumber', $id)->first();
            $agent->phoneNumber = is_null($request->phoneNumber) ? $agent->phoneNumber : $request->phoneNumber;
            $agent->save();

            return response()->json([
                "message" => "records update successfully : " . $request->phoneNumber,
            ], 200);
        } else {
            return response()->json([
                "message" => "Not found",
            ], 404);

        }
    }

    public function destroy($id)
    {
        Agents::where('identicstionNumber', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function export()
    {
        $agents = Agents::all('identicstionNumber', 'firstName', 'lastName', 'department', 'phoneNumber', 'createDate');
        // return response()->json($agents);
        return response($agents);
    }

    public function login(Request $request)
    {
        $res      = [];
        $username = $request->username;
        $password = $request->password;
        $data = AgentsMember::where('identicstionNumber', $username)->first();

        if ($data) {

            if (Hash::check($password, $data->password)) {
                $res["status"]    = "true";
                $res["firstName"] = $data->firstName;
                $res["lastName"]  = $data->lastName;
                $res["level"]     = $data->level;
                return response($res);
            } else {
                $res["status"] = false;
                return response($res);
            }
        } else {
            $res["status"] = false;
            return response($res);
        }
    }

}