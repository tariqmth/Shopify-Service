<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClickAndCollectSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('click_and_collect_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('addon_license_id')->unsigned()->index();
            $table->integer('shopify_store_id')->unsigned()->index();
            $table->boolean('map_enabled')->default(false);
            $table->string('google_api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('click_and_collect_settings');
    }
}
