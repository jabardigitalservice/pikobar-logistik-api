<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Letter;
use App\AcceptanceReportEvidence;

class FileUpload extends Model
{
    protected $table = 'fileuploads';

    const LETTER_PATH = 'registration/letter';
    const APPLICANT_IDENTITY_PATH = 'registration/applicant_identity';
    const ACCEPTANCE_REPORT_PATH = 'registration/acceptance_report';

    protected $fillable = [
        'id', 'name', 'created_at', 'updated_at'
    ];

    static function storeLetterFile($request)
    {
        $fileuploadid = null;
        $path = Storage::put(self::LETTER_PATH, $request->file('letter_file'));
        $fileupload = self::create(['name' => $path]);
        $fileuploadid = $fileupload->id;
        $request->merge(['letter' => $fileuploadid]);
        $deleteotherletter = Letter::where('agency_id', '=', $request->agency_id)->where('applicant_id', '=', $request->applicant_id)->delete();
        $letter = Letter::create($request->all());
        $letter->file_path = Storage::url($fileupload->name);
        return $letter;
    }

    static function storeApplicantFile($request)
    {
        $path = Storage::put(self::APPLICANT_IDENTITY_PATH, $request->file('applicant_file'));
        $fileUpload = self::create(['name' => $path]);
        $fileUploadId = $fileUpload->id;
        return $fileUpload;
    }

    static function uploadAcceptanceReportFile($request, $paramName, $index)
    {
        $path = Storage::put(self::ACCEPTANCE_REPORT_PATH, $request->file($paramName . $index));
        $fileUpload = FileUpload::create(['name' => $path]);
        $evidence = AcceptanceReportEvidence::create([
            'acceptance_report_id' => $request->acceptance_report_id,
            'path' => $path,
            'type' => $paramName
        ]);
        return $fileUpload;
    }
}
