<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactPersonToSohLocation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('soh_locations', function (Blueprint $table) {
            $table->string('pic_name', 70)->nullable();
            $table->string('pic_handphone', 30)->nullable();
            $table->string('map_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('soh_locations', function (Blueprint $table) {
            $table->dropColumn('pic_name');
            $table->dropColumn('pic_handphone');
            $table->dropColumn('map_url');
        });
    }
}
