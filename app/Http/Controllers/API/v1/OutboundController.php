<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Outbound;
use App\WmsJabar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Validation;

class OutboundController extends Controller
{
    /**
     * sendPing function
     * send notification to WMS Jabar to read new logistic request list and create their outbound tickets
     *
     * @return void
     */
    public function sendPing()
    {
        return WmsJabar::sendPing();
    }

    /**
     * getNotification function for API {{base_url}}/api/v1/poslog-notify
     * Get notification from POSLOG to read update logistic request data
     *
     * @return void
     */
    public function getNotification(Request $request)
    {
        $param = [
            'request_id' => 'required'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() == Response::HTTP_OK) {
            $response = WmsJabar::getOutboundById($request);
        }
        return $response;
    }

    public function updateAll(Request $request)
    {
        return WmsJabar::updateAll($request);
    }

    public function tracking(Request $request, $id)
    {
        $limit = $request->input('limit', 3);
        $outbound = Outbound::select('lo_id', 'whs_name', 'pic_name', 'pic_handphone', 'map_url')
                    ->join('soh_locations', 'soh_locations.location_id', '=', 'outbounds.lo_location')
                    ->where('req_id', $id)
                    ->get();

        return $outbound;
    }
}
