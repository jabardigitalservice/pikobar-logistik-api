<?php

namespace App;

use DB;
use App\LogisticRealizationItems;
use Illuminate\Http\Request;

class Tracking extends Agency
{
    static function selectFieldsList()
    {
        return [
            'id',
            'agency_id',
            DB::raw('applicant_name as request'),
            DB::raw('verification_status'),
            DB::raw('approval_status'),
            DB::raw('verification_status as verification'),
            DB::raw('approval_status as approval'),
            DB::raw('FALSE as delivering'), // Waiting for Integration data with POSLOG
            DB::raw('FALSE as delivered'), // Waiting for Integration data with POSLOG
            DB::raw('concat(approval_status, "-", verification_status) as status'),
            DB::raw('concat(approval_status, "-", verification_status) as statusDetail'),
            DB::raw('IFNULL(approval_note, note) as reject_note')
        ];
    }

    static function selectFieldsDetail()
    {
        return $select = [
            DB::raw('IFNULL(logistic_realization_items.id, needs.id) as id'),
            'needs.id as need_id',
            'logistic_realization_items.id as realization_id',

            'needs.product_id as need_product_id',
            'products.name as need_product_name',
            'needs.brand as need_description',
            'needs.quantity as need_quantity',
            'needs.unit as need_unit_id',
            'master_unit.unit as need_unit_name',
            'needs.usage as need_usage',
            'products.category',

            'logistic_realization_items.product_id as recommendation_product_id',
            'logistic_realization_items.product_name as recommendation_product_name',
            'logistic_realization_items.realization_quantity as recommendation_quantity',
            'realization_unit as recommendation_unit_name',
            'logistic_realization_items.recommendation_at',
            'logistic_realization_items.status as recommendation_status',

            'logistic_realization_items.final_product_id',
            'logistic_realization_items.final_product_name',
            'logistic_realization_items.final_quantity',
            'logistic_realization_items.final_unit',
            'logistic_realization_items.final_date',
            'logistic_realization_items.final_status'
        ];
    }

    static function getJoin($data, $isByAdmin)
    {
        $joinType = $isByAdmin ? 'left' : 'right';
        return $data->join('needs', 'logistic_realization_items.need_id', '=', 'needs.id', $joinType)
        ->join('products', 'needs.product_id', '=', 'products.id', 'left')
        ->join('master_unit', 'needs.unit', '=', 'master_unit.id', 'left')
        ->join('wms_jabar_material', 'logistic_realization_items.product_id', '=', 'wms_jabar_material.material_id', 'left');
    }

    static function getLogisticRequest($select, $request, $id)
    {
        $data = LogisticRealizationItems::select($select);
        $data = self::getJoin($data, false);
        if ($request->filled('final_status')) {
            $data = $data->whereIn('final_status', ['approved', 'replaced']);
        }
        return $data->orderBy('needs.id')->where('needs.agency_id', $id);
    }

    static function getLogisticAdmin($select, $request, $id)
    {
        $data = LogisticRealizationItems::select($select);
        $data = self::getJoin($data, true);
        if ($request->filled('final_status')) {
            $data = $data->whereIn('final_status', ['approved', 'replaced']);
        }
        return $data->whereNotNull('logistic_realization_items.created_by')
            ->orderBy('logistic_realization_items.id')
            ->where('logistic_realization_items.agency_id', $id);
    }

    static function trackList(Request $request)
    {
        $list = Tracking::with([
            'tracking' => function ($query) {
                $query->select(Tracking::selectFieldsList())->active();
            }
        ])->whereHas('applicant', function ($query) use ($request) {
            $query->when($request->input('search'), function ($query) use ($request)  {
                $query->where('agency_id', '=', $request->input('search'));
                $query->orWhere('email', '=', $request->input('search'));
                $query->orWhere('primary_phone_number', '=', $request->input('search'));
                $query->orWhere('secondary_phone_number', '=', $request->input('search'));
            });
        })
        ->getDefaultWith()
        ->whereHasApplicant($request)
        ->orderBy('agency.created_at', 'desc')->limit(5)->get();

        return $list;
    }

    public function tracking()
    {
        return $this->hasOne('App\Applicant', 'agency_id', 'id');
    }
}
