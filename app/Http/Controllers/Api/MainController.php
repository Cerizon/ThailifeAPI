<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Surveys;
use Illuminate\Http\Request;

class MainController extends Controller
{

    public function index()
    {
        $surveys = Surveys::all();
        return response()->json($surveys);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        $survey = Surveys::find($id);
        return response()->json($survey);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}