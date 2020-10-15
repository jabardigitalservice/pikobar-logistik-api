<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\City;
use App\Subdistrict;
use App\Village;
use App\Applicant;

class AreasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCities(Request $request)
    {
        // id JABAR for default
        $idProvience = 32;

        $query = City::where('kemendagri_provinsi_kode', $idProvience)
                        ->orderBy('kemendagri_kabupaten_nama', 'asc');

        if ($request->query('city_code')) {
            $query->where('kemendagri_kabupaten_kode', '=', $request->query('city_code'));
        }

        return response()->format(200, true, $query->get());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSubDistricts(Request $request)
    {
        $cityCode = '32.01';

        $query = Subdistrict::select('*')
                     ->orderBy('kemendagri_kecamatan_nama', 'asc');

        if ($request->query('city_code')) {
            $query->where('kemendagri_kabupaten_kode', '=', $request->query('city_code'));
        } else {
            $query->where('kemendagri_kabupaten_kode', '=', $cityCode);
        }

        if ($request->query('subdistrict_code')) {
            $query->where('kemendagri_kecamatan_kode', '=', $request->query('subdistrict_code'));
        }

        return response()->format(200, true, $query->get());
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getVillages(Request $request)
    {
        $subdistrictCode = '32.01.01';

        $query = Village::select('*')
                     ->orderBy('kemendagri_desa_nama', 'asc');

        if ($request->query('subdistrict_code')) {
            $query->where('kemendagri_kecamatan_kode', '=', $request->query('subdistrict_code'));
        } else {
            $query->where('kemendagri_kecamatan_kode', '=', $subdistrictCode);
        }

        if ($request->query('village_code')) {
            $query->where('kemendagri_desa_kode', '=', $request->query('village_code'));
        }

        return response()->format(200, true, $query->get());
    }

    public function getCitiesTotalRequest(Request $request)
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') . ' 00:00:00' : '2020-01-01 00:00:00';
        $endDate = $request->filled('end_date') ? $request->input('end_date') . ' 23:59:59' : date('Y-m-d H:i:s');

        try {
            $query = City::withCount([
                'agency' => function ($query) use ($startDate, $endDate) {
                    return $query->join('applicants', 'applicants.agency_id', 'agency.id')
                            ->where('applicants.verification_status', Applicant::STATUS_VERIFIED)
                            ->where('applicants.is_deleted', '!=', 1)
                            ->whereBetween('applicants.created_at', [$startDate, $endDate]);
                }
                ]);
            if ($request->filled('sort')) {
                $query->orderBy('agency_count', $request->input('sort'));
            }
            $data = $query->get();
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }
        return response()->format(200, 'success', $data);
    }

}
