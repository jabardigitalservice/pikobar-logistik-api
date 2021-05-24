<?php

namespace App\Http\Controllers\API\v1;

use App\OutboundDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OutboundDetailController extends Controller
{
    public function tracking(Request $request, $id, $loId)
    {
        $limit = $request->input('limit', 3);
        $outboundDetail = OutboundDetail::query()
                        ->where('req_id', $id)
                        ->where('lo_id', $loId)
                        ->paginate($limit);

        return $outboundDetail;
    }
}
