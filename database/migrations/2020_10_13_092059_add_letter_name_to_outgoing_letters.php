<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLetterNameToOutgoingLetters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->string('letter_name')->nullable();
            $table->string('letter_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn('letter_name');
            $table->string('letter_number')->change();
        });
    }
}
