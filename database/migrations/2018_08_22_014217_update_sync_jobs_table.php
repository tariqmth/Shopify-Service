<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSyncJobsTable extends Migration
{
    /**`
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_jobs', function(Blueprint $table) {
			$table->integer('client_id')->nullable()->change();
		});

        Schema::dropIfExists('rex_jobs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_jobs', function(Blueprint $table) {
			$table->integer('client_id')->nullable(false)->change();
		});
    }
}
