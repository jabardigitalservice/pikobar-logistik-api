<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Validation;
use App\LogisticRequest;
use App\Applicant;
use Log;

class LogisticRequestStatusController extends Controller
{
    public function undoStep(Request $request)
    {
        $param = [
            'agency_id' => 'required|numeric',
            'applicant_id' => 'required|numeric',
            'step' => 'required',
            'url' => 'required'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $request = $this->undoStepCondition($request);
            $email = LogisticRequest::sendEmailNotification($request, $request['status']);
            $response = response()->format(Response::HTTP_OK, 'success', $request->all());
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/return', $request->all());
        return $response;
    }

    static function undoStepCondition($request)
    {
        $dataUpdate = [];
        switch ($request->step) {
            case 'final':
                $dataUpdate = Applicant::setNotYetFinalized($dataUpdate);
                $request['status'] = 'realisasi';
                break;
            case 'realisasi':
                $dataUpdate = Applicant::setNotYetApproved($dataUpdate);
                $request['status'] = 'rekomendasi';
                break;
            case 'ditolak rekomendasi':
                $dataUpdate = Applicant::setNotYetApproved($dataUpdate);
                $request['status'] = 'rekomendasi';
                break;
            default:
                $dataUpdate = Applicant::setNotYetVerified($dataUpdate);
                $request['status'] = 'surat';
                break;
        }
        $update = Applicant::updateApplicant($request, $dataUpdate);
        return $request;
    }
}
