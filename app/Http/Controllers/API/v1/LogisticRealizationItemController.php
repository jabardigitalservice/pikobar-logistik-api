<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\LogisticRealizationItems;
use App\Validation;
use DB;
use JWTAuth;
use App\Applicant;
use App\Enums\LogisticRealizationItemsStatusEnum;
use App\PoslogProduct;
use Log;

class LogisticRealizationItemController extends Controller
{
    public function store(Request $request)
    {
        $params = [
            'agency_id' => 'numeric',
            'applicant_id' => 'numeric',
            'need_id' => 'numeric',
            'status' => 'string'
        ];
        $cleansingData = $this->cleansingData($request, $params);
        $params = $cleansingData['param'];
        $request = $cleansingData['request'];
        $response = Validation::validate($request, $params);
        if ($response->getStatusCode() === 200) {
            if ($this->isApplicantExists($request, 'store')) {
                try {
                    $model = new LogisticRealizationItems();
                    $findOne = LogisticRealizationItems::where('need_id', $request->need_id)->orderBy('created_at', 'desc')->first();
                    $resultset = $this->setValue($request, $findOne);
                    $findOne = $resultset['findOne'];
                    $request = $resultset['request'];
                    $model->fill($request->input());
                    $model->save();
                    if ($findOne) { //updating latest log realization record
                        $findOne->realization_ref_id = $model->id;
                        $findOne->deleted_at = date('Y-m-d H:i:s');
                        $findOne->save();
                    }
                    $response = response()->format(200, 'success', $model);
                } catch (\Exception $exception) { //Return Error Exception
                    $response = response()->format(400, $exception->getMessage());
                }
            }
        }
        Log::channel('dblogging')->debug('post:v1/logistic-request/realization', $request->all());
        return $response;
    }

    public function add(Request $request)
    {
        $params = [
            'agency_id' => 'numeric', 
            'applicant_id' => 'numeric', 
            'product_id' => 'string',
            'usage' => 'string',
            'priority' => 'string',
            'status' => 'string'
        ];
        $cleansingData = $this->cleansingData($request, $params);
        $params = $cleansingData['param'];
        $request = $cleansingData['request'];
        $response = Validation::validate($request, $params);        
        if ($response->getStatusCode() === 200) {
            $applicant = Applicant::select('id')->where('id', $request->applicant_id)->where('agency_id', $request->agency_id)->first();
            //Get Material from PosLog by Id
            $request = $this->getPosLogData($request);
            $realization = $this->realizationStore($request);
            $response = response()->format(200, 'success');
        }
        return $response;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $params = [
            'agency_id' => 'required'
        ];
        $response = Validation::validate($request, $params);        
        if ($response->getStatusCode() === 200) {
            $limit = $request->input('limit', 3);
            $data = LogisticRealizationItems::getList($request);
            $response = response()->format(200, 'success', $data);
        }
        return $response;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $params = [
            'agency_id' => 'numeric',
            'product_id' => 'string',
            'status' => 'string'
        ];
        $cleansingData = $this->cleansingData($request, $params);
        $params = $cleansingData['param'];
        $request = $cleansingData['request'];
        $response = Validation::validate($request, $params);        
        if ($response->getStatusCode() === 200) {
            $response = $this->isValidStatus($request);
            if ($response->getStatusCode() === 200) {
                DB::beginTransaction();
                try {
                    $request['applicant_id'] = $request->input('applicant_id', $request->input('agency_id'));
        
                    //Get Material from PosLog by Id
                    $request = $this->getPosLogData($request);
                    $realization = $this->realizationUpdate($request, $id);

                    $data = array( 
                        'realization' => $realization
                    );
                    DB::commit();
                    $response = response()->format(200, 'success', $data);
                } catch (\Exception $exception) {
                    DB::rollBack();
                    $response = response()->format(400, $exception->getMessage());
                }
            }
        }
        return $response;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = LogisticRealizationItems::deleteData($id);
        return response()->format($result['code'], $result['message'], $result['data']);
    }

    // Utilities Function Below Here

    public function realizationStore($request)
    {   
        $store_type = $this->setStoreType($request);
        return LogisticRealizationItems::storeData($store_type);
    }

    public function realizationUpdate($request, $id)
    { 
        $findOne = LogisticRealizationItems::find($id);
        if ($findOne) {                
            //updating latest log realization record
            if ($request->input('store_type') === 'recommendation') {
                $store_type = [  
                    'agency_id' => $request->input('agency_id'),
                    'applicant_id' => $request->input('applicant_id'),
                    'product_id' => $request->input('product_id'), 
                    'product_name' => $request->input('product_name'), 
                    'realization_unit' => $request->input('recommendation_unit'), 
                    'material_group' => $request->input('material_group'), 
                    'realization_quantity' => $request->input('recommendation_quantity'),
                    'realization_date' => $request->input('recommendation_date'),
                    'status' => $request->input('status'),
                    'updated_by' => JWTAuth::user()->id,
                    'recommendation_by' => JWTAuth::user()->id,
                    'recommendation_at' => date('Y-m-d H:i:s')
                ];
            } else {
                $store_type['final_product_id'] = $request->input('product_id');
                $store_type['final_product_name'] = $request->input('product_name');
                $store_type['final_quantity'] = $request->input('realization_quantity');
                $store_type['final_unit'] = $request['realization_unit'];
                $store_type['final_date'] = $request->input('realization_date');
                $store_type['final_status'] = $request->input('status');
                $store_type['final_by'] = JWTAuth::user()->id;
                $store_type['final_at'] = date('Y-m-d H:i:s');
            }

            $findOne->fill($store_type);  
            $findOne->save();
        }
        return $findOne;
    }

