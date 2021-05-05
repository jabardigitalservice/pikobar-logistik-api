<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Agency;

class LogisticRequestExport implements FromCollection, WithMapping, WithHeadings, WithEvents, ShouldAutoSize
{
    use Exportable;

    protected $request;

    function __construct($request) {
           $this->request = $request;
    }

    public function collection()
    {
        $data = Agency::getList($this->request, false)->get();
        foreach ($data as $key => $value) {
            $data[$key]->row_number = $key + 1;
        }

        return $data;
    }

    public function headings(): array
    {
        $columns = [
            'Nomor', 'Nomor Surat Permohonan', 'Tanggal Pengajuan', 'Jenis Instansi', 'Nama Instansi', 'Nomor Telp Instansi',
            'Alamat Lengkap', 'Kab/Kota', 'Kecamatan', 'Desa/Kel', 'Nama Pemohon',
            'Jabatan', 'Email', 'Nomor Kontak Pemohon (opsi 1)', 'Nomor Kontak Pemohon (opsi 2)', 'Detail Permohonan (Nama Barang, Jumlah dan Satuan, Urgensi)',
            'Diverifikasi Oleh', 'Rekomendasi Salur', 'Disetujui Oleh', 'Realisasi Salur', 'Diselesaikan Oleh', 'Status Permohonan'
        ];

        return [
            ['DAFTAR PERMOHONAN LOGISTIK'],
            ['ALAT KESEHATAN'],
            [], //add empty row
            $columns
        ];
    }

    /**
     * Map each row
     *
     * @var LogisticsRequest $logisticsRequest
     */
    public function map($logisticsRequest): array
    {
        $administrationColumn = $this->administrationColumn($logisticsRequest);
        $logisticRequestColumns = $this->logisticRequestColumn($logisticsRequest);
        $recommendationColumn = $this->recommendationColumn($logisticsRequest);
        $finalizationColumn = $this->finalizationColumn($logisticsRequest);
        $data = array_merge($administrationColumn, $logisticRequestColumns, $recommendationColumn, $finalizationColumn);
        return $data;
    }

    public function administrationColumn($logisticsRequest)
    {
        $data = [
            $logisticsRequest->row_number,
            $logisticsRequest->applicant['application_letter_number'],
            $logisticsRequest->created_at,
            $logisticsRequest->masterFaskesType['name'],
            $logisticsRequest->agency_name,
            $logisticsRequest->phone_number,
            $logisticsRequest->location_address,
            $logisticsRequest->city['kemendagri_kabupaten_nama'],
            $logisticsRequest->subDistrict['kemendagri_kecamatan_nama'],
            $logisticsRequest->village['kemendagri_desa_nama'],
            $logisticsRequest->applicant['applicant_name'],
            $logisticsRequest->applicant['applicants_office'],
            $logisticsRequest->applicant['email'],
            $logisticsRequest->applicant['primary_phone_number'],
            $logisticsRequest->applicant['secondary_phone_number']
        ];
        return $data;
    }

    public function logisticRequestColumn($logisticsRequest)
    {
        $data = [
            $logisticsRequest->logisticRequestItems->map(function ($items) {
                $isQuantityEmpty = $items['quantity'] == '-' && $items->masterUnit['name'] == '-';
                if (!$isQuantityEmpty) {
                    $items['quantity'] = $items['quantity'] == '-' ? 'jumlah tidak ada ' : $items['quantity'];
                    $items['unit'] = $items->masterUnit['name'] ?? 'satuan tidak ada';
                    $items->quantityUnit = $items['quantity'] . ' ' . $items['unit'];
                }

                $list = [
                    $items->product['name'] ?? '-',
                    $items->quantityUnit ?? 'jumlah dan satuan tidak ada',
                    $items['priority'] == '-' ? 'urgensi tidak ada' : $items['priority']
                ];
                return implode(', ', $list);
            })->implode('; ', '')
        ];
        return $data;
    }

    public function recommendationColumn($logisticsRequest)
    {
        $data = [
            $logisticsRequest->applicant->verifiedBy['name'] ?? '-',
            $logisticsRequest->recommendationItems->map(function ($items) {
                $items->quantityUnit = $items['realization_quantity'] . ' ' . $items['realization_unit'];
                return implode(', ', [$items->product_name, $items->quantityUnit]);
            })->implode('; ', '')
        ];

        return $data;
    }

    public function finalizationColumn($logisticsRequest)
    {
        $data = [
            $logisticsRequest->applicant->approvedBy['name'] ?? '-',
            $logisticsRequest->finalizationItems->map(function ($items) {
                $items->quantityUnit = $items['final_quantity'] . ' ' . $items['final_unit'];
                return implode(', ', [$items->final_product_name, $items->quantityUnit]);
            })->implode('; ', ''),
            $logisticsRequest->applicant->finalizedBy['name'] ?? '-',
            $logisticsRequest->applicant['status']
        ];

        return $data;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        $styleArray = [
            'font' => [
                'bold' => true,
            ]
        ];
        return [
            AfterSheet::class => function(AfterSheet $event) use ($styleArray) {
                $cellRange = 'A1:V4'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                $event->sheet->getStyle($cellRange)->ApplyFromArray($styleArray);
                $event->sheet->mergeCells('A1:O1');
            },
        ];
    }
}
