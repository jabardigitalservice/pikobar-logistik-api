<?php

namespace App\Http\Controllers\API\v1;

use App\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Tracking;
use App\Needs;
use App\Outbound;
use App\OutboundDetail;
use DB;

class TrackController extends Controller
{
    /**
     * Track Function
     * Show application list based on ID, No. HP, or applicant email
     * @param Request $request
     * @return array of Applicant $data
     */
    public function index(Request $request)
    {
        $list = Tracking::trackList($request);
        $data = [
            'total' => count($list),
            'application' => $list
        ];
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    /**
     * Track Detail function
     * - return data is pagination so it can receive the parameter limit, page, sorting and filtering / searching
     * @param Request $request
     * @param integer $id
     * @return array of Applicant $data
     */
    public function show(Request $request, $id)
    {
        $limit = $request->input('limit', 3);
        $select = Tracking::selectFieldsDetail();
        $logisticRealizationItems = Tracking::getLogisticAdmin($select, $request, $id); //List of item(s) added from admin
        $data = Tracking::getLogisticRequest($select, $request, $id); //List of updated item(s)
        $data = $data->union($logisticRealizationItems)->paginate($limit);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function request(Request $request, $id)
    {
        $limit = $request->input('limit', 3);
        $applicant = Applicant::where('agency_id', $id)->active()->first();
        $items = Needs::select(
                    'needs.id',
                    'needs.product_id',
                    'needs.brand as description',
                    'needs.quantity',
                    'needs.usage',
                    'master_unit.unit as unit_name',
                    'products.material_group',
                    'products.name as product_name'
                )
                ->join('master_unit', 'master_unit.id', '=', 'needs.unit')
                ->join('products', 'products.id', '=', 'needs.product_id')
                ->where('needs.agency_id', $id)
                ->orderBy('needs.id')
                ->paginate($limit);

        return [
            'status' => $applicant->tracking_status,
            'items' => $items
        ];
    }

    public function getItems(Request $request, $id)
    {
        $applicant = Applicant::where('agency_id', $id)->active()->first();
        $select = $this->setSelect($request);

        $logisticAdmin = Tracking::getLogisticAdmin($select, $request, $id); //List of item(s) added from admin
        $data = Tracking::getLogisticRequest($select, $request, $id); //List of updated item(s)

        $status = $applicant->tracking_status;
        $data = $this->whereByStatus($request, $logisticAdmin, $data);

        return [
            'status' => $status,
            'items' => $data
        ];
    }

    public function setSelect($request)
    {
        $select = [
            DB::raw('IFNULL(logistic_realization_items.id, needs.id) as id'),
            'needs.id as need_id',
            'logistic_realization_items.id as realization_id',
            'logistic_realization_items.created_by',
            'products.category'
        ];

        if ($request->route()->named('recommendation')) {
            $select[] = 'logistic_realization_items.product_id as product_id';
            $select[] = 'logistic_realization_items.product_name as product_name';
            $select[] = 'logistic_realization_items.realization_quantity as quantity';
            $select[] = 'realization_unit as unit_name';
            $select[] = 'logistic_realization_items.created_at';
            $select[] = 'logistic_realization_items.status as status';
        } else {
            $select[] = 'logistic_realization_items.final_product_id as product_id';
            $select[] = 'logistic_realization_items.final_product_name as product_name';
            $select[] = 'logistic_realization_items.final_quantity as quantity';
            $select[] = 'logistic_realization_items.final_unit as unit_name';
            $select[] = 'logistic_realization_items.final_date as created_at';
            $select[] = 'logistic_realization_items.final_status as status';
        }

        return $select;
    }

    public function whereByStatus($request, $logisticAdmin, $data)
    {
        $limit = $request->input('limit', 3);
        if ($request->route()->named('finalization')) {
            $logisticAdmin = $logisticAdmin->whereIn('final_status', ['approved', 'replaced'])
                        ->whereNotNull('logistic_realization_items.final_date');

            $data = $data->whereIn('final_status', ['approved', 'replaced'])
                ->whereNotNull('logistic_realization_items.final_date');
        } else {
            $logisticAdmin = $logisticAdmin->whereIn('logistic_realization_items.status', ['approved', 'replaced'])
                        ->whereNotNull('logistic_realization_items.recommendation_at');

            $data = $data->whereIn('logistic_realization_items.status', ['approved', 'replaced'])
                ->whereNotNull('logistic_realization_items.recommendation_at');
        }

        return $data->union($logisticAdmin)->paginate($limit);
    }
}
