<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFurtherIndexesToSyncJobsHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_jobs_history', function (Blueprint $table) {
            $table->index('source');
            $table->index('queue');
            $table->index('entity_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_jobs_history', function (Blueprint $table) {
            $table->dropIndex('source');
            $table->dropIndex('queue');
            $table->dropIndex('entity_id');
        });
    }
}
