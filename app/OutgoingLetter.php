<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Applicant;
use App\Enums\ApplicantStatusEnum;
use App\RequestLetter;

class OutgoingLetter extends Model
{
    use SoftDeletes;
    protected $table = 'outgoing_letters';

    const APPROVED = 'approved';
    const NOT_APPROVED = 'not_approved';
    const VALID_USER = [
        'superadmin',
        'gtlog',
        'gtlogsurat',
    ];
    
    protected $fillable = [
        'user_id',
        'letter_number',
        'letter_date',
        'status',
        'filename',
        'letter_name'
    ];

    /**
     * Get total request letter by Outgoing Letter ID
     */
    public function requestLetter()
    {
        return $this->hasMany('App\RequestLetter', 'outgoing_letter_id', 'id');
    }

    /**
     * Function to return filename if exists 
     *
     * @param [int] $value
     * @return string / null
     */
    public function getFileAttribute($value)
    {
        $data = FileUpload::find($value);
        if (!$data) {
            return null;
        } elseif (strpos($data->name, 'registration/outgoing_letter') !== false) {
            return config('aws.url') . $data->name;
        } else {
            return $data->name;
        }
    }

    /**
     * Function to return Request Letter Total
     *
     * @param [int] $this->id
     * @return integer
     */
    public function getRequestLetterTotalAttribute()
    {
        return RequestLetter::where('outgoing_letter_id', $this->id)
        ->join('applicants', 'applicants.id', '=', 'request_letters.applicant_id')
        ->where('applicants.verification_status', '=', ApplicantStatusEnum::verified())
        ->count();
    }

    static function getPrintOutgoingLetter($id)
    {
        $outgoingLetter = self::select(
            'id',
            'letter_number',
            'letter_date'
        )->find($id);
        return $outgoingLetter;
    }
}
