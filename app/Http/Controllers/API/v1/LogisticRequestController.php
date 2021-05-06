<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Needs;
use App\Agency;
use App\Applicant;
use App\LogisticRequest;
use App\FileUpload;
use App\Imports\LogisticImport;
use Maatwebsite\Excel\Facades\Excel;
use App\MasterFaskes;
use App\User;
use App\Validation;
use Log;
use JWTAuth;

class LogisticRequestController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $data = Agency::getList($request, false)->paginate($limit);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function finalList(Request $request)
    {
        $syncSohLocation = \App\PoslogProduct::syncSohLocation();
        $request->request->add(['verification_status' => Applicant::STATUS_VERIFIED]);
        $request->request->add(['approval_status' => Applicant::STATUS_APPROVED]);
        $request->request->add(['finalized_by' => Applicant::STATUS_FINALIZED]);
        // Cut Off Logistic Data
        $cutOffDateTimeState = \Carbon\Carbon::createFromFormat(config('wmsjabar.cut_off_format'), config('wmsjabar.cut_off_datetime'))->toDateTimeString();
        $cutOffDateTime = $request->input('cut_off_datetime', $cutOffDateTimeState);
        $today = \Carbon\Carbon::now()->toDateTimeString();

        $request->request->add(['start_date' => $cutOffDateTime]);
        $request->request->add(['end_date' => $today]);
        $logisticRequest = Agency::getList($request, false)->get();

        $data = [
            'data' => $logisticRequest,
            'total' => count($logisticRequest)
        ];
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function store(Request $request)
    {
        $request = $this->masterFaskesCheck($request);
        $responseData = LogisticRequest::responseDataStore();
        $param = LogisticRequest::setParamStore($request);
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $response = LogisticRequest::storeProcess($request, $responseData);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request', $request->all());
        return $response;
    }

    public function update(Request $request, $id)
    {
        $param['agency_id'] = 'required';
        $param['applicant_id'] = 'required';
        $param['update_type'] = 'required';
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $response = LogisticRequest::saveData($request);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('put:v1/logistic-request', $request->all());
        return $response;
    }

    public function show(Request $request, $id)
    {
        $data = Agency::getList($request, false)
                        ->with('letter:id,agency_id,letter')
                        ->where('agency.id', $id)
                        ->firstOrFail();

        $response = response()->format(Response::HTTP_OK, 'success', $data);

        $isNotAdmin = !in_array(JWTAuth::user()->roles, User::ADMIN_ROLE);
        $isDifferentDistrict = $data->location_district_code != JWTAuth::user()->code_district_city;
        if ($isNotAdmin && $isDifferentDistrict) {
            $response = response()->format(Response::HTTP_UNAUTHORIZED, 'Permohonan anda salah, Anda tidak dapat membuka alamat URL tersebut');
        }
        return $response;
    }

    public function listNeed(Request $request)
    {
        $param = ['agency_id' => 'required'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $response = Needs::listNeed($request);
        }
        return $response;
    }

    public function import(Request $request)
    {
        $param = ['file' => 'required|mimes:xlsx'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $response = LogisticImport::importProcess($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/import', $request->all());
        return $response;
    }

    public function requestSummary(Request $request)
    {
        $requestSummaryResult['lastUpdate'] = Applicant::active()->createdBetween($request)->filter($request)->first();
        $requestSummaryResult['totalPikobar'] = Applicant::active()->createdBetween($request)->sourceData('pikobar')->filter($request)->count();
        $requestSummaryResult['totalDinkesprov'] = Applicant::active()->createdBetween($request)->sourceData(false)->filter($request)->count();
        $requestSummaryResult['totalUnverified'] = Applicant::active()->createdBetween($request)->unverified()->filter($request)->count();
        $requestSummaryResult['totalApproved'] = Applicant::active()->createdBetween($request)->approved()->filter($request)->count();
        $requestSummaryResult['totalFinal'] = Applicant::active()->createdBetween($request)->final()->filter($request)->count();
        $requestSummaryResult['totalVerified'] = Applicant::active()->createdBetween($request)->verified()->filter($request)->count();
        $requestSummaryResult['totalVerificationRejected'] = Applicant::active()->createdBetween($request)->verificationRejected()->filter($request)->count();
        $requestSummaryResult['totalApprovalRejected'] = Applicant::active()->createdBetween($request)->approvalRejected()->filter($request)->count();

        $data = Applicant::requestSummaryResult($requestSummaryResult);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function changeStatus(Request $request)
    {
        $param['agency_id'] = 'required|numeric';
        $param['applicant_id'] = 'required|numeric';
        $processType = 'verification';
        $changeStatusParam = $this->setChangeStatusParam($request, $param, $processType);
        $param = $changeStatusParam['param'];
        $processType = $changeStatusParam['processType'];
        $dataUpdate = $changeStatusParam['dataUpdate'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $response = LogisticRequest::changeStatus($request, $processType, $dataUpdate);
            Validation::setCompleteness($request);
        }

        Log::channel('dblogging')->debug('post:v1/logistic-request/' . $processType, $request->all());
        return $response;
    }

    public function setChangeStatusParam(Request $request, $param, $processType)
    {
        $dataUpdate = [];
        if ($request->route()->named('verification')) {
            $processType = 'verification';
            $param['verification_status'] = 'required|string';
            $param['note'] = $request->verification_status === Applicant::STATUS_REJECTED ? 'required' : '';
            $dataUpdate['verification_status'] = $request->verification_status;
            $dataUpdate['note'] = $request->verification_status === Applicant::STATUS_REJECTED ? $request->note : '';
        } else if ($request->route()->named('approval')) {
            $processType = 'approval';
            $param['approval_status'] = 'required|string';
            $param['approval_note'] = $request->approval_status === Applicant::STATUS_REJECTED ? 'required' : '';
            $dataUpdate['approval_status'] = $request->approval_status;
            $dataUpdate['approval_note'] = $request->approval_status === Applicant::STATUS_REJECTED ? $request->approval_note : '';
        } else {
            $processType = 'final';
            $param['approval_status'] = 'required|string';
            $param['approval_note'] = $request->approval_status === Applicant::STATUS_REJECTED ? 'required' : '';
            $dataUpdate['approval_status'] = $request->approval_status;
            $dataUpdate['approval_note'] = $request->approval_status === Applicant::STATUS_REJECTED ? $request->approval_note : '';
        }

        $changeStatusParam['param'] = $param;
        $changeStatusParam['processType'] = $processType;
        $changeStatusParam['dataUpdate'] = $dataUpdate;

        return $changeStatusParam;
    }

    public function stockCheking(Request $request)
    {
        $param = [
            'applicant_id' => 'required|numeric',
            'stock_checking_status' => 'required|string'
        ];
        $applicant = (Validation::validate($request, $param)) ? $this->updateApplicant($request) : null;
        return response()->format(Response::HTTP_OK, 'success', $applicant);
    }

    public function masterFaskesCheck($request)
    {
        return $request = (!MasterFaskes::find($request->master_faskes_id)) ? $this->alloableAgencyType($request) : $request;
    }

    public function alloableAgencyType($request)
    {
        $response = Validation::validateAgencyType($request->agency_type, ['4', '5']);
        if ($response->getStatusCode() === 200) {
            $param = [
                'agency_type' => 'required|numeric',
                'agency_name' => 'required|string'
            ];
            $response = Validation::validate($request, $param);
            if ($response->getStatusCode() === 200) {
                $masterFaskes = MasterFaskes::createFaskes($request);
                $request['master_faskes_id'] = $masterFaskes->id;
                $response = $request;
            }
        }
        return $response;
    }

    public function uploadLetter(Request $request, $id)
    {
        $param['letter_file'] = 'required|mimes:jpeg,jpg,png,pdf|max:10240';
        $param['agency_id'] = 'required';
        $param['applicant_id'] = 'required';
        $param['update_type'] = 'required';
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $applicant = Applicant::where('id', $request->applicant_id)->where('agency_id', $request->agency_id)->firstOrFail();
            $response = FileUpload::storeLetterFile($request);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/letter/' . $id, $request->all());
        return $response;
    }

    public function uploadApplicantFile(Request $request, $id)
    {
        $param = [
            'applicant_file' => 'required|mimes:jpeg,jpg,png,pdf|max:10240'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $request->request->add(['applicant_id' => $id]);
            $response = FileUpload::storeApplicantFile($request);
            $applicant = Applicant::where('id', '=', $request->applicant_id)->update(['file' => $response->id]);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/identity/' . $id, $request->all());
        return $response;
    }

    public function urgencyChange(Request $request)
    {
        $param = [
            'agency_id' => 'required|numeric',
            'applicant_id' => 'required|numeric',
            'is_urgency' => 'required|numeric',
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === 200) {
            $model = Applicant::where('id', $request->applicant_id)->where('agency_id', $request->agency_id)->first();
            $model->is_urgency = $request->is_urgency;
            $model->save();
            $response = response()->format(Response::HTTP_OK, 'success', $model);
            Validation::setCompleteness($request);
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/urgency', $request->all());
        return $response;
    }
}
