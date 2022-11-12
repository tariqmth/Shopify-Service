<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSyncJobsHistoryTable extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_jobs_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('unique_id', 26)->nullable()->index();
            $table->string('parent_unique_id', 26)->nullable()->index();
            $table->string('source');
            $table->string('queue');
            $table->integer('entity_id')->unsigned()->nullable();
            $table->string('entity_external_id')->nullable();
            $table->string('direction');
            $table->integer('time');
            $table->integer('client_id')->unsigned()->nullable()->index();
            $table->integer('shopify_store_id')->nullable()->unsigned()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_jobs_history');
    }
}
