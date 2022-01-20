<?php

namespace App\Http\Controllers;

use App\Models\Surveys;
use Illuminate\Http\Request;
use FFI\Exception;
use Illuminate\Support\Str;

class QuestionaryController extends Controller
{

    public function JsonOut($status, $msg, $surveyId)
    {
        return Response('{ "status" : "' . $status . '" , "msg" : "' . $msg . '", "surveyId" : "' . $surveyId . '" } ');
    }

    public function index(Request $request)
    {
        try {
            $data = Surveys::select('survey_url', 'type_surveys','surveyId')->get();
            foreach ($data as $index => $val) {
                $strcompare = Str::between($val["survey_url"], "/r/", "?");
                if ($strcompare == $request->id) {
                    $check = $data[$index];
                    break;
                }
            }

            if ($data) {
                if (!isset($check)) {
                    return $this->JsonOut("error", "Not found", "-");
                }
                if ($check['type_surveys'] == "แบบสอบถามสำหรับตัวแทน") {
                    return $this->JsonOut("success", "agent", $check['surveyId']);
                }
                if ($check['type_surveys'] == "แบบสอบถามสำหรับลูกค้า") {
                    return $this->JsonOut("success", "customer", $check['surveyId']);
                }
            }
            return $this->JsonOut("error", "Error not know", "-");
        } catch (Exception $e) {
            return $this->JsonOut("error", $e, "-");
        }
    }
}
