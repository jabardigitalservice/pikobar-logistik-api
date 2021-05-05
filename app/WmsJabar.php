<?php

/**
 * Class for storing all method & data regarding item usage information, which
 * are retrieved from Pelaporan API
 */

namespace App;
use App\Outbound;
use App\OutboundDetail;
use App\MasterFaskes;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DB;

class WmsJabar extends Usage
{
    static function callAPI($config)
    {
        try {
            $param = $config['param'];
            $apiLink = config('wmsjabar.url');
            $apiKey = config('wmsjabar.key');
            $apiFunction = $config['apiFunction'];
            $url = $apiLink . $apiFunction;
            return static::getClient()->get($url, [
                'headers' => [
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'api-key' => $apiKey,
                ],
                'body' => json_encode($param)
            ]);
        } catch (\Throwable $th) {
            return $th;
        }
    }

    static function sendPing()
    {
        try {
            // Send Notification to WMS Jabar Poslog
            $config['param'] = [];
            $config['apiFunction'] = '/api/pingme';
            $res = self::callAPI($config);

            $outboundPlans = json_decode($res->getBody(), true);
            $response = response()->format(Response::HTTP_OK, 'success', $outboundPlans);
            if ($outboundPlans) {
                $response = self::insertData($outboundPlans);
            }
        } catch (\Exception $exception) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $exception->getTrace());
        }

        return $response;
    }

    static function insertData($outboundPlans)
    {
        DB::beginTransaction();
        $agency_ids = [];
        try {
            foreach ($outboundPlans['msg'] as $key => $outboundPlan) {
                if (isset($outboundPlan['lo_detil'])) {
                    Outbound::updateOrCreate([
                            'lo_id' => $outboundPlan['lo_id'],
                            'req_id' => $outboundPlan['req_id']
                        ],
                        $outboundPlan
                    );
                    OutboundDetail::massInsert($outboundPlan['lo_detil']);
                    self::updateFaskes($outboundPlan);

                    $agency_ids[] = $outboundPlan['req_id'];
                }
            }
            //Flagging to applicants by agency_id = req_id
            $applicantFlagging = Applicant::whereIn('agency_id', $agency_ids)->update(['is_integrated' => 1]);
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success', $outboundPlans);
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'Error Insert Outbound. Because ' . $exception->getMessage(), $exception->getTrace());
        }

        return $response;
    }

    static function getOutboundById($request)
    {
        try {
            // Send Notification to WMS Jabar Poslog
            $config['param']['request_id'] = $request->input('request_id');
            $config['apiFunction'] = '/api/outbound_fReqID';
            $res = self::callAPI($config);

            $outboundPlans = json_decode($res->getBody(), true);
            return self::updateOutbound($outboundPlans);
        } catch (\Exception $exception) {
            return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $exception->getTrace());
        }
    }

    static function updateOutbound($outboundPlans)
    {
        $update = [];
        DB::beginTransaction();
        try {
            $outbounds = collect($outboundPlans['msg'])->map(function($outboundPlan) {
                if (isset($outboundPlan['lo_detil'])) {
                    $lo = $outboundPlan;
                    $lo_detil = $lo['lo_detil'];
                    unset($lo['lo_detil']);

                    $update[$lo['lo_id']] = Outbound::where('lo_id', $lo['lo_id'])->update($lo);

                    self::updateFaskes($outboundPlan);

                    $outboundDetail = collect($lo_detil)->map(function($detil) {
                        OutboundDetail::where('lo_id', $detil['lo_id'])
                                        ->where('material_id', $detil['material_id'])
                                        ->update($detil);
                    });
                }
            });
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success', $outboundPlans);
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'Error Update Outbound. Because ' . $exception->getMessage(), $exception->getTrace());
        }
        return $response;
    }

    static function updateAll(Request $request)
    {
        try {
            $map = Outbound::all()->reject(function ($user) {
                return $user->send_to_extid === false;
            })->map(function ($outbound) use ($request) {
                $request->merge(['request_id' => $outbound->req_id]);
                $update[] = self::getOutboundById($request);
            });
            $response = response()->format(Response::HTTP_OK, 'success');
        } catch (\Exception $exception) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage(), $exception->getTrace());
        }
        return $response;
    }

    static function updateFaskes($outboundPlan)
    {
        return MasterFaskes::where('id', $outboundPlan['send_to_extid'])->update([
            'poslog_id' => $outboundPlan['send_to_id'],
            'poslog_name' => $outboundPlan['send_to_name'],
            'alamat' => $outboundPlan['send_to_address'],
            'kode_kab_kemendagri' => $outboundPlan['city_id'],
            'nama_kab' => $outboundPlan['send_to_city'],
        ]);
    }
}
