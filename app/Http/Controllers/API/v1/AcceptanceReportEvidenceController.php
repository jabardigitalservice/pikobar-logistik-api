<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\AcceptanceReportEvidence;

class AcceptanceReportEvidenceController extends Controller
{
    public function index(Request $request)
    {
        $acceptanceReportEvidence = AcceptanceReportEvidence::where('acceptance_report_id', $request->input('acceptance_report_id'))->get();
        return response()->format(Response::HTTP_OK, 'success', $acceptanceReportEvidence);
    }
}