    public function getPosLogData($request)
    {
        $material = PoslogProduct::where('material_id', $request->product_id)->first();
        if ($material) {
            $request['product_name'] = $material->material_name;
            $request['realization_unit'] = $material->uom;
            $request['material_group'] = $material->matg_id;
        }
        return $request;
    }

    public function setValue($request, $findOne)
    {
        unset($request['id']);
        if ($request->input('status') !== LogisticRealizationItemsStatusEnum::not_available()) {
            //Get Material from PosLog by Id
            $request = $this->getPosLogData($request);
        } else {
            unset($request['realization_unit']);
            unset($request['material_group']);
            unset($request['realization_quantity']);
            unset($request['unit_id']);
            unset($request['realization_date']);
        }
        $request = LogisticRealizationItems::setValue($request, $findOne);
        $result = [
            'request' => $request, 
            'findOne' => $findOne
        ];
        return $result;
    }
    
    public function isApplicantExists($request, $method)
    {
        $applicantCheck = Applicant::where('verification_status', '=', Applicant::STATUS_VERIFIED);
        $applicantCheck = $applicantCheck->where('id', $request->applicant_id);
        $applicantCheck = $applicantCheck->where('agency_id', $request->agency_id);
        return $applicantCheck->exists();
    }

    public function setStoreType($request)
    {        
        $store_type['need_id'] = $request->input('need_id');
        $store_type['agency_id'] = $request->input('agency_id');
        $store_type['applicant_id'] = $request->input('applicant_id');
        $store_type['created_by'] = JWTAuth::user()->id;
        $store_type['final_product_id'] = $request->input('product_id');
        $store_type['final_product_name'] = $request->input('product_name');
        $store_type['final_quantity'] = $request->input('realization_quantity');
        $store_type['final_unit'] = $request['realization_unit'];
        $store_type['final_date'] = $request->input('realization_date');
        $store_type['final_status'] = $request->input('status');
        $store_type['final_by'] = JWTAuth::user()->id;
        $store_type['final_at'] = date('Y-m-d H:i:s');
        if ($request->input('store_type') === 'recommendation') {
            $store_type = [  
                'need_id' => $request->input('need_id'), 
                'agency_id' => $request->input('agency_id'), 
                'applicant_id' => $request->input('applicant_id'), 
                'product_id' => $request->input('product_id'), 
                'product_name' => $request->input('product_name'), 
                'realization_unit' => $request->input('recommendation_unit'), 
                'material_group' => $request->input('material_group'), 
                'realization_quantity' => $request->input('recommendation_quantity'),
                'realization_date' => $request->input('recommendation_date'),
                'status' => $request->input('status'),
                'created_by' => JWTAuth::user()->id,
                'recommendation_by' => JWTAuth::user()->id,
                'recommendation_at' => date('Y-m-d H:i:s')
            ];
        }
        return $store_type;
    }
    
    public function cleansingData($request, $param)
    {
        $extra = [
            'realization_quantity' => 'numeric',
            'realization_date' => 'date',
        ];
        if ($request->input('store_type') === 'recommendation') {
            $extra = [
                'recommendation_quantity' => 'numeric',
                'recommendation_date' => 'date',
                'recommendation_unit' => 'string',
            ];
        }
        $param = array_merge($extra, $param);
        if ($this->isStatusNoNeedItem($request->status)) {
            unset($param['recommendation_date']);
            unset($param['recommendation_quantity']);
            unset($param['recommendation_unit']);
            unset($param['realization_date']);
            unset($param['realization_quantity']);

            unset($request['product_id']);
            unset($request['product_name']);
            unset($request['realization_unit']);
            unset($request['material_group']);
            unset($request['recommendation_date']);
            unset($request['recommendation_quantity']);
            unset($request['recommendation_unit']);
            unset($request['realization_date']);
            unset($request['realization_quantity']);
        }
        
        $result = [
            'request' => $request, 
            'param' => $param
        ];
        return $result;
    }

    public function isStatusNoNeedItem($status)
    {
        return ($status === LogisticRealizationItemsStatusEnum::not_available() || $status === LogisticRealizationItemsStatusEnum::not_yet_fulfilled());
    }

    public function isValidStatus($request)
    {
        $response = response()->format(200, 'success');
        if (!in_array($request->status, LogisticRealizationItems::STATUS)) {
            $response = response()->json(['status' => 'fail', 'message' => 'verification_status_value_is_not_accepted']);
        }
        return $response;
    }
}
