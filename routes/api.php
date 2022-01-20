<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::apiResource('admin', 'App\Http\Controllers\Api\AdminController');
    Route::apiResource('agent', 'App\Http\Controllers\Api\AgentController');
    Route::apiResource('agent-survey', 'App\Http\Controllers\Api\AgentSurveyController');
    Route::apiResource('branch', 'App\Http\Controllers\Api\BranchController');
    Route::apiResource('group', 'App\Http\Controllers\Api\GroupController');
    Route::apiResource('position', 'App\Http\Controllers\Api\PositionController');
    Route::apiResource('survey', 'App\Http\Controllers\Api\SurveyController');

    Route::get('assign/history/{id}', 'App\Http\Controllers\Api\AssignSurveyController@assignHistory');
    Route::get('export/agent', 'App\Http\Controllers\Api\AgentController@export');
    Route::get('getSurveyAvailableDay/{id}', 'App\Http\Controllers\Api\AssignSurveyController@GetSurveyAvailableDay');
    Route::get('getEmployeeSurvey/{id}', 'App\Http\Controllers\Api\AssignSurveyController@getEmployeeSurvey');
    Route::get('getEmployeeSurveyCount/{id}', 'App\Http\Controllers\Api\AssignSurveyController@getEmployeeSurveyCount');
    Route::get('getSurvey', 'App\Http\Controllers\Api\SurveyMonkeyController@getSurvey');
    Route::get('webhook/{id}', 'App\Http\Controllers\Api\SurveyMonkeyController@WebHook');
    Route::post('log-survey', 'App\Http\Controllers\Api\SurveyMonkeyController@LogCreateSurvey');

    Route::post('insert', 'App\Http\Controllers\Api\AssignSurveyController@insertPost');
    Route::post('insertEmployeeSurvey', 'App\Http\Controllers\Api\AssignSurveyController@insertEmployeeSurvey');
    Route::post('insertSurveyAvailableDay', 'App\Http\Controllers\Api\AssignSurveyController@insertSurveyAvailableDay');
    Route::post('insertAssignHistory', 'App\Http\Controllers\Api\AssignSurveyController@insertAssignHistory');
    Route::post('insertAssignSeller', 'App\Http\Controllers\Api\AssignSurveyController@insertAssignSeller');
    Route::post('update/{id}', 'App\Http\Controllers\Api\AssignSurveyController@updatePost');

    Route::post('assign/survey', 'App\Http\Controllers\Api\AssignSurveyController@assignSurvey');
    Route::post('assign/survey/update/{id}', 'App\Http\Controllers\Api\AssignSurveyController@assignSurveyUpdate');
    Route::post('assign/domoresurvey/{id}', 'App\Http\Controllers\Api\AssignSurveyController@assignDoMoreSurvey');

    Route::get('survey/detail/{id}', 'App\Http\Controllers\Api\SurveyController@getSurveyDetail');
    Route::get('survey/check/{id}', 'App\Http\Controllers\Api\SurveyController@checkSurvey');
    Route::get('survey/send/{id}', 'App\Http\Controllers\Api\SurveyController@getSurveySend');
    Route::get('prioritymax', 'App\Http\Controllers\Api\SurveyController@getSurveyPriorityMax');
    Route::get('agent-survey/check/{surveyId}/{agentIdenNumber}', 'App\Http\Controllers\Api\AgentSurveyController@check');
    Route::get('agent-member/{depart}', 'App\Http\Controllers\Api\AgentsMemberController@getByDepart');
    Route::get('agent/survey/{id}', 'App\Http\Controllers\Api\AgentsMemberController@getSurvey');
    Route::get('agent-member-search/{idenNumber?}', 'App\Http\Controllers\Api\AgentsMemberController@SearchAgent');
    Route::post('agent-member-register', 'App\Http\Controllers\Api\AgentsMemberController@RegisterAgent');
    Route::post('agent-member/updatepassword', 'App\Http\Controllers\Api\AgentsMemberController@UpdatePassword');
    Route::get('agent-member/surveylist/{id}', 'App\Http\Controllers\Api\AgentsMemberController@SurveyList');
    Route::get('agent-member/detail/{id}', 'App\Http\Controllers\Api\AgentsMemberController@AgentDetail');
    Route::get('agent-member/emp-survey/{sid}/{empid}', 'App\Http\Controllers\Api\AgentsMemberController@EmpSurvey');

    Route::delete('deleteSurvey/{id}', 'App\Http\Controllers\Api\AssignSurveyController@deleteSurvey');
    Route::delete('deleteEmployeeSurvey/{id}', 'App\Http\Controllers\Api\AssignSurveyController@deleteEmployeeSurvey');
    Route::delete('deleteSurveyAvailableDay/{id}', 'App\Http\Controllers\Api\AssignSurveyController@deleteSurveyAvailableDay');
    Route::delete('deleteAssignHistory/{id}', 'App\Http\Controllers\Api\AssignSurveyController@deleteAssignHistory');
    Route::delete('deleteAgentSurvey/{id}', 'App\Http\Controllers\Api\AssignSurveyController@deleteAgentSurvey');

    // Front
    Route::get('find-agent/{employeeId}/{surveyId}', 'App\Http\Controllers\Api\FrontController@findAgent');
    Route::get('surveyurl/{url}', 'App\Http\Controllers\Api\FrontController@getSurveyData');
    Route::get('getHistory/{employeeId}/{month?}/{year?}', 'App\Http\Controllers\Api\FrontController@showHistory');

    Route::get('empSurveys/{employeeId}', 'App\Http\Controllers\Api\FrontController@empSurveys');
    Route::get('empSurveysNotmulti/{employeeId}', 'App\Http\Controllers\Api\FrontController@empSurveysNotmulti');
    Route::get('publicSurveys', 'App\Http\Controllers\Api\FrontController@publicSurveys');
    Route::get('publicSurveysNotmulti', 'App\Http\Controllers\Api\FrontController@publicSurveysNotmulti');

    Route::post('admin/login', 'App\Http\Controllers\Api\AdminController@login');
    Route::post('agent-login', 'App\Http\Controllers\Api\AgentController@login');

    // From API from web da
    // Used front
    // Route::post('getSurveyById', 'App\Http\Controllers\Api\ApiController@getSurveyByIdTL'); // call from web DA
    Route::post('waitnew', 'App\Http\Controllers\Api\ApiController@getAlertNewSurvey');
    Route::post('setNoAlert', 'App\Http\Controllers\Api\ApiController@setNoAlert');
    Route::post('getSeller', 'App\Http\Controllers\Api\ApiController@getSeller')->name('get.seller');
    Route::post('getHistoryApi', 'App\Http\Controllers\Api\ApiController@getHistory'); // Change name
    Route::post('updateSurveyPriority', 'App\Http\Controllers\Api\ApiController@updateSurveyPriority');
});

// Backend used API from web da
Route::post('thailife/findagent', 'App\Http\Controllers\Api\ThailifeController@findagent');
Route::post('thailife/agentdata', 'App\Http\Controllers\Api\ThailifeController@AgentData');

// Front
Route::post('getSurveyById', 'App\Http\Controllers\Api\DaController@getSurveyByIdTL');
Route::post('getAllSurveys',  'App\Http\Controllers\Api\AllSurveyController@getAllSurveys');

// Front PDPA
Route::prefix('customer')->group(function () {
    Route::get('searchsms', 'App\Http\Controllers\Api\CustomersController@Search_sms');
    Route::post('updatesms', 'App\Http\Controllers\Api\CustomersController@Update_sms');
    Route::post('savesms', 'App\Http\Controllers\Api\CustomersController@Save_sms');
    Route::post('survey', 'App\Http\Controllers\Api\CustomersController@Save_customer');
    Route::post('sendsms', 'App\Http\Controllers\Api\CustomersController@SendSms');
    // Route::get('CorrectLinkId', 'App\Http\Controllers\Api\CustomersController@linkid')->name('customers.linkid');
});

Route::get('questionary', 'App\Http\Controllers\QuestionaryController@index');
