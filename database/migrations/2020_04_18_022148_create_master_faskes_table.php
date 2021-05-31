<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMasterFaskesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_faskes', function (Blueprint $table) {
            $table->increments('id')->unique();
            $table->integer('id_tipe_faskes');
            $table->string('nama_faskes');
            $table->string('kode_kab_bps')->nullable();
            $table->string('kode_kec_bps')->nullable();
            $table->string('kode_kel_bps')->nullable();
            $table->string('kode_kab_kemendagri')->nullable();
            $table->string('kode_kec_kemendagri')->nullable();
            $table->string('kode_kel_kemendagri')->nullable();
            $table->string('nama_kab')->nullable();
            $table->string('nama_kec')->nullable();
            $table->string('nama_kel')->nullable();
            $table->string('alamat')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('url')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('point_longitude_latitude')->nullable();
            $table->string('lini_rujukan_covid19')->nullable();
            $table->string('kepemilikan_kemkes')->nullable();
            $table->string('kode_rs_kemkes')->nullable();
            $table->string('jenis_rs_kemkes')->nullable();
            $table->string('kelas_rs_kemkes')->nullable();
            $table->string('id_bpjs')->nullable();
            $table->string('kode_ppk_bpjs')->nullable();
            $table->string('jenis_ppk_bpjs')->nullable();
            $table->string('jenis_faskes_bpjs')->nullable();
            $table->string('kode_unit_dinkes')->nullable();
            $table->string('tipe_dinkes')->nullable();
            $table->string('kelas_dinkes')->nullable();
            $table->string('penyelenggara_dinkes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_faskes');
    }
}
