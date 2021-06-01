<?php

namespace App\Http\Controllers\API\v1;

use App\OutgoingLetter;
use App\RequestLetter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Validation;
use JWTAuth;
use DB;
use App\LogisticRealizationItems;
use App\FileUpload;
use Illuminate\Support\Facades\Storage;

class OutgoingLetterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $data = [];
        $limit = $request->input('limit', 10);
        $sortType = $request->input('sort', 'DESC');
        $data = OutgoingLetter::where(function ($query) use ($request) {
            if ($request->has('letter_number')) {
                $query->where('letter_number', 'LIKE', "%{$request->input('letter_number')}%");
            }

            if ($request->has('letter_date')) {
                $query->where('letter_date', $request->input('letter_date'));
            }

            if (!in_array(JWTAuth::user()->username, OutgoingLetter::VALID_USER)) {
                $query->where('user_id',  JWTAuth::user()->id);
            }
        });
        $data = $data->orderBy('letter_date', $sortType)->orderBy('created_at', $sortType)->paginate($limit);
        $response = response()->format(Response::HTTP_OK, 'success', $data);
        return $response;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $response = [];
        $param = [
            'letter_name' => 'required',
            'letter_date' => 'required',
            'letter_request' => 'required',
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            $response = $this->outgoingLetterStore($request);
        }
        return $response;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\OutgoingLetter  $outgoingLetter
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $limit = $request->input('limit', 10);
        $outgoingLetter = OutgoingLetter::find($id);
        $data = [ 'outgoing_letter' => $outgoingLetter ];
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    /**
     * Print Function
     * Return spesific data for print outgoing letter format
     *
     * @param  integer $id
     * @return \Illuminate\Http\Response
     */
    public function print($id)
    {
        $data = [];
        try {
            $requestLetter = RequestLetter::getForPrint($id);
            $materials = $this->getAllMaterials($requestLetter);
            $data = [
                'image' => $this->getImageBlog(),
                'outgoing_letter' => OutgoingLetter::getPrintOutgoingLetter($id),
                'request_letter' => $requestLetter,
                'material' => $materials,
            ];
        } catch (\Exception $exception) {
            return response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function getImageBlog()
    {
        //Return Image to base64 format
        $pathPemprov = config('aws.url') . 'logo/pemprov_jabar.png';
        $pathDivLog = config('aws.url') . 'logo/divisi_managemen_logistik.png';
        $dataPemprov = file_get_contents($pathPemprov);
        $dataDivlog = file_get_contents($pathDivLog);
        $pemprovLogo = 'data:image/png;base64,' . base64_encode($dataPemprov);
        $divlogLogo = 'data:image/png;base64,' . base64_encode($dataDivlog);
        return [
            'pemprov' => $pemprovLogo,
            'divlog' => $divlogLogo,
        ];
    }

    public function upload(Request $request)
    {
        $data = [];
        $param = [
            'id' => 'numeric|required',
            'letter_number' => 'string|required',
            'file' => 'required|mimes:jpeg,jpg,png,pdf|max:10240'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            try {
                $path = Storage::put('registration/outgoing_letter', $request->file);
                $fileUpload = FileUpload::create(['name' => $path]);
                $fileUploadId = $fileUpload->id;
                $update = OutgoingLetter::where('id', $request->id)->update([//Update file to Outgoing Letter by ID
                    'file' => $fileUploadId,
                    'letter_number' => $request->letter_number,
                    'status' => OutgoingLetter::APPROVED //Asumsi bahwa file yang diupload sudah bertandatangan basah
                ]);
                $response = response()->format(Response::HTTP_OK, 'success', $data);
            } catch (\Exception $exception) {
                //Return Error Exception
                $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
            }
        }
        return $response;
    }


    /**
     * Store Outgoing Letter
     *
     * @param  \App\OutgoingLetter  $outgoingLetter
     * @return \Illuminate\Http\Response
     */
    public function outgoingLetterStore($request)
    {
        DB::beginTransaction();
        try {
            $request->merge(['user_id' => JWTAuth::user()->id]);
            $request->merge(['status' =>  OutgoingLetter::NOT_APPROVED]);
            $outgoing_letter = OutgoingLetter::create($request->all());
            $request->merge(['outgoing_letter_id' => $outgoing_letter->id]);
            $request_letter = $this->requestLetterStore($request);
            $response = [
                'outgoing_letter' => $outgoing_letter,
                'request_letter' => $request_letter,
            ];
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success', $response);
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return $response;
    }

    /**
     * Store Request Letter
     *
     */
    public function requestLetterStore($request)
    {
        $response = [];
        foreach (json_decode($request->input('letter_request'), true) as $key => $value) {
            $request_letter = RequestLetter::firstOrCreate([
                'outgoing_letter_id' => $request->input('outgoing_letter_id'),
                'applicant_id' => $value['applicant_id']
            ]);
            $response[] = $request_letter;
        }
        return $response;
    }

    /**
     * Get All Materials from Selected Application Letters function
     *
     * @param App\RequestLetter $requestLetter
     * @return array $data
     */
    public function getAllMaterials($requestLetter)
    {
        $requestLetterList = [];
        foreach ($requestLetter as $key => $value) {
            $requestLetterList[] = $value['applicant_id'];
        }
        $data = LogisticRealizationItems::select(
            'agency_name',
            'final_product_id',
            'final_product_name',
            'final_unit',
            'material_group',
            DB::raw('sum(final_quantity) as qty'),
            'soh_location_name as location'
        )
        ->join('agency', 'agency.id', '=', 'agency_id')
        ->join('poslog_products', 'poslog_products.material_id', '=', 'final_product_id', 'left')
        ->whereIn('applicant_id', $requestLetterList)
        ->whereIn('final_status', ['approved', 'replaced'])
        ->groupBy(
            'agency_name',
            'final_product_id',
            'final_product_name',
            'final_unit',
            'material_group',
            'soh_location_name'
        )->orderBy('agency_name', 'final_product_id', 'final_product_name')->get();
        return $data;
    }
}
