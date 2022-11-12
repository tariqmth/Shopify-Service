<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSyncJobsStatusUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_jobs_status_updates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sync_jobs_history_unique_id')->index();
            $table->integer('sync_jobs_status_code');
            $table->integer('time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_jobs_status_updates');
    }
}
