<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::all();
        return response()->json($admins);
    }

    public function store(Request $request)
    {
        $id = Admin::create($request->all())->id;

        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function show($id)
    {
        $admin = Admin::find($id);
        return response()->json($admin);
    }

    public function destroy($id)
    {
        Admin::where('id', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }

    public function login(Request $request)
    {
        $res      = [];
        $username = $request->username;
        $password = $request->password;

        $data = Admin::where('username', $username)->first();

        if ($data) {
            if (Hash::check($password, $data->password)) {
                $res["status"]  = "true";
                $res["name"]    = $data->name;
                $res["surname"] = $data->surname;
                $res["email"]   = $data->email;
                $res["level"]   = $data->level;
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
