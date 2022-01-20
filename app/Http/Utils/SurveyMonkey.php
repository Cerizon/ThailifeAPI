<?php

namespace App\Http\Utils;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Models\EmployeeSurvey;

class SurveyMonkey {

    static $API_KEY = 'a58RDx47hUg7P9xVKQMILZILwm2KuVYVPU1ODsQLk16JHLFK5RWJyQP0MtzUWYgKpJE-Y.0IVG369TITVUVApDrt4d4FKdjzkhiPmlRBW8tXP93DJLBguttaMARZ4krG';    
    static $BASE_URL = 'https://api.surveymonkey.net/v3';

    public function subscribeWebhookResponseComplete($surveyId) {
        $surveyIds = [$surveyId];
        $hookName = "survey" . URL::to('/') . "/survey/". $surveyId;
        $arrayObj = [
            "name" => $hookName,
            "event_type" => "response_completed",
            "object_type" => "survey",
            "object_ids" => $surveyIds,
            "subscription_url" => URL::to('/') . "/callback/surveyReceived?name=" .$hookName
        ];
        $result = $this->curlPostJson('/webhooks', $arrayObj);
        // Log::useDailyFiles(storage_path() . '/logs/callbackSubscribeWebhook.log');
        // Log::info($result);
        return $result;
    }

    public function getSurveys() {
        return $this->curlGet('/surveys');
    }

    public function getSurveyById($id) {
        return $this->curlGet('/surveys/' . $id);
    }

    public function getSurveyResponse($id) {
        return $this->curlGet('/surveys/' . $id . "/responses/bulk?per_page=100&page=1");
    }
    public function getSurveyResponseByPage($id, $page) {
        return $this->curlGet('/surveys/' . $id . "/responses/bulk?per_page=100&page=" . $page);
    }
    public function getDetails($id){
        return json_decode(json_encode($this->curlGet('/surveys/' . $id . "/details")));
    }

    private function checkFinishResponsesAndUpdate($responses, $surveyId){
        $KEY_FINISH_RESPONSE_IDS = "finish_response_id";
        $MIN_CACHE = 500000;
        foreach($responses->data as $surveyDetail){
            $customVars = $surveyDetail->custom_variables;
            $responseId = $surveyDetail->id;
            // $customVars['employee_id'] = '55510428';
            if (isset($customVars->employee_id)) {
                $employeeId = $customVars->employee_id;
                if(Cache::has($KEY_FINISH_RESPONSE_IDS)){
                   $allFinishes = Cache::get($KEY_FINISH_RESPONSE_IDS);
                   if(in_array($responseId,$allFinishes )){
                    // Continue from here 
                      $this->updateFinishSurvey($surveyId, $employeeId);
                      if (($key = array_search($responseId, $allFinishes)) !== false) {
                        unset($allFinishes[$key]);
                        Cache::put($KEY_FINISH_RESPONSE_IDS, $allFinishes, $MIN_CACHE);
                      }
                   }
                }
            }
        }
    }

    private function updateFinishSurvey($surveyId, $employeeId) {
        $queryObj = DB::table('employee_survey')
                ->where([
            ['employeeId', '=', $employeeId],
            ['surveyId', '=', $surveyId],
        ]);
        $surveys = $queryObj->get();
        if (sizeof($surveys) == 0) {
            $this->insertEmployeeSurvey($surveyId, $employeeId);
        }
        $queryObj->update(['percentComplete' => 100, 'status' => 1, 'completedAt' => new \DateTime()]);
    }

    private function insertEmployeeSurvey($surveyId, $employeeId) {
        $s = new EmployeeSurvey();
        $s->surveyId = $surveyId;
        $s->employeeId = $employeeId;
        $s->percentComplete = 10;
        $s->status = 1;
        $s->is_alert = 1;
        $s->completedAt =  new \DateTime();
        $s->save();
    }

