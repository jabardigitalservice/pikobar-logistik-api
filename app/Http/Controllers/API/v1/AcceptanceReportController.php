<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Validation;
use App\Agency;
use App\Applicant;
use DB;
use App\LogisticRealizationItems;
use App\AcceptanceReport;
use App\AcceptanceReportDetail;
use App\FileUpload;
use App\Http\Resources\AcceptanceReportResource;

class AcceptanceReportController extends Controller
{
    /**
     *
     * index function
     * get acceptance_reports table records
     *
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);
        $data = Agency::select('agency.id', 'agency.created_at')
                        ->final()
                        ->when($request->has('city_code'), function ($query) use ($request) {
                            $query->where('location_district_code', $request->input('city_code'));
                        })
                        ->with('applicant', 'AcceptanceReport')
                        ->leftJoin('acceptance_reports', 'agency.id', '=', 'acceptance_reports.agency_id')
                        ->searchReport($request)
                        ->orderBy('acceptance_reports.date', 'desc')
                        ->orderBy('agency.id', 'asc')
                        ->groupBy('acceptance_reports.agency_id', 'agency.id', 'agency.created_at', 'acceptance_reports.date')
                        ->paginate($limit);

        return response()->json($data);
    }

    /**
     * show function
     *
     * @param  Request $request
     * @return AcceptanceReport
     */
    public function show(Agency $acceptanceReport)
    {
        $acceptanceReport->load('applicant', 'AcceptanceReport');
        return new AcceptanceReportResource($acceptanceReport);
    }

    public function store(Request $request)
    {
        $param = AcceptanceReport::setParamStore();
        $response = Validation::validate($request, $param);
        abort_if($response->getStatusCode() != Response::HTTP_OK, $response);
        DB::beginTransaction();
        try {
            $acceptanceReport = $this->storeAcceptanceReport($request);
            $this->itemStore($request, $acceptanceReport);
            $request->merge(['acceptance_report_id' => $acceptanceReport->id]);
            // Upload Seluruh File
            $proof_pic = $this->uploadAcceptanceFile($request, 'proof_pic');
            $bast_proof = $this->uploadAcceptanceFile($request, 'bast_proof');
            $item_proof = $this->uploadAcceptanceFile($request, 'item_proof');
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success');
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'Error Insert Acceptance Report. Because ' . $exception->getMessage(), $exception->getTrace());
        }
        return $response;
    }

    public function storeAcceptanceReport($request)
    {
        $acceptanceReport = new AcceptanceReport;
        $acceptanceReport->fullname = $request->fullname;
        $acceptanceReport->position = $request->position;
        $acceptanceReport->phone = $request->phone;
        $acceptanceReport->date = $request->date;
        $acceptanceReport->officer_fullname = $request->officer_fullname;
        $acceptanceReport->note = $request->note;
        $acceptanceReport->feedback = $request->feedback;
        $acceptanceReport->agency_id = $request->agency_id;
        $acceptanceReport->save();
        return $acceptanceReport;
    }

    public function itemStore($request, $acceptanceReport)
    {
        foreach (json_decode($request->items, true) as $value) {
            $acceptanceReportDetail = new AcceptanceReportDetail;
            $acceptanceReportDetail->acceptance_report_id = $acceptanceReport->id;
            $acceptanceReportDetail->agency_id = $request->agency_id;
            $acceptanceReportDetail->logistic_realization_item_id = $value['id'];
            $acceptanceReportDetail->product_id = $value['product_id'];
            $acceptanceReportDetail->product_name = $value['name'];
            $acceptanceReportDetail->qty = $value['qty'];
            $acceptanceReportDetail->unit = $value['unit'];
            $acceptanceReportDetail->status = $value['status'];
            $acceptanceReportDetail->qty_ok = $value['qty_ok'];
            $acceptanceReportDetail->qty_nok = $value['qty_nok'];
            $acceptanceReportDetail->quality = $value['quality'];
            $acceptanceReportDetail->save();
        }
    }

    public function uploadAcceptanceFile($request, $paramName)
    {
        $file = [];
        for ($index = 0; $index < $request->input($paramName . '_length'); $index++) {
            if ($request->hasFile($paramName . $index)) {
                $file[] = FileUpload::uploadAcceptanceReportFile($request, $paramName, $index);
            }
        }

        return $file;
    }

    public function realizationLogisticList(Request $request, $id)
    {
        $select = [
            'logistic_realization_items.id as id',
            'logistic_realization_items.final_product_id as product_id',
            'logistic_realization_items.final_product_name as name',
            'logistic_realization_items.final_quantity as qty',
            'logistic_realization_items.final_unit as unit',
            'logistic_realization_items.final_status as status',
            DB::raw('0 as qty_ok'),
            DB::raw('0 as qty_nok')
        ];
        $data = LogisticRealizationItems::select($select)
        ->where('agency_id', $id)
        ->whereIn('logistic_realization_items.final_status', [LogisticRealizationItems::STATUS_REPLACED, LogisticRealizationItems::STATUS_APPROVED])
        ->get();
        $response = response()->format(Response::HTTP_OK, 'success', $data);
        return $response;
    }

    public function statistic(Request $request)
    {
        $alreadyReportedTotal = Agency::final()->has('acceptanceReport')
                                        ->when($request->has('city_code'), function ($query) use ($request) {
                                            $query->where('location_district_code', $request->input('city_code'));
                                        })
                                        ->count();
        $notYetReportedTotal = Agency::final()->doesntHave('acceptanceReport')
                                        ->when($request->has('city_code'), function ($query) use ($request) {
                                            $query->where('location_district_code', $request->input('city_code'));
                                        })
                                        ->count();
        $statistic = [
            'already_reported_total' => $alreadyReportedTotal,
            'not_yet_reported_total' => $notYetReportedTotal
        ];
        return response()->format(Response::HTTP_OK, 'success', $statistic);
    }
}
