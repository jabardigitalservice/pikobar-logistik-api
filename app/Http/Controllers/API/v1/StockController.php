<?php

namespace App\Http\Controllers\API\v1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Usage;
use App\Product;
use App\PoslogProduct;
use App\SyncApiSchedules;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $result = $this->getParam($request);
        $this->syncDatabase($result['field_poslog'], $result['field_poslog']);
        $data = Usage::getPoslogItem($result['field_poslog'], $result['value_poslog'], $result['material_name']);
        return response()->format(200, 'success', $data);
    }

    /**
     * Display a listing of the resource.
     * if did not exists in our database, system will update material list
     *
     * @return \Illuminate\Http\Response
     */
    public function productUnitList($id)
    {
        $data = Usage::getPoslogItemUnit('material_id', $id, false);
        return response()->format(200, 'success', $data);
    }

    public function getParam($request)
    {
        $result = [
            'field_poslog' => '',
            'value_poslog' => '',
            'material_name' => false
        ];
        if ($request->has('poslog_id')) {
            $result['field_poslog'] = 'material_id';
            $result['value_poslog'] = $request->input('poslog_id');
        } else if ($request->has('id')) {
            $product = Product::getFirst($request->input('id'));
            $result['field_poslog'] = 'matg_id';
            $result['value_poslog'] = $product->material_group;
            if (strpos($product->name, 'VTM') !== false) {
                $result['material_name'] = 'VTM';
            }
        }
        $result['material_name'] = $request->material_name ? $request->material_name : $result['material_name'];
        return $result;
    }

    public function syncDatabase($fieldPoslog, $valuePoslog)
    {
        $baseApi = PoslogProduct::API_POSLOG;
        if ($this->checkOutdated($fieldPoslog, $valuePoslog, $baseApi)) {
            Usage::syncWmsJabar(); // Sync from WMS JABAR
        }
    }

    public function checkOutdated($fieldPoslog, $valuePoslog, $baseApi)
    {
        $result = false;
        $updateTime = PoslogProduct::getUpdateTime($fieldPoslog, $valuePoslog, $baseApi);
        $result = $this->isOutdated($updateTime, $baseApi);
        return $result;
    }

    public function isOutdated($updateTime, $baseApi)
    {
        $time = date('Y-m-d H:i:s');
        $updateTime = SyncApiSchedules::getIntervalTimeByAPI($baseApi, $updateTime);
        return $updateTime < $time ?? false;
    }
}
