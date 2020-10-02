<?php

/**
 * Class for storing all method & data regarding item usage information, which
 * are retrieved from Pelaporan API
 */

namespace App;

use GuzzleHttp;
use JWTAuth;

class Usage
{
    static $client=null;

    static function getClient()
    {
        if (static::$client == null) {
            static::$client = new GuzzleHttp\Client();
        }

        return static::$client;
    }

    /**
     * Request authorization token from pelaporan API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanAuthToken()
    {
        // login first
        $login_url = env('PELAPORAN_API_BASE_URL') . '/api/login';
        $res = static::getClient()->post($login_url, [
            'json'   => [
                'username' => env('PELAPORAN_AUTH_USER'),
                'password' => env('PELAPORAN_AUTH_PASSWORD'),
            ],
            'verify' => false,
        ]);
        if ($res->getStatusCode() != 200) {
            return [ response()->format(500, 'Internal server error'), null ];
        }
        return json_decode($res->getBody())->data->token;
    }

    /**
     * Request authorization token from pelaporan API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanData($url)
    {
        $token = static::getPelaporanAuthToken();
        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: pelaporan API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return [ null, json_decode($res->getBody())->data ];
        }
    }

    /**
     * Request used rdt stock data from pelaporan dinkes API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanCitySummary()
    {
        // retrieving summary by cities endpont
        $token = static::getPelaporanAuthToken();
        $url = env('PELAPORAN_API_BASE_URL') . '/api/rdt/summary-by-cities';
        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: pelaporan API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            // Extract the data
            return [ null,  json_decode($res->getBody())->data ];
        }
    }

    /**
     * Request used RDT result status
     *
     * @return Array [ error, result_array ]
     */
    static function getRdtResultSummary($city_code)
    {
        $url = env('PELAPORAN_API_BASE_URL') . '/api/rdt/summary-result-by-cities?city_code=' . $city_code;
        return $this->getPelaporanData($url)
    }

    /**
     * Request used RDT result status
     *
     * @return Array [ error, result_array ]
     */
    static function getRdtResultList($city_code)
    {
        $url = env('PELAPORAN_API_BASE_URL') . '/api/rdt/summary-result-list-by-cities?city_code=' . $city_code;
        return $this->getPelaporanData($url)
    }

    /**
     * Request used rdt stock data (grouped by faskes, filter only from the same
     * kob/kota) from pelaporan dinkes API
     *
     * @return Array [ error, result_array ]
     */
    static function getPelaporanFaskesSummary()
    {
        // retrieving summary by cities endpont
        $token = static::getPelaporanAuthToken();
        $district_code = JWTAuth::user()->code_district_city;
        $url  = env('PELAPORAN_API_BASE_URL') . '/api/rdt/faskes-summary-by-cities';
        $url .= "?district_code=$district_code";

        $res = static::getClient()->get($url, [
            'verify' => false,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: pelaporan API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            // Extract the data
            $raw_list = json_decode($res->getBody())->data;

            // format data
            $faskes_list = [];
            foreach ($raw_list as $row) {
                $faskes_list[] = [
                    "faskes_name" => $row->_id,
                    "total_stock" => null,
                    "total_used" => $row->total,
                ];
            }

            return [ null,  $faskes_list ];
        }
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getLogisticStock($param, $api, $baseApi)
    {
        $apiKey = ($baseApi === 'DASHBOARD_PIKOBAR_API_BASE_URL') ? env('DASHBOARD_PIKOBAR_API_KEY') : env('WMS_JABAR_API_KEY');
        $apiLink = ($baseApi === 'DASHBOARD_PIKOBAR_API_BASE_URL') ? env('DASHBOARD_PIKOBAR_API_BASE_URL') : env('WMS_JABAR_BASE_URL');
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
        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return ($baseApi === 'DASHBOARD_PIKOBAR_API_BASE_URL') ? json_decode($res->getBody())->data : json_decode($res->getBody())->msg;
        }
    }

    /**
     * Request logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getMaterialPosLog()
    {
        $apiKey = env('WMS_JABAR_API_KEY');
        $apiLink = env('WMS_JABAR_BASE_URL');
        $apiFunction = '/api/material';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return json_decode($res->getBody())->msg;
        }
    }

    /**
     * Request Material logistic stock data obtained from PT POS
     *
     * @return Array [ error, result_array ]
     */
    static function getMaterialStock($id)
    {
        $param = '{"material_id":"' . $id . '"}';
        $apiKey = env('WMS_JABAR_API_KEY');
        $apiLink = env('WMS_JABAR_BASE_URL');
        $apiFunction = '/api/soh_fmaterial';
        $url = $apiLink . $apiFunction;
        $res = static::getClient()->get($url, [
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'api-key' => $apiKey,
            ],
            'body' => $param
        ]);

        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
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
        $apiKey = env('WMS_JABAR_API_KEY');
        $apiLink = env('WMS_JABAR_BASE_URL');
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

        if ($res->getStatusCode() != 200) {
            error_log("Error: WMS Jabar API returning status code ".$res->getStatusCode());
            return [ response()->format(500, 'Internal server error'), null ];
        } else {
            return json_decode($res->getBody())->msg;
        }
    }
}
