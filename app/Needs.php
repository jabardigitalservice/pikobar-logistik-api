<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use DB;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Needs extends Model
{
    use SoftDeletes;

    const STATUS = [
        'Rendah',
        'Menengah',
        'Tinggi',
    ];

    protected $fillable = [
        'agency_id',
        'applicant_id',
        'product_id',
        'item',
        'brand',
        'quantity',
        'unit',
        'usage',
        'priority',
        'created_by'
    ];

    static function getFields()
    {
        $data = array_merge(self::needFields(), self::recommendationFields(), self::realizationFields());
        return self::select($data);
    }

    static function needFields()
    {
        $data = [
            'needs.id',
            'needs.agency_id',
            'needs.applicant_id',
            'needs.product_id',
            'needs.item',
            'needs.brand',
            'needs.quantity',
            'needs.unit',
            'needs.unit as unit_id',
            'needs.usage',
            'needs.priority',
            'needs.created_at',
            'needs.updated_at',

            'logistic_realization_items.need_id',
            'logistic_realization_items.material_group',
            'logistic_realization_items.status',
            'logistic_realization_items.created_by',
            'logistic_realization_items.final_by',
            'logistic_realization_items.updated_by'
        ];
        return $data;
    }

    static function recommendationFields()
    {
        $data = [
            'logistic_realization_items.product_id as recommendation_product_id',
            'logistic_realization_items.product_name as recommendation_product_name',
            'logistic_realization_items.realization_quantity as recommendation_quantity',
            'logistic_realization_items.realization_unit as recommendation_unit',
            'logistic_realization_items.realization_date as recommendation_date',
            'logistic_realization_items.status as recommendation_status', 
            'logistic_realization_items.realization_unit as recommendation_unit_id',
            'logistic_realization_items.recommendation_by',
            'logistic_realization_items.recommendation_at'
        ];

        return $data;
    }

    static function realizationFields()
    {
        $data = [
            'logistic_realization_items.final_product_id as realization_product_id',
            'logistic_realization_items.final_product_name as realization_product_name',
            'logistic_realization_items.final_quantity as realization_quantity',
            'logistic_realization_items.final_unit as realization_unit',
            'logistic_realization_items.final_date as realization_date',
            'logistic_realization_items.final_status as realization_status',
            'logistic_realization_items.final_unit_id as realization_unit_id',
            'logistic_realization_items.final_by as realization_by',
            'logistic_realization_items.final_at as realization_at'
        ];

        return $data;
    }

    static function getListNeed($data, $request)
    {
        return $data->with([
            'product' => function ($query) {
                return $query->select(['id', 'name', 'category']);
            },
            'unit' => function ($query) {
                return $query->select(['id', 'unit']);
            },
            'verifiedBy' => function ($query) {
                return $query->select(['id', 'name', 'agency_name', 'handphone']);
            },
            'recommendBy' => function ($query) {
                return $query->select(['id', 'name', 'agency_name', 'handphone']);
            },
            'realizedBy' => function ($query) {
                return $query->select(['id', 'name', 'agency_name', 'handphone']);
            }
        ])
        ->join(DB::raw('(select * from logistic_realization_items where deleted_at is null) logistic_realization_items'), 'logistic_realization_items.need_id', '=', 'needs.id', 'left')
        ->orderBy('needs.id')
        ->where('needs.agency_id', $request->agency_id);
    }

    public function agency()
    {
        return $this->belongsToMany('App\Agency', 'id', 'agency_id');
    }

    public function product()
    {
        return $this->hasOne('App\Product', 'id', 'product_id');
    }

    public function unit()
    {
        return $this->hasOne('App\MasterUnit', 'id', 'unit');
    }

    public function masterUnit()
    {
        return $this->hasOne('App\MasterUnit', 'id', 'unit');
    }

    public function verifiedBy()
    {
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function recommendBy()
    {
        return $this->hasOne('App\User', 'id', 'recommendation_by');
    }

    public function realizedBy()
    {
        return $this->hasOne('App\User', 'id', 'realization_by');
    }

    public function applicant()
    {
        return $this->belongsTo('App\Applicant');
    }

    static function listNeed(Request $request)
    {        
        $limit = $request->input('limit', 3);
        $data = Needs::getFields();
        $data = Needs::getListNeed($data, $request)->paginate($limit);
        $logisticItemSummary = Needs::where('needs.agency_id', $request->agency_id)->sum('quantity');
        $data->getCollection()->transform(function ($item, $key) use ($logisticItemSummary) { 
            if (!$item->realization_product_name) {
                $product = Product::where('id', $item->realization_product_id)->first();
                $item->realization_product_name = $product ? $product->name : '';
            }
            $item->status = !$item->status ? 'not_approved' : $item->status;
            $item->logistic_item_summary = (int)$logisticItemSummary;
            return $item;
        });
        $response = response()->format(Response::HTTP_OK, 'success', $data);
        return $response;
    }

    public function scopeFilterByApplicant($query, $request)
    {
        return $query->whereHas('applicant', function($query) use ($request) {
            $query->active()
                ->createdBetween($request)
                ->where('verification_status', Applicant::STATUS_VERIFIED)
                ->filter($request);
        });
    }
}
