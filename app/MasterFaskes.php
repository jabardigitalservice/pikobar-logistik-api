<?php

namespace App;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class MasterFaskes extends Model
{
    const STATUS_NOT_VERIFIED = 'not_verified';
    const STATUS_VERIFIED = 'verified';

    protected $table = 'master_faskes';
    protected $fillable = [
        'nomor_izin_sarana',
        'nomor_registrasi',
        'nama_faskes',
        'poslog_id',
        'poslog_name',
        'id_tipe_faskes',
        'nama_atasan',
        'longitude',
        'latitude',
        'is_imported',
        'point_latitude_longitude',
        'non_medical',
        'kode_kab_kemendagri',
        'nama_kab',
        'alamat',
        'is_reference'
    ];

    public function masterFaskesType()
    {
        return $this->hasOne('App\MasterFaskesType', 'id', 'id_tipe_faskes');
    }

    public function getVerificationStatusAttribute($value)
    {
        $status = $value === self::STATUS_NOT_VERIFIED ? 'Belum Terverifikasi' : ($value === self::STATUS_VERIFIED ? 'Terverifikasi' : '');
        return $status;
    }

    static function getFaskesName($request)
    {
        $name = $request->agency_name;

        if ($request->agency_type <= 3) {
            $data = self::findOrFail($request->master_faskes_id);
            $name = $data->nama_faskes;
        }
        return $name;
    }

    static function getFaskesList($request)
    {
        $limit = $request->input('limit', 20);
        $sort = $request->input('sort', 'asc');

        $data = self::with('masterFaskesType')
        ->where(function ($query) use ($request) {
            if ($request->has('nama_faskes')) {
                $query->where('master_faskes.nama_faskes', 'LIKE', "%{$request->input('nama_faskes')}%");
            }

            if ($request->has('id_tipe_faskes')) {
                $query->where('master_faskes.id_tipe_faskes', '=', $request->input('id_tipe_faskes'));
            }

            if ($request->has('verification_status')) {
                $query->where('master_faskes.verification_status', '=', $request->input('verification_status'));
            } else {
                $query->where('master_faskes.verification_status', '=', self::STATUS_VERIFIED);
            }

            if ($request->has('is_imported')) {
                $query->where('master_faskes.is_imported', $request->input('is_imported'));
            }

        })
        ->orderBy('nama_faskes', $sort)
        ->paginate($limit);
        return $data;
    }

    static function createFaskes(Request $request)
    {
        try {
            $model = new MasterFaskes();
            $model->fill([
                'id_tipe_faskes' => $request->agency_type,
                'nama_faskes' => $request->agency_name
            ]);
            $model->nomor_izin_sarana = '-';
            $model->nama_atasan = '-';
            $model->point_latitude_longitude = '-';
            $model->verification_status = 'verified';
            $model->is_imported = 0;
            $model->non_medical = 1;
            $model->save();
        } catch (\Exception $exception) {
            return response()->format(400, $exception->getMessage());
        }
        return $model;
    }
}
