<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_fulfillment_services', function(Blueprint $table) {
			$table->foreign('shopify_store_id')
						->references('id')
						->on('shopify_stores')
						->onDelete('cascade')
						->onUpdate('restrict');
		});
        Schema::table('shopify_locations', function(Blueprint $table) {
			$table->foreign('shopify_fulfillment_service_id')
						->references('id')
						->on('shopify_fulfillment_services')
						->onDelete('cascade')
						->onUpdate('restrict');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_fulfillment_services', function(Blueprint $table) {
			$table->dropForeign('shopify_fulfillment_services_shopify_store_id_foreign');
		});
        Schema::table('shopify_locations', function(Blueprint $table) {
			$table->dropForeign('shopify_locations_shopify_fulfillment_service_id_foreign');
		});
    }
}
