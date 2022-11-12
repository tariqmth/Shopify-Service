<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeClickAndCollectSettingsUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('click_and_collect_settings', function(Blueprint $table) {
            $table->dropUnique(['addon_license_id']);
            $table->unique(['shopify_store_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('click_and_collect_settings', function(Blueprint $table) {
            $table->dropUnique(['shopify_store_id']);
            $table->unique(['addon_license_id']);
        });
    }
}
