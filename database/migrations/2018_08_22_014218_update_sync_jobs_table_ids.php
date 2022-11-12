<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSyncJobsTableIds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_jobs', function(Blueprint $table) {
			$table->string('entity_external_id')->nullable()->index();
			$table->string('unique_id', 26)->nullable()->index();
            $table->string('parent_unique_id', 26)->nullable()->index();
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