    public function getSurveyResponseByEmployee($id, $employeeId = "") {

        $KEY_DATA_STABLE = "tlstable". $id;
        $KEY_DATA_NON_STABLE = "tlnonstable". $id;
        $KEY_NUM_TOTAL = "tlnumtotal". $id;
        $KEY_LAST_FULL_PAGE = "tllastfullpage". $id;
        $MIN_CACHE = 500000;

        $responses = json_decode(json_encode($this->getSurveyResponse($id)));
        $this->checkFinishResponsesAndUpdate($responses, $id);
        // var_dump($responses);
        // die();
        $result = array();

        $empPush = $employeeId;
        $allData = $responses->data;
        if(!isset($responses->data)){
          return false;
        }

        // Do the check
        if(!Cache::has($KEY_DATA_STABLE)){
          // init data
          Cache::put($KEY_LAST_FULL_PAGE, 0, $MIN_CACHE);
          Cache::put($KEY_NUM_TOTAL, $responses->total, $MIN_CACHE);
          Cache::put($KEY_DATA_NON_STABLE, $responses->data, $MIN_CACHE);
          Cache::put($KEY_DATA_STABLE, [], $MIN_CACHE);
          // find next response and save
          while(isset($responses->links->next) && $responses->links->next){
            Cache::put($KEY_DATA_STABLE, $allData, $MIN_CACHE);
            Cache::put($KEY_LAST_FULL_PAGE, $responses->page, $MIN_CACHE);
            $newLink = $responses->links->next;
            $responses = json_decode(json_encode($this->curlGetAbsolute($newLink)));
            Cache::put($KEY_DATA_NON_STABLE, $responses->data, $MIN_CACHE);
            $allData = array_merge($responses->data, $allData);
          }
        }
        else if(Cache::get($KEY_NUM_TOTAL) == $responses->total){
            $allData = array_merge(Cache::get($KEY_DATA_STABLE),Cache::get($KEY_DATA_NON_STABLE));
        }
        else{
          // fetch latest page
          $result = $this->getSurveyResponseByPage($id, Cache::get($KEY_LAST_FULL_PAGE) +1);
          $responses = json_decode(json_encode($result));
          $allData = array_merge(Cache::get($KEY_DATA_STABLE), $responses->data);
          while(isset($responses->links->next) && $responses->links->next){
            Cache::put($KEY_DATA_STABLE, $allData, $MIN_CACHE);
            Cache::put($KEY_LAST_FULL_PAGE, $responses->page, $MIN_CACHE);
            $newLink = $responses->links->next;
            $responses = json_decode(json_encode($this->curlGetAbsolute($newLink)));
            Cache::put($KEY_DATA_NON_STABLE, $responses->data, $MIN_CACHE);
            $allData = array_merge($responses->data, $allData);
          }
        }

        // stable data = array of full page
        // last full page = page number of last full
        // num total = contain the num total of response
        // non stable data = array of non full page

        // So, if num total not change, no need to fetch
        // if num total change, fetch strt from last page until no next
        // save last page
        // So, we array merge of stable and not stable

        //
	$result= [];
        foreach($allData as $key => $value){
            foreach($value->custom_variables as $key => $variable){
              if($key == 'employee_id' && $variable == $employeeId){
                array_push($result,$value);
              }
              /*  if(!empty($variable)){
                    if(!empty($employeeId)){
                        if($variable == $employeeId){
                            array_push($result,$value);
                        }
                    } else {
                        (empty($empPush)) ? $empPush .= $variable : $empPush .= ",".$variable;
                        array_push($result,$value);
                    }
                }*/
            }
        }
        return json_decode(json_encode(array("result"=>$result,"employeeId"=>$empPush)));
    }

