<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\DB;
use App\Http\Utils\SurveyMonkey;

class CronController extends Controller {

    public function updateResponsePercentAndComplete(Request $request) {
        $allUpdateData = [];
//        $surveyMonkey = new SurveyMonkey();
//        $surveys = $surveyMonkey->getSurveyResponse('130011300');
//        var_dump($surveys);

        // Find all survey of all surveys that not complete
        $surveys = DB::table('employee_survey as emp_sur')
                ->where([
                    ['status', '=', 0],
                ])
                ->select('surveyId')
                ->distinct()
                ->get();
                
        foreach ($surveys as $survey) {
            $surveyId = $survey->surveyId;
            $surveyMonkey = new SurveyMonkey();
            $surveyResponse = $surveyMonkey->getSurveyResponse($surveyId);
            if (empty($surveyResponse['error'])) { // Check only not error
                $allData = $surveyResponse['data'];
                foreach ($allData as $data) {
                    if (!empty($data['custom_variables']) && !empty($data['custom_variables']['employee_id'])) {
                        $employeeId = $data['custom_variables']['employee_id'];
                        $percent = 0;
                        $isFinish = 0;
                        $needUpdate = false;
                        $updateTime = $data['date_modified'];
                        if ($data['response_status'] == 'completed') {
                            $percent = 100;
                            $isFinish = 1;
                            $needUpdate = true;
                        } else if ($data['response_status'] == 'partial') {
                            $pages = $data['pages'];
                            $numAllPage = sizeof($pages);
                            $numFinishPage = 0;
                            foreach ($pages as $page) {
                                if (!empty($page['questions'])) {
                                    $numFinishPage = $numFinishPage + 1;
                                }
                            }
                            $percent = (int) ($numFinishPage / $numAllPage * 100 );
                            $isFinish = 0;
                            $needUpdate = true;
                        }
                        if ($needUpdate) {
                            $updateData = ['percentComplete' => $percent, 'status' => $isFinish];
                            if($isFinish == 1){
                                $updateData['completedAt'] = new \DateTime($updateTime);
                                $survey = DB::table('surveys as surveys')
                                ->where([
                                        ['surveyId', '=', $surveyId],
                                    ])->first();
                                if($survey->multiple_answer == 0){ // If is not multiple answer
                                    $updateData['is_alert'] = 0; // Should not use is_alert because admin can update the survey table later
                                }
                            }
                            $update = DB::table('employee_survey')
                                    ->where([
                                        ['employeeId', '=', $employeeId],
                                        ['surveyId', '=', $surveyId],
                                    ])
                                    ->update($updateData);
                            $updateData['employeeId'] = $employeeId;
                            $updateData['surveyId'] = $surveyId;
                            $allUpdateData[] = $updateData;

                        }
                    }
                }
            }
        }
        $response = [
            'status' => '200',
            'updatedData' => $allUpdateData
        ];
        return response()->json($response);
    }

}
