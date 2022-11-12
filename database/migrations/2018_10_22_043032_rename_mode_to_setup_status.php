<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameModeToSetupStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->renameColumn('mode', 'setup_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->renameColumn('setup_status', 'mode');
        });
    }
}
