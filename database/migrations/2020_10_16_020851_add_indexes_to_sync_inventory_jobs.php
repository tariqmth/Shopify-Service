<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToSyncInventoryJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {    
        Schema::table('sync_inventory_jobs', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('sync_inventory_jobs');

            if(array_key_exists("sync_inventory_jobs_reserved_at_index", $indexesFound))
            {
                $table->dropUnique("sync_inventory_jobs_reserved_at_index");
            }
            if(array_key_exists("sync_inventory_jobs_available_at_index", $indexesFound))
            {
                $table->dropUnique("sync_inventory_jobs_available_at_index");
            }

        });
        Schema::table('sync_inventory_jobs', function (Blueprint $table) {
            $table->index('reserved_at');
            $table->index('available_at');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sync_inventory_jobs', function (Blueprint $table) {
            $table->dropIndex(['reserved_at','available_at']);
        });
    }
}
