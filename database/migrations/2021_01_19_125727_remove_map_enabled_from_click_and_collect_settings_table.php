<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveMapEnabledFromClickAndCollectSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('click_and_collect_settings', function (Blueprint $table) {
             $table->dropColumn('map_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('click_and_collect_settings', function (Blueprint $table) {
            $table->boolean('map_enabled')->default(false);
        });
    }
}
