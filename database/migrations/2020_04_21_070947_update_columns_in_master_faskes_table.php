<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnsInMasterFaskesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_faskes', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->integer('faskes_origin_id')->after('id')->nullable();
            $table->string('nama_atasan')->after('alamat')->nullable();
            $table->string('nomor_registrasi')->after('alamat')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('master_faskes', function (Blueprint $table) {
            $table->dropColumn('id')->change();
            $table->dropColumn('faskes_origin_id');
            $table->dropColumn('nama_atasan');
            $table->dropColumn('nomor_registrasi');
        });
    }
}
