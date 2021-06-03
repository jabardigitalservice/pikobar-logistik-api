<?php

/**
 * Class for storing all method & data regarding item usage information, which
 * are retrieved from Pelaporan API
 */

namespace App;

use GuzzleHttp;
use JWTAuth;
use DB;
use App\PoslogProduct;
use App\SohLocation;
use Illuminate\Http\Response;

class Usage
{
    static $client = null;

    static function getClient()
    {
        if (static::$client == null) {
            static::$client = new GuzzleHttp\Client();
        }

        return static::$client;
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getLogisticStock($param, $api, $baseApi)
    {
        $apiKey = config('wmsjabar.key');
        $apiLink = config('wmsjabar.url');
        $apiFunction = $api ? $api : '/api/soh_fmaterialgroup';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
            'body' => $param
        ]);
        if ($res->getStatusCode() != Response::HTTP_OK) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal server error', null) ];
        } else {
            return json_decode($res->getBody())->msg;
        }
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getLogisticStockByLocation($id)
    {
        $param = '{"soh_location":"' . $id . '"}';
        $apiKey = config('wmsjabar.key');
        $apiLink = config('wmsjabar.url');
        $apiFunction = '/api/soh_flocation';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
            'body' => $param
        ]);

        if ($res->getStatusCode() != Response::HTTP_OK) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal server error'), null ];
        } else {
            return json_decode($res->getBody())->msg;
        }
    }

    static function getPoslogItem($fieldPoslog, $valuePoslog, $materialName)
    {
        $poslogProduct = [];
        try {
            $poslogProduct = PoslogProduct::select(DB::raw('CONCAT("(", material_id, ") ", material_name) as name'), 'material_id', 'material_name', 'soh_location', 'soh_location_name', 'uom', 'matg_id', 'stock_ok', 'stock_nok')
            ->where(function ($query) use ($fieldPoslog, $valuePoslog, $materialName) {
                $query->where('stock_ok', '>', 0);
                if ($valuePoslog) {
                    $query->where($fieldPoslog, '=', $valuePoslog);
                }

                if ($materialName) {
                    $query->where(DB::raw('CONCAT("(", material_id, ") ", material_name)'), 'LIKE', '%' . $materialName . '%');
                }
            })->orderBy('material_name','asc')->orderBy('soh_location','asc')->orderBy('stock_ok','desc')->get();
        } catch (\Exception $exception) {
            return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return $poslogProduct;
    }

    static function getPoslogItemUnit($fieldPoslog, $valuePoslog, $materialName)
    {
        $data = self::getPoslogItem($fieldPoslog, $valuePoslog, $materialName);
        $dataFinal = [];
        foreach ($data as $val) {
            $dataFinal[] = [
                'id' => $val->uom,
                'name' => $val->uom
            ];
        }
        if (!$dataFinal) {
            $dataFinal[] = [
                'id' => 'PCS',
                'name' => 'PCS'
            ];
        }
        return $dataFinal;
    }

    static function syncWmsJabar()
    {
        $data = [];
        $sohLocation = SohLocation::all();
        $baseApi = PoslogProduct::API_POSLOG;
        foreach ($sohLocation as $val) {
            $materials = static::getLogisticStockByLocation($val['location_id']);
            $data = static::setPoslogProduct($materials, $baseApi, $data);
        }
        PoslogProduct::updatingPoslogProduct($data, $baseApi);
    }

    static function setPoslogProduct($materials, $baseApi, $data)
    {
        foreach ($materials as $material) {
            $key = static::getKeyIndex($material);
            if (!isset($data[$key])) {
                $data = PoslogProduct::setValue($data, $material, $baseApi);
            } else {
                $data = PoslogProduct::addStock($data, $material);
            }
        }
        return $data;
    }

    static function getLocationId($material)
    {
        return isset($material->soh_location) ? $material->soh_location : ($material->inbound[0]->inbound_location ? $material->inbound[0]->inbound_location : $material->inbound[0]->whs_name);
    }

    static function getKeyIndex($material)
    {
        return $material->material_id .'-'. static::getLocationId($material);
    }

    static function getStockOk($material)
    {
        return isset($material->stock_ok) ? ($material->stock_ok - $material->booked_stock) : $material->qty_good_in;
    }

    static function getStockNok($material)
    {
        return isset($material->stock_nok) ? $material->stock_nok : $material->qty_notgood_in;
    }

    static function getSohLocationName($material)
    {
        return isset($material->soh_location_name) ? $material->soh_location_name : $material->inbound[0]->whs_name;
    }

    static function getUnitofMaterial($material)
    {
        return isset($material->UoM) ? $material->UoM : $material->uom;
    }
}
