<?php

namespace App\Mail;

use App\Agency;
use App\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogisticEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $agency;
    protected $status;
    protected $subject;
    protected $texts;
    protected $notes;
     
    public function __construct(Agency $agency, $status)
    {
        $this->agency = $agency;
        $this->status = $status;
        $this->subject = '[Pikobar] Persetujuan Permohonan Logistik';
        $this->texts = [];
        $this->notes = [];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        switch($this->status) {
            case Applicant::STATUS_NOT_VERIFIED:
                $this->textNotVerified();
                break;
            case Applicant::STATUS_REJECTED:
                $$this->textRejected();
                break;
            case Applicant::STATUS_VERIFIED:
                $this->textVerified();
                break;
            default:
                $this->textOther();
                break;
        }
        return $this->getContent();
    }

    public function textNotVerified()
    {
        $this->subject = '[Pikobar] Permohonan Logistik Diterima';
        $this->texts[] = 'Terima kasih Anda sudah melakukan permohonan pada Aplikasi Permohonan Logistik Pikobar.'; 
        $this->texts[] = 'Melalui surat elektronik ini, kami bermaksud untuk menyampaikan bahwa permohonan logistik dengan kode permohonan #' . $this->agency->id . ' sudah kami terima.';
        $this->notes[] = 'Silahkan anda dapat menghubungi nomor kontak hotline atau email untuk melakukan pengecekan terhadap permohonan tersebut.';
    }
    
    public function textRejected()
    {
        $this->subject = '[Pikobar] Penolakan Permohonan Logistik';
        $this->texts[] = 'Terima kasih Anda sudah melakukan permohonan pada Aplikasi Permohonan Logistik Pikobar.'; 
        $this->texts[] = 'Melalui surat elektronik ini, kami bermaksud untuk menyampaikan bahwa permohonan logistik dengan kode permohonan #' . $this->agency->id . ' tidak bisa kami penuhi.';
        $this->texts[] = 'Dengan alasan penolakan sebagai berikut:'; 
        $this->notes[] = $this->agency->applicant->note;
        $this->notes[] = 'Mohon maaf atas ketidaknyamanan ini.';
    }

    public function textVerified()
    {
        $this->subject = '[Pikobar] Permohonan Logistik Terverifikasi';
        $this->texts[] = 'Terima kasih Anda sudah melakukan permohonan pada Aplikasi Permohonan Logistik Pikobar.';
        $this->texts[] = 'Melalui surat elektronik ini, kami bermaksud untuk menyampaikan bahwa permohonan logistik dengan kode permohonan #' . $this->agency->id . '  sudah dalam status terverifikasi. Selanjutnya kami akan melakukan pengecekan ketersediaan barang pada gudang logistik.';
        $this->notes[] = 'Silahkan anda dapat menghubungi nomor kontak hotline atau email untuk melakukan pengecekan dan konfirmasi terhadap permohonan tersebut.';
    }

    public function textOther()
    {
        $this->subject = '[Pikobar] Permohonan Logistik Sudah Realisasi Salur';
        $this->texts[] = 'Terima kasih Anda sudah melakukan permohonan pada Aplikasi Permohonan Logistik Pikobar.';
        $this->texts[] = 'Melalui surat elektronik ini, kami bermaksud untuk menyampaikan bahwa permohonan logistik dengan kode permohonan #' . $this->agency->id . ' sudah disetujui untuk realisasi salur logistik dan barang sudah siap untuk diambil/ dikirimkan.';
        $this->texts[] = '';
        $this->texts[] = 'Jika barang sudah diterima oleh pemohon, silahkan untuk melaporkan penggunaan logistik dengan ketentuan berikut:';
        $this->texts[] = '';
        $this->texts[] = '1. Pemohon tanpa adanya mutasi barang dapat langsung menjalankan alur pelaporan sebagai berikut:';
        $this->texts[] = 'a. Pemohon dapat mengisi formulir penerimaan barang dari Pemdaprov Jabar melalui laman http://bit.ly/LaporPenerimaanLogistik. Pengisian form dilakukan sebanyak 1 kali sejak penerimaan barang dilakukan, dengan batas maksimum pelaporan yaitu 2x24 jam setelah barang diterima.';
        $this->texts[] = 'b. Pemohon mengisi formulir penggunaan barang dari setiap pengguna melalui laman http://bit.ly/LaporPenggunaanLogistik secara berkala setiap kali ada penggunaan barang. ';
        $this->texts[] = '';
        $this->texts[] = '2. Pemohon yang melakukan mutasi barang dapat menjalankan alur pelaporan seperti berikut:';
        $this->texts[] = 'a. Dinas Kesehatan Kab/Kota dapat mengisi formulir penerimaan barang dari Pemdaprov Jabar melalui laman http://bit.ly/LaporPenerimaanLogistik. Pengisian form dilakukan sebanyak 1 kali sejak penerimaan barang dilakukan, dengan batas maksimum pelaporan yaitu 2x24 jam setelah barang diterima. Dinas Kesehatan Kab/Kota dapat mendistribusikan barang logistik yang telah diterima dari Pemdaprov Jabar ke setiap fasyankes sesuai dengan rencana alokasi masing-masing. dengan batas waktu distribusi selama 4x24 jam.';
        $this->texts[] = 'b. Fasyankes yang telah menerima barang dari Pemdaprov Jabar melalui Dinas Kesehatan Kab/Kota dapat melaporkan penerimaan barang tersebut melalui laman http://bit.ly/LaporPenerimaanLogistik dengan batas waktu maksimal 2x24 jam sejak barang diterima. Fasyankes diharapkan dapat koordinasi dengan Dinkes Kab/Kota mengenai kode permohonan dan nomor surat pemohon.';
        $this->texts[] = 'c. Fasyankes yang menerima bantuan logistik dipersilakan untuk mengisi formulir penggunaan barang melalui laman http://bit.ly/LaporPenggunaanLogistik secara berkala setiap kali ada penggunaan barang.';
        $this->texts[] = '';
        $this->texts[] = '3. Jika barang yang diterima sudah habis terpakai, maka pelaporan penggunaan logistik dapat dihentikan.';
        $this->texts[] = '';
        $this->texts[] = 'Untuk panduan lebih lengkap, dapat dilihat pada link berikut :';
        $this->texts[] = 'https://bit.ly/PanduanPelaporanLogistik';
        $this->notes[] = 'Silahkan anda dapat menghubungi nomor kontak hotline atau email untuk melakukan pengecekan dan konfirmasi terhadap permohonan tersebut.';
    }

    public function getContent()
    {
        return $this->view('email.logisticemailnotification')
                    ->subject($this->subject)
                    ->with([
                        'applicantName' => $this->agency->applicant->applicant_name,
                        'notes' => $this->notes,
                        'agency' => $this->agency->agency_name,
                        'texts' => $this->texts,
                        'from' => env('MAIL_FROM_NAME'),
                        'hotLine' => env('HOTLINE_PIKOBAR')
                    ]);
    }
}
