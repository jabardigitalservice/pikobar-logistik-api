<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSyncApiScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_api_schedules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('api');
            $table->string('type');
            $table->integer('hour');
            $table->integer('minute');
            $table->integer('second');
            $table->timestamps();
        });

        $dataSyncAPISchedules = [
            [
                'api' => 'WMS_JABAR_BASE_URL',
                'type' => 'interval',
                'hour' => 0,
                'minute' => 5,
                'second' => 0,
            ]
        ];
        DB::table('sync_api_schedules')->insert($dataSyncAPISchedules);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_api_schedule');
    }
}
