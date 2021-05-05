<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcceptanceReport extends Model
{
    const STATUS_REPORTED = 1;
    const STATUS_NOT_REPORTED = 0;

    static function setParamStore()
    {
        $param['fullname'] = 'required';
        $param['position'] = 'required';
        $param['phone'] = 'required';
        $param['date'] = 'required';
        $param['officer_fullname'] = 'required';
        $param['note'] = 'required';
        $param['agency_id'] = 'required';
        $param['items'] = 'required';
        $param['proof_pic_length'] = 'required';
        $param['bast_proof_length'] = 'required';
        $param['item_proof_length'] = 'required';
        return $param;
    }

    public function agency()
    {
        return $this->hasOne('App\Agency', 'id', 'agency_id');
    }

    public function applicant()
    {
        return $this->hasOne('App\Applicant', 'agency_id', 'agency_id');
    }

    /**
     * Get the acceptance_report_detail for the blog post.
     */
    public function AcceptanceReportDetail()
    {
        return $this->hasMany('App\AcceptanceReportDetail');
    }
}
