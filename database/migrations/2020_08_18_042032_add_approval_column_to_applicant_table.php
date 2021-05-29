<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddApprovalColumnToApplicantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dateTime('verified_at')->nullable()->after('verified_by');
            $table->integer('approved_by')->nullable()->after('verified_at');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn('verified_at');
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_at');
        });
    }
}
