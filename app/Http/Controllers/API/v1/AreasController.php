<?php

namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\City;
use App\Subdistrict;
use App\Village;
use App\Applicant;
use App\Enums\ApplicantStatusEnum;
use Illuminate\Http\Response;

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

        if ($request->has('city_code')) {
            $query->where('kemendagri_kabupaten_kode', '=', $request->input('city_code'));
        }

        return response()->format(Response::HTTP_OK, true, $query->get());
    }

    public function subArea(Request $request)
    {
        $query = [];
        $param = $this->getParamAreas($request->area_type);
        if ($request->area_type === 'village') {
            $query = Village::select('*');
        } else {
            $query = Subdistrict::select('*');
        }
        $query->orderBy($param['orderBy'], 'asc');

        $valueCodeLevelOne = $request->has($param['requestQueryLevelOne']) ? $request->input($param['requestQueryLevelOne']) : $param['code'];
        $query->where($param['whereCodeLevelOne'], '=', $valueCodeLevelOne);

        if ($request->has($param['requestQueryLevelTwo'])) {
            $query->where($param['whereCodeLevelTwo'], '=', $request->input($param['requestQueryLevelTwo']));
        }

        return response()->format(Response::HTTP_OK, true, $query->get());
    }

    public function getParamAreas($areasType)
    {
        $param['orderBy'] = 'kemendagri_kecamatan_nama';
        $param['whereCodeLevelOne'] = 'kemendagri_kabupaten_kode';
        $param['code'] = '32.01';
        $param['requestQueryLevelOne'] = 'city_code';
        $param['whereCodeLevelTwo'] = 'kemendagri_kecamatan_kode';
        $param['requestQueryLevelTwo'] = 'subdistrict_code';
        if ($areasType === 'village') {
            $param['orderBy'] = 'kemendagri_desa_nama';
            $param['whereCodeLevelOne'] = 'kemendagri_kecamatan_kode';
            $param['code'] = '32.01.01';
            $param['requestQueryLevelOne'] = 'subdistrict_code';
            $param['whereCodeLevelTwo'] = 'kemendagri_desa_kode';
            $param['requestQueryLevelTwo'] = 'village_code';
        }

        return $param;
    }

    public function getCitiesTotalRequest(Request $request)
    {
        $startDate = $request->has('start_date') ? $request->input('start_date') . ' 00:00:00' : '2020-01-01 00:00:00';
        $endDate = $request->has('end_date') ? $request->input('end_date') . ' 23:59:59' : date('Y-m-d H:i:s');

        $query = City::withCount([
            'agency' => function ($query) use ($startDate, $endDate) {
                return $query->join('applicants', 'applicants.agency_id', 'agency.id')
                        ->where('applicants.verification_status', ApplicantStatusEnum::verified())
                        ->where('applicants.is_deleted', '!=', 1)
                        ->whereBetween('applicants.created_at', [$startDate, $endDate]);
            }
            ]);
        if ($request->has('sort')) {
            $query->orderBy('agency_count', $request->input('sort'));
        }
        $data = $query->get();

        return response()->format(Response::HTTP_OK, 'success', $data);
    }

}
