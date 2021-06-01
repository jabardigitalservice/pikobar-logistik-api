<?php

namespace App\Http\Controllers\API\v1;

use App\MasterFaskes;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Validation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MasterFaskesController extends Controller
{
    public function index(Request $request)
    {
        $data = MasterFaskes::getFaskesList($request);
        $response = response()->format(Response::HTTP_OK, 'success', $data);
        return $response;
    }

    public function show($id)
    {
        $data =  MasterFaskes::find($id);
        return response()->format(Response::HTTP_OK, 'success', $data);
    }

    public function store(Request $request)
    {
        $model = new MasterFaskes();
        $param = [
            'nomor_izin_sarana' => 'required',
            'nama_faskes' => 'required',
            'id_tipe_faskes' => 'required',
            'nama_atasan' => 'required',
            'point_latitude_longitude' => 'string',
            'permit_file' => 'required|mimes:jpeg,jpg,png|max:10240'
        ];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            try {
                $model->fill($request->input());
                $model->verification_status = 'not_verified';
                $model->is_imported = 0;
                $model->permit_file = $this->permitLetterStore($request);
                $model->save();
                $response = response()->format(Response::HTTP_OK, 'success', $model);
            } catch (\Exception $e) {
                $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage());
            }
        }
        return $response;
    }

    public function verify(Request $request, $id)
    {
        $param = ['verification_status' => 'required'];
        $response = Validation::validate($request, $param);
        if ($response->getStatusCode() === Response::HTTP_OK) {
            if ($request->verification_status == 'verified' || $request->verification_status == 'rejected') {
                try {
                    $model =  MasterFaskes::findOrFail($id);
                    $model->verification_status = $request->verification_status;
                    $model->save();
                    $response = response()->format(Response::HTTP_OK, 'success', $model);
                } catch (\Exception $e) {
                    $response = response()->json(array('message' => 'could_not_verify_faskes'), Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
        }
        return $response;
    }

    public function permitLetterStore($request)
    {
        $path = null;
        if ($request->hasFile('permit_file')) {
            $path = Storage::put('registration/letter', $request->permit_file);
        }
        return $path;
    }
}
