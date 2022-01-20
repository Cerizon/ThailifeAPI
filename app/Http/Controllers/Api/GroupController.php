<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::all();
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $id = Group::create($request->all())->id;

        return response()->json([
            "message" => "ID : " . $id,
        ], 200);
    }

    public function show($id)
    {
        $group = Group::find($id);
        return response()->json($group);
    }

    public function destroy($id)
    {
        Group::where('id', $id)->delete();

        return response()->json([
            "message" => "Delete complete",
        ], 200);
    }
}