<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FileUpload;

class Letter extends Model
{
    protected $table = 'letter';

    protected $fillable = [
        'agency_id',
        'applicant_id',
        'letter'
    ];

    public function getLetterAttribute($value)
    {
        $data = FileUpload::find($value);
        $letter = '';
        return $letter;
        if (isset($data->name)) {
            $letter = $data->name;
            if (substr($data->name, 0, 12) === 'registration') {
                $letter = config('aws.url') . $data->name;
            }
        }
        return $letter;
    }
}
