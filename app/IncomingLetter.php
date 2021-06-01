<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Applicant;
use Illuminate\Http\Request;

class IncomingLetter extends Model
{
    static function getIncomingLetterList(Request $request)
    {
        $data = [];
        $limit = $request->input('limit', 10);
        $sort = $request->has('sort') ? ['applicants.application_letter_number ' . $request->input('sort') ] : ['applicants.created_at ASC'];

        $data = Applicant::select(self::getIncomingLetterSelectList());
        $data = self::joinTable($data);
        $data = $data->join('districtcities', 'districtcities.kemendagri_kabupaten_kode', '=', 'agency.location_district_code');
        $data = self::whereList($request, $data);
        $data = $data->orderByRaw(implode($sort))->paginate($limit);

        $data->getCollection()->transform(function ($applicant, $key) {
            $applicant->letter_date = date('Y-m-d', strtotime($applicant->letter_date));
            return $applicant;
        });

        return $data;
    }

    static function getIncomingLetterSelectList()
    {
        return [
            'applicants.id as applicant_id',
            'applicants.application_letter_number as letter_number',
            'applicants.agency_id as id',
            'applicants.applicant_name',
            'applicants.created_at as letter_date',
            'agency.agency_type',
            'request_letters.id as incoming_mail_status',
            'request_letters.id as request_letters_id',

            'agency.agency_name',
            'agency.location_district_code as district_code',
            'districtcities.kemendagri_kabupaten_nama as district_name'
        ];
    }

    static function whereList(Request $request, $data)
    {
        return $data->where(function ($query) use ($request) {
            if ($request->has('letter_date')) {
                $query->whereRaw("DATE(applicants.created_at) = '" . $request->input('letter_date') . "'");
            }
            if ($request->has('district_code')) {
                $query->where('agency.location_district_code', '=', $request->input('district_code'));
            }
            if ($request->has('agency_type')) {
                $query->where('agency.agency_type', '=', $request->input('agency_type'));
            }
            if ($request->has('letter_number')) {
                $query->where('applicants.application_letter_number', 'LIKE', "%{$request->input('letter_number')}%");
            }
            if ($request->has('mail_status')) {
                if ($request->input('mail_status') === 'exists') {
                    $query->whereNotNull('request_letters.id');
                } else {
                    $query->whereNull('request_letters.id');
                }
            }
        })
        ->where('applicants.is_deleted', '!=', 1)
        ->whereNotNull('applicants.finalized_by');
    }

    static function joinTable($data)
    {
        return $data->join('agency', 'agency.id', '=', 'applicants.agency_id')
        ->leftJoin('request_letters', 'request_letters.applicant_id', '=', 'applicants.id');
    }
}