    /**
     * Undocumented function
     * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      * !!!         warning         !!!
     * !! Don't touch this function !!
     * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     * @param [type] $id
     * @param [type] $pages
     * @return void
     */
    public function getSurveyAnswer($id, $responses, $details){
        // $pAns = array();
        // $dAns = array();
        $returnAnswer = array();

        foreach ($responses as $response){
            $pushResponse = array();
            $newPage = array();
            foreach($response->custom_variables as $variable){
                    $pushResponse["employeeId"] = $variable;
            }
            $pushResponse["title"] = $details->title;
            // $pushResponse["preview"] = $details->preview;
            $pushResponse["total_time"] = $response->total_time;
            $pushResponse["status"] = $response->response_status;
            $pushResponse["collector_id"] = $response->collector_id;
            $pushResponse["survey_id"] = $response->survey_id;
            $pushResponse["analyze_url"] = $response->analyze_url;
            $pushResponse["date_created"] = $response->date_created;

            foreach($response->pages as $keyPage => $page){
                foreach($details->pages as $keyDetail => $detail){
                    if($page->id == $detail->id){
                        $pushPage = array("pageId" => $page->id);
                        if (isset($detail->position)) {
                            $pushPage["pagePosition"] = $detail->position;
                        }

                        $newQuestion = array();
                        foreach($page->questions as $keyPageQuestion => $pageQuestion){
                            foreach($detail->questions as $keyDetailQuestion => $detailQuestion){
                                if($pageQuestion->id == $detailQuestion->id){
                                    $pushQuestion = array(
                                        "questionId" => $pageQuestion->id,
                                        "questionHeading" => $detailQuestion->headings,
                                        "questionFamily" => $detailQuestion->family,
                                        "questionSubtype" => $detailQuestion->subtype,
                                    );
                                    $newAnswer = array();
                                    $questionRowsText = $questionColsText = array();
                                    // var_dump($pageQuestion->answers);
                                    foreach($pageQuestion->answers as $keyPageAnswer => $pageAnswer){
                                        $pushAnswer = array();
                                            // if(is_array($pageAnswer) || is_object($pageAnswer)){
                                            //     array_push($pAns,$pageAnswer);
                                            // }

                                        // array_push($pAns,array($detailQuestion->family,$detailQuestion->subtype));
                                        switch($detailQuestion->family){
                                            case "open_ended" :
                                                switch($detailQuestion->subtype){
                                                    case 'single':
                                                        $pushAnswer = array(
                                                            'answerText' => $pageAnswer->text
                                                        );
                                                        // $pushAnswer = $pageAnswer->text;
                                                        break;
                                                    case 'essay':
                                                        # code...
                                                        $pushAnswer = array(
                                                            'answerText' => $pageAnswer->text
                                                        );
                                                        break;
                                                }
                                                break;
                                        }

                                        if(isset($detailQuestion->answers )){
                                            $choiceAnsMap = [];
                                            foreach($detailQuestion->answers as $keyDetailAnswer => $detailAnswer){
                                              foreach($detailAnswer as $keyDetailAnswerRow => $detailAnswerRow){
                                                if(is_object($detailAnswerRow) ){
                                                    $choiceAnsMap[$detailAnswerRow->id] = $detailAnswerRow->text;
                                                }

                                              }
                                            }
                                            foreach($detailQuestion->answers as $keyDetailAnswer => $detailAnswer){
                                                foreach($detailAnswer as $keyDetailAnswerRow => $detailAnswerRow){
                                                    if(is_array($detailAnswerRow) || is_object($detailAnswerRow)){
                                                        switch($detailQuestion->family){
                                                            case "single_choice" :
                                                                # code...
                                                                   if(isset($pageAnswer->choice_id)){
                                                                     $pushAnswer = array(
                                                                         'choiceId' => $pageAnswer->choice_id,
                                                                         'choiceText' => $choiceAnsMap[$pageAnswer->choice_id],
                                                                         // 'answerText' => $pageAnswer->text,
                                                                         'position' => $detailAnswerRow->position,
                                                                         'visible' => $detailAnswerRow->visible
                                                                     );
                                                                   }

                                                                   if(isset($pageAnswer->other_id)){
                                                                       // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                           $pushAnswer = array_merge($pushAnswer,array(
                                                                               'otherId' => $pageAnswer->other_id,
                                                                               // 'otherText' => $detailAnswerRow->text,
                                                                               'otherAnswer' => $pageAnswer->text,
                                                                           ));
                                                                       // }
                                                                   }

                                                                        // switch($detailQuestion->subtype){
                                                                        //     case 'vertical':
                                                                        //         # code...
                                                                        //         if(isset($pageAnswer->choice_id)){
                                                                        //             if($pageAnswer->choice_id == $detailAnswerRow->id){
                                                                        //                 $pushAnswer = array(
                                                                        //                     'choiceId' => $pageAnswer->choice_id,
                                                                        //                     'choiceText' => $choiceAnsMap[$pageAnswer->choice_id],
                                                                        //                     // 'answerText' => $pageAnswer->text,
                                                                        //                     'position' => $detailAnswerRow->position,
                                                                        //                     'visible' => $detailAnswerRow->visible
                                                                        //                 );
                                                                        //             }
                                                                        //         }
                                                                        //         if(isset($pageAnswer->other_id)){
                                                                        //             // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                        //                 $pushAnswer = array_merge($pushAnswer,array(
                                                                        //                     'otherId' => $pageAnswer->other_id,
                                                                        //                     // 'otherText' => $detailAnswerRow->text,
                                                                        //                     'otherAnswer' => $pageAnswer->text,
                                                                        //                 ));
                                                                        //             // }
                                                                        //         }
                                                                        //
                                                                        //         break;
                                                                        //     case 'horiz':
                                                                        //         # code...
                                                                        //         break;
                                                                        //     case 'menu':
                                                                        //         # code...
                                                                        //         if(isset($pageAnswer->choice_id)){
                                                                        //             $pushAnswer = array(
                                                                        //                 'choiceId' => $pageAnswer->choice_id,
                                                                        //                 'choiceText' => $choiceAnsMap[$pageAnswer->choice_id],
                                                                        //                 // 'answerText' => $pageAnswer->text,
                                                                        //                 'position' => $detailAnswerRow->position,
                                                                        //                 'visible' => $detailAnswerRow->visible
                                                                        //             );
                                                                        //         }
                                                                        //         if(isset($pageAnswer->other_id)){
                                                                        //             // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                        //                 $pushAnswer = array_merge($pushAnswer,array(
                                                                        //                     'otherId' => $pageAnswer->other_id,
                                                                        //                     // 'otherText' => $detailAnswerRow->text,
                                                                        //                     'otherAnswer' => $pageAnswer->text,
                                                                        //                 ));
                                                                        //             // }
                                                                        //         }
                                                                        //
                                                                        //         break;
                                                                        // }
                                                                break;
                                                            case "matrix" :
                                                                # code...
                                                                switch ($detailQuestion->subtype) {
                                                                    case 'single':
                                                                        # code...
                                                                        break;
                                                                    case 'rating':
                                                                        # code...Miss Other
                                                                        if(isset($pageAnswer->row_id)){
                                                                            if($pageAnswer->row_id == $detailAnswerRow->id){
                                                                                $rowArr = array(
                                                                                    'rowId' => $pageAnswer->row_id,
                                                                                    'rowText' => $detailAnswerRow->text,
                                                                                );
                                                                                $pushAnswer = $rowArr;
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->choice_id)){
                                                                            if($pageAnswer->choice_id == $detailAnswerRow->id){
                                                                                $choiceArr = array(
                                                                                    'choiceId' => $pageAnswer->choice_id,
                                                                                    'choiceText' => $detailAnswerRow->text,
                                                                                    'choicePosition' => $detailAnswerRow->position,
                                                                                    'choiceVisible' => $detailAnswerRow->visible
                                                                                );
                                                                                $pushAnswer = array_merge($pushAnswer,$choiceArr);
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->other_id)){
                                                                            // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                                $pushAnswer = array_merge($pushAnswer,array(
                                                                                    'otherId' => $pageAnswer->other_id,
                                                                                    // 'otherText' => $detailAnswerRow->text,
                                                                                    'otherAnswer' => $pageAnswer->text,
                                                                                ));
                                                                            // }
                                                                        }

                                                                        break;
                                                                    case 'ranking':
                                                                        # code...
                                                                        if(isset($pageAnswer->row_id)){
                                                                            if($pageAnswer->row_id == $detailAnswerRow->id){
                                                                                $rowArr = array(
                                                                                    'rowId' => $pageAnswer->row_id,
                                                                                    'rowText' => $detailAnswerRow->text,
                                                                                );
                                                                                $pushAnswer = $rowArr;
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->choice_id)){
                                                                            if($pageAnswer->choice_id == $detailAnswerRow->id){
                                                                                $choiceArr = array(
                                                                                    'choiceId' => $pageAnswer->choice_id,
                                                                                    'choiceText' => $detailAnswerRow->text,
                                                                                    'choicePosition' => $detailAnswerRow->position,
                                                                                    'choiceVisible' => $detailAnswerRow->visible
                                                                                );
                                                                                $pushAnswer = array_merge($pushAnswer,$choiceArr);
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->other_id)){
                                                                            // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                                $pushAnswer = array_merge($pushAnswer,array(
                                                                                    'otherId' => $pageAnswer->other_id,
                                                                                    // 'otherText' => $detailAnswerRow->text,
                                                                                    'otherAnswer' => $pageAnswer->text,
                                                                                ));
                                                                            // }
                                                                        }
                                                                        break;
                                                                    case 'menu':
                                                                        # code...Miss Other
                                                                        if(isset($pageAnswer->row_id)){
                                                                            if($pageAnswer->row_id == $detailAnswerRow->id){
                                                                                if(!in_array($detailAnswerRow->text,$questionRowsText)) array_push($questionRowsText,$detailAnswerRow->text);
                                                                                $rowArr = array(
                                                                                    'rowId' => $pageAnswer->row_id,
                                                                                    'rowText' => $detailAnswerRow->text,
                                                                                );
                                                                                $pushAnswer = $rowArr;
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->col_id)){
                                                                            if($pageAnswer->col_id == $detailAnswerRow->id){
                                                                                if(!in_array($detailAnswerRow->text,$questionColsText)) array_push($questionColsText,$detailAnswerRow->text);
                                                                                $colArr = array(
                                                                                    'choiceId' => $pageAnswer->choice_id,
                                                                                    'colText' => $detailAnswerRow->text,
                                                                                    'colPosition' => $detailAnswerRow->position,
                                                                                    'colVisible' => $detailAnswerRow->visible
                                                                                );

                                                                                $pushAnswer = array_merge($pushAnswer,$colArr);

                                                                                foreach($detailAnswerRow->choices as $choice){
                                                                                    if(isset($pageAnswer->choice_id)){
                                                                                        if($choice->id == $pageAnswer->choice_id){
                                                                                            $choiceArr = array(
                                                                                                'choiceId' => $choice->id,
                                                                                                'choiceVisible' => $choice->visible,
                                                                                                'choiceIs_na' => $choice->is_na,
                                                                                                'choiceText' => $choice->text,
                                                                                                'choicePosition' =>  $choice->position
                                                                                            );
                                                                                            $pushAnswer = array_merge($pushAnswer,$choiceArr);
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                        if(isset($pageAnswer->other_id)){
                                                                            // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                                $pushAnswer = array_merge($pushAnswer,array(
                                                                                    'otherId' => $pageAnswer->other_id,
                                                                                    // 'otherText' => $detailAnswerRow->text,
                                                                                    'otherAnswer' => $pageAnswer->text,
                                                                                ));
                                                                            // }
                                                                        }
                                                                        break;
                                                                    case 'multi':
                                                                        # code...
                                                                        break;
                                                                }
                                                                break;
                                                            case "open_ended" :
                                                                switch($detailQuestion->subtype){
                                                                    case 'multi':
                                                                            if(isset($pageAnswer->row_id)){
                                                                                if($pageAnswer->row_id == $detailAnswerRow->id){
                                                                                    $pushAnswer = array(
                                                                                        'rowId' => $pageAnswer->row_id,
                                                                                        'rowText' => $detailAnswerRow->text,
                                                                                        'answerText' => $pageAnswer->text,
                                                                                        'position' => $detailAnswerRow->position,
                                                                                        'visible' => $detailAnswerRow->visible
                                                                                    );
                                                                                }
                                                                            }
                                                                        break;
                                                                    case 'numerical':
                                                                        # code...
                                                                        break;


                                                                }
                                                                break;
                                                            case "demographic" :
                                                                # code...
                                                                break;
                                                            case "datetime" :
                                                                # code...
                                                                break;
                                                            case "multiple_choice" :
                                                                # code...Success
                                                                if(isset($pageAnswer->choice_id)){
                                                                    if($pageAnswer->choice_id == $detailAnswerRow->id){
                                                                        $pushAnswer = array(
                                                                            'choiceId' => $pageAnswer->choice_id,
                                                                            'choiceText' => $detailAnswerRow->text,
                                                                            // 'answerText' => $pageAnswer->text,
                                                                            'position' => $detailAnswerRow->position,
                                                                            'visible' => $detailAnswerRow->visible
                                                                        );
                                                                    }
                                                                }
                                                                if(isset($pageAnswer->other_id)){
                                                                    // if($pageAnswer->other_id == $detailAnswerRow->id){
                                                                        $pushAnswer = array_merge($pushAnswer,array(
                                                                            'otherId' => $pageAnswer->other_id,
                                                                            // 'otherText' => $detailAnswerRow->text,
                                                                            'otherAnswer' => $pageAnswer->text,
                                                                        ));
                                                                    // }
                                                                }
                                                                break;
                                                            case "presentation" :
                                                                # code...
                                                                break;
                                                        }

                                                    }
                                                        // array_push($dAns,$detailAnswerRow);
                                                        // foreach($detailAnswerRow as $keyDetailAnswerSubLast => $detailAnswerSubLast){

                                                        // }
                                                }
                                            }
                                        }
                                        array_push($newAnswer,$pushAnswer);
                                    }
                                    // if($detailQuestion->family == "presentation"){

                                    // }
                                    $pushQuestion["questionAnswer"] = $newAnswer;
                                    if(!empty($questionRowsText)) $pushQuestion["questionRowsText"] = $questionRowsText;
                                    if(!empty($questionColsText)) $pushQuestion["questionColsText"] = $questionColsText;
                                    array_push($newQuestion,$pushQuestion);
                                }
                            }
                        }
                        $pushPage["questions"] = $newQuestion;
                        array_push($newPage,$pushPage);

                    }
                }
            }
            $pushResponse["pages"] = $newPage;

            array_push($returnAnswer,$pushResponse);
        }
        return json_decode(json_encode(Array(
            // "pageAnswer" => $pAns,
            "responseAnswer" => $returnAnswer,
            // "detailAnswer" => $dAns
        )));
    }

    public function getSurveyCollectors($id) {
        return $this->curlGet('/surveys/' . $id . "/collectors");
    }

    public function getSurveyResponseById($surveyId, $responseId) {
        return $this->curlGet('/surveys/' . $surveyId . "/responses/" .$responseId );
    }

    private function curlPostJson($path, $payloadArray) {
        // Log::useDailyFiles(storage_path() . '/logs/requestSubscribeWebhook.log');
        $requestHeaders = array(
            'Content-Type: application/json',
            'Authorization: bearer ' . SurveyMonkey::$API_KEY,
        );
        $url = SurveyMonkey::$BASE_URL . $path;
        Log::info("curlPostJson - " .$url);
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        $payload = json_encode( $payloadArray);
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $payload );

        $result = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($http_status == '502'){
            die("Survey Monkey API is down. Please wait 30 minutes and try again");
        }
        curl_close($curl);
        return json_decode($result, true);
    }

    private function curlGet($path) {
        $e = new \Exception;
        
        $requestHeaders = array(
            'Content-Type: application/json',
            'Authorization: bearer ' . SurveyMonkey::$API_KEY,
        );
        $url = SurveyMonkey::$BASE_URL . $path;

        // Log::useDailyFiles(storage_path() . '/logs/surveymonkeyApi.log');
        // Log::info("curlGet - " . $url . $e->getTraceAsString());

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        
        $result = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($http_status == '502'){
            die("Survey Monkey API is down. Please wait 30 minutes and try again");
        }
        curl_close($curl);
        return json_decode($result, true);
    }

    private function curlGetAbsolute($path) {
        $requestHeaders = array(
            'Content-Type: application/json',
            'Authorization: bearer ' . SurveyMonkey::$API_KEY,
        );
        $curl = curl_init();
        // Log::useDailyFiles(storage_path() . '/logs/surveymonkeyApi.log');
        Log::info("curlGetAbsolute - " . $url);
        curl_setopt($curl, CURLOPT_URL, $path);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);

        $result = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if($http_status == '502'){
            die("Survey Monkey API is down. Please wait 30 minutes and try again");
        }
        curl_close($curl);
        return json_decode($result, true);
    }
}