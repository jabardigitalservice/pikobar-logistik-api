<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcceptanceReportEvidence extends Model
{
    protected $table = "acceptance_report_evidences";

    protected $fillable = [
        'acceptance_report_id', 'path', 'type'
    ];

    public function getPathAttribute($value)
    {
        return config('aws.url') . $value;
    }
}
