<?php

namespace App\Http\Controllers\API\v1;

use App\MasterFaskesType;
use App\Agency;
use DB;
use App\Applicant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class MasterFaskesTypeController extends Controller
{
    public function index(Request $request)
    {

        try {
            $data = MasterFaskesType::where(function ($query) use ($request) {
                if ($request->filled('is_imported')) {
                    $query->where('is_imported', $request->input('is_imported'));
                }
                if ($request->filled('non_public')) {
                    $query->where('non_public', $request->input('non_public'));
                }
            })->get();
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }

        return response()->format(200, 'success', $data);
    }

    public function masterFaskesTypeRequest(Request $request)
    {
        try {
            $startDate = $request->filled('start_date') ? $request->input('start_date') . ' 00:00:00' : '2020-01-01 00:00:00';
            $endDate = $request->filled('end_date') ? $request->input('end_date') . ' 23:59:59' : date('Y-m-d H:i:s');

            $query = MasterFaskesType::withCount([
                'agency as total_request' => function ($query) use ($startDate, $endDate) {
                    return $query->join('applicants', 'applicants.agency_id', 'agency.id')
                        ->where('applicants.verification_status', Applicant::STATUS_VERIFIED)
                        ->where('applicants.is_deleted', '!=', 1)
                        ->whereBetween('applicants.created_at', [$startDate, $endDate]); 
                }
            ]);
            if ($request->filled('sort')) {
                $query->orderBy('total_request', $request->input('sort'));
            }
            $data = $query->get();
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }

        return response()->format(200, 'success', $data);
    } 

    /**
     * masterFaskesTypeTopRequest function
     * 
     * to get top faskes type requested by applicants
     *
     * @param Request $request
     * @return void
     */
    public function masterFaskesTypeTopRequest(Request $request)
    {
        $totalMax = MasterFaskesType::select('id', 'name')
                        ->withCount(['agency as total' => function ($query) use ($request) {
                            $query->whereHas('applicant', function($query) use ($request) {
                                $query->active()
                                    ->createdBetween($request)
                                    ->where('verification_status', Applicant::STATUS_VERIFIED)
                                    ->filter($request);
                            });
                        }])
                        ->orderBy('total', 'desc')
                        ->firstOrFail();

        $agencyTotal = Agency::select('agency_type')
                            ->whereHas('applicant', function($query) use ($request) {
                                $query->active()
                                    ->createdBetween($request)
                                    ->where('verification_status', Applicant::STATUS_VERIFIED)
                                    ->filter($request);
                            })
                            ->groupBy('agency_type')->get();

        $data = [
            'total_agency' => count($agencyTotal),
            'total_max' => $totalMax
        ];

        return response()->format(Response::HTTP_OK, 'success', $data);
    }
}
