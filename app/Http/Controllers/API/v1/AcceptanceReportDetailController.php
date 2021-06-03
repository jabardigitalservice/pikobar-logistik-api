<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\AcceptanceReportDetail;

class AcceptanceReportDetailController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 3);
        $acceptanceReport = AcceptanceReportDetail::where('acceptance_report_id', $request->input('acceptance_report_id'))->paginate($limit);
        return response()->format(Response::HTTP_OK, 'success', $acceptanceReport);
    }
}
