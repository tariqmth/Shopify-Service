<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SyncJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source')->index();
            $table->string('queue')->index();
            $table->integer('entity_id')->unsigned()->nullable()->index();
            $table->string('direction');
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable()->index();
            $table->unsignedInteger('available_at')->index();
            $table->unsignedInteger('created_at');
            $table->integer('client_id')->unsigned()->index();
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
        Schema::dropIfExists('sync_jobs');
    }
}
