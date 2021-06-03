<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use App\Agency;
use App\Applicant;
use App\Letter;
use App\FileUpload;
use App\Needs;
use App\MasterFaskesType;
use App\MasterFaskes;
use App\City;
use App\Subdistrict;
use App\Village;
use App\Product;
use App\MasterUnit;
use App\ProductUnit;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use JWTAuth;
use DB;
use App\Validation;
use App\Imports\MultipleSheetImport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;

class LogisticImport extends Model
{
    public static function import($data)
    {
        $user = JWTAuth::user();
        $application = $data->sheetData[0]->toArray();

        foreach ($application as $item) {
            if (isset($item['id_permohonan']) && isset($item['tanggal_pengajuan']) && isset($item['jenis_instansi']) && isset($item['nama_instansi'])) {
                $createdAt = Date::excelToDateTimeObject($item['tanggal_pengajuan']);
                $item['agency'] = self::createAgency($item, $createdAt);
                $item['applicant'] = self::createApplicant($item, $user, $createdAt);
                self::createProducts($products, $data, $item);
                $letter = self::createLetter($item);
            }
        }
    }

    static function createAgency($item, $createdAt)
    {
        $item['master_faskes_type_id'] = self::getMasterFaskesType($item);
        $masterFaskesId = self::getMasterFaskes($item);
        $districtCityId = self::getDistrictCity($item) ?: '-';
        $subDistrictId = self::getSubDistrict($item) ?: '-';
        $villageId = self::getVillage($item) ?: '-';

        $agency = Agency::create([
            'master_faskes_id' => $masterFaskesId,
            'agency_type' => $item['master_faskes_type_id'],
            'agency_name' => $item['nama_instansi'] ?: '-',
            'phone_number' => $item['nomor_telepon'] ?: '-',
            'location_district_code' => $districtCityId,
            'location_subdistrict_code' => $subDistrictId,
            'location_village_code' => $villageId,
            'location_address' => $item['alamat'] ?: '-',
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);

        return $agency;
    }

    static function createApplicant($item, $user, $createdAt)
    {
        $applicant = Applicant::create([
            'agency_id' => $item['agency']->id,
            'applicant_name' => $item['nama_pemohon'] ?: '-',
            'applicants_office' => $item['jabatan_pemohon'] ?: '-',
            'file' => self::getFileUpload($item['ktp']),
            'email' => $item['email_pemohon'] ?: '-',
            'primary_phone_number' => $item['nomor_telepon_pemohon_1'] ?: '-',
            'secondary_phone_number' => $item['nomor_telepon_pemohon_2'] ?: '-',
            'verification_status' => $item['status_verifikasi'],
            'source_data' => $item['source_data'],
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'verified_by' => $user->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);
        return $applicant;
    }

    static function createProducts($products, $data, $item)
    {
        $products = self::findProductInSheet($data, $item['id_permohonan']);
        if (count($products) > 0) {
            foreach ($products as $product) {
                $need = Needs::create(
                    [
                        'agency_id' => $item['agency']->id,
                        'applicant_id' => $item['applicant']->id,
                        'product_id' => $product['product_id'],
                        'brand' => $product['deskripsi_produk'],
                        'quantity' => $product['jumlah'],
                        'unit' => $product['unit_id'],
                        'usage' => $product['kegunaan'],
                        'priority' => $product['urgensi']
                    ]
                );
            }
        }
    }

    static function createLetter($item)
    {
        $letter = Letter::create([
            'agency_id' => $item['agency']->id,
            'applicant_id' => $item['applicant']->id,
            'letter' => self::getFileUpload($item['surat_permohonan'])
        ]);
    }

    public static function getMasterFaskesType($data)
    {
        $masterFaskesType = MasterFaskesType::where('name', 'LIKE', "%{$data['jenis_instansi']}%")->first();
        if (!$masterFaskesType) {
            $masterFaskesType = MasterFaskesType::create([
                'name' => $data['jenis_instansi'],
                'is_imported' => true
            ]);
        }
        return $masterFaskesType->id;
    }

    public static function getMasterFaskes($data)
    {
        $masterFaskes = MasterFaskes::where('nama_faskes', 'LIKE', "%{$data['nama_instansi']}%")->first();
        if (!$masterFaskes) {
            $masterFaskes = MasterFaskes::create([
                'id_tipe_faskes' => $data['master_faskes_type_id'],
                'verification_status' => 'verified',
                'nama_faskes' => $data['nama_instansi'],
                'nama_atasan' => '-',
                'nomor_registrasi' => '-',
                'verification_status' => 'verified',
                'is_imported' => true
            ]);
        }
        return $masterFaskes->id;
    }

    public static function getDistrictCity($data)
    {
        $city = City::where('kemendagri_kabupaten_nama', 'LIKE', "%{$data['kabupaten']}%")->first();

        return $city ? $city->kemendagri_kabupaten_kode : false;
    }

    public static function getSubDistrict($data)
    {
        $subDistrict = Subdistrict::where('kemendagri_kecamatan_nama', 'LIKE', "%{$data['kecamatan']}%")->first();

        return $subDistrict ? $subDistrict->kemendagri_kecamatan_kode : false;
    }

    public static function getVillage($data)
    {
        $village = Village::where('kemendagri_desa_nama', 'LIKE', "%{$data['desa']}%")->first();

        return $village ? $village->kemendagri_desa_kode : false;
    }

    public static function getFileUpload($file)
    {
        $fileUpload = FileUpload::create(['name' => $file]);
        return $fileUpload->id;
    }

    public static function findProductInSheet($data, $idPermohonan)
    {
        $logisticItem = $data->sheetData[1]->toArray();
        $result = [];

        foreach ($logisticItem as $item) {
            if (isset($item['id_permohonan']) && $item['id_permohonan'] === $idPermohonan) {
                $productId = self::getProduct($item);
                $item['product_id'] = $productId;
                $unitId = self::getMasterUnit($item);
                $item['unit_id'] = $unitId;
                $result[] = $item;
            }
        }
        return $result;
    }

    public static function getProduct($data)
    {
        $product = Product::where('name', 'LIKE', "%{$data['nama_produk']}%")->first();
        if (!$product) {
            $product = Product::create([
                'name' => $data['nama_produk'],
                'is_imported' => true
            ]);
        }

        return $product->id;
    }

    public static function getMasterUnit($data)
    {
        $masterUnit = MasterUnit::where('unit', 'LIKE', "%{$data['satuan']}%")->first();
        if (!$masterUnit) {
            $masterUnit = MasterUnit::create([
                'unit' => ucwords($data['satuan']),
                'is_imported' => true,
            ]);

            ProductUnit::create([
                'product_id' => $data['product_id'],
                'unit_id' => $masterUnit->id
            ]);
        }

        return $masterUnit->id;
    }

    static function importProcess(Request $request)
    {
        $response = Validation::defaultError();
        DB::beginTransaction();
        try {
            $import = new MultipleSheetImport();
            $ts = Excel::import($import, request()->file('file'));
            self::import($import);
            DB::commit();
            $response = response()->format(Response::HTTP_OK, 'success', '');
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = response()->format(Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
        return $response;
    }
}
