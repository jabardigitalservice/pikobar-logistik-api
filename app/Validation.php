<?php

namespace App;

use Illuminate\Http\Response;
use Validator;

class Validation
{
    static function validate($request, $param)
    {
        $response = response()->format(Response::HTTP_OK, 'success');
        $validator = Validator::make($request->all(), $param);
        if ($validator->fails()) {
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors(), $param);
        }
        return $response;
    }

    static function defaultError()
    {
        return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, 'Error Tidak Diketahui');
    }

    static function validateAgencyType($agencyType, $allowable)
    {
        $response = response()->json(['status' => 'fail', 'message' => 'agency_type_value_is_not_accepted']);
        if (in_array($agencyType, $allowable)) { //allowable agency_type: {agency_type 4 => Masyarakat Umum , agency_type 5 => Instansi Lainnya}
            $response = response()->format(Response::HTTP_OK, 'success');
        }
        return $response;
    }

    public static function setCompleteness($request)
    {
        $updateCompleteness = Agency::where('agency_name', '')
            ->orWhereNull('agency_name')
            ->orWhere('location_address', '')
            ->orWhereNull('location_address')
            ->update(['completeness' => 0]);

        $applicants = Applicant::select('agency_id')
            ->where('applicant_name', '')
            ->orWhereNull('applicant_name')
            ->orWhere('primary_phone_number', '')
            ->orWhereNull('primary_phone_number')
            ->orWhere('file', '')
            ->orWhereNull('file')
            ->get();

        $upcateCompleteness = Agency::whereIn('id', $applicants)
            ->update(['completeness' => 0]);

        $agencyNoLetter = Agency::doesnthave('letter')
            ->update(['completeness' => 0]);
    }
}
