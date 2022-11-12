<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStoreNamesToSyncJobHistories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sync_jobs_history', function (Blueprint $table) {
            $table->string('client_name')->nullable()->index();
            $table->string('shopify_store_subdomain')->nullable()->index();
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
            $table->dropColumn('client_name');
            $table->dropColumn('shopify_store_subdomain');
        });
    }
}
