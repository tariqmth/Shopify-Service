<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddClickAndCollectSettingsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('click_and_collect_settings', function(Blueprint $table) {
            $table->foreign('addon_license_id')
                ->references('id')
                ->on('addon_licenses')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(['addon_license_id']);
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
            $table->dropForeign('click_and_collect_settings_addon_license_id_foreign');
            $table->dropForeign('click_and_collect_settings_shopify_store_id_foreign');
            $table->dropUnique(['addon_license_id']);
        });
    }
}
