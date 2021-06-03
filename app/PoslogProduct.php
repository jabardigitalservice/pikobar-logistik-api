<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoslogProduct extends Model
{
    const API_POSLOG = 'WMS_JABAR_BASE_URL';
    const DEFAULT_UOM = 'PCS';
    const DEFAULT_STOCK = 0;

    protected $fillable = [
        'material_id', 'material_name', 'soh_location', 'soh_location_name', 'uom', 'matg_id', 'stock_ok', 'stock_nok'
    ];

    public function setUomAttribute($value)
    {
        return $value ? $value : self::DEFAULT_UOM;
    }

    public function setStockOkAttribute($value)
    {
        return $value ? $value : self::DEFAULT_STOCK;
    }

    public function setStockNokAttribute($value)
    {
        return $value ? $value : self::DEFAULT_STOCK;
    }

    public function getUomAttribute($value)
    {
        return $value ? $value : self::DEFAULT_UOM;
    }

    public function getStockOkAttribute($value)
    {
        return $value ? number_format($value, 0, ",", ".") : self::DEFAULT_STOCK;
    }

    public function getStockNokAttribute($value)
    {
        return $value ? number_format($value, 0, ",", ".") : self::DEFAULT_STOCK;
    }

    static function updatingPoslogProduct($data, $baseApi)
    {
        $data = array_values($data);
        if ($data) {
            //delete all data from WMS JABAR
            $delete = self::where('source_data', '=', $baseApi)->delete();
            //insert all data from $data
            $insertPoslog = self::insert($data);
        }
    }

    static function setValue($data, $material, $baseApi)
    {
        $key = Usage::getKeyIndex($material);
        $stockOk = Usage::getStockOk($material);
        if ($stockOk > 0) {
            $data[$key] = [
                'material_id' => $material->material_id,
                'material_name' => $material->material_name,
                'soh_location' => Usage::getLocationId($material),
                'soh_location_name' => Usage::getSohLocationName($material),
                'UoM' => Usage::getUnitofMaterial($material),
                'matg_id' => $material->matg_id,
                'stock_ok' => $stockOk,
                'stock_nok' => Usage::getStockNok($material),
                'source_data' => $baseApi,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return $data;
    }

    static function addStock($data, $material)
    {
        $key = Usage::getKeyIndex($material);
        $data[$key]['stock_ok'] += Usage::getStockOk($material);
        $data[$key]['stock_nok'] += Usage::getStockNok($material);
        return $data;
    }

    static function getUpdateTime($field, $value, $baseApi)
    {
        try {
            $updateTime = self::where(function ($query) use($field, $value, $baseApi) {
                $query->where('source_data', '=', $baseApi);
            })->orderBy('updated_at','desc')->value('updated_at');
        } catch (\Exception $exception) {
            $updateTime = null;
        }
        return $updateTime;
    }

    static function syncSohLocation()
    {
        $finalMaterials = \App\LogisticRealizationItems::select('final_product_id')->whereNotNull('final_by')->groupBy('final_product_id')->get();
        foreach($finalMaterials as $material) {
            $poslog = \App\PoslogProduct::where('material_id', $material->final_product_id)->first();
            if ($poslog) {
                $update = \App\LogisticRealizationItems::where('final_product_id', $material->final_product_id)->update([
                    'final_soh_location' => $poslog->soh_location,
                    'final_soh_location_name' => $poslog->soh_location_name
                ]);
            }
        }
    }
}
