<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_sales_channels', function(Blueprint $table) {
			$table->foreign('client_id')
						->references('id')
						->on('clients')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('shopify_stores', function(Blueprint $table) {
			$table->foreign('client_id')
						->references('id')
						->on('clients')
						->onDelete('cascade')
						->onUpdate('restrict');
			$table->foreign('rex_sales_channel_id')
						->references('id')
						->on('rex_sales_channels')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('rex_products', function(Blueprint $table) {
			$table->foreign('rex_sales_channel_id')
						->references('id')
						->on('rex_sales_channels')
						->onDelete('cascade')
						->onUpdate('restrict');
			$table->foreign('rex_product_group_id')
						->references('id')
						->on('rex_product_groups')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('rex_product_groups', function(Blueprint $table) {
			$table->foreign('rex_sales_channel_id')
						->references('id')
						->on('rex_sales_channels')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('shopify_products', function(Blueprint $table) {
			$table->foreign('shopify_store_id')
						->references('id')
						->on('shopify_stores')
						->onDelete('cascade')
						->onUpdate('restrict');
			$table->foreign('rex_product_id')
						->references('id')
						->on('rex_products')
						->onDelete('cascade')
						->onUpdate('restrict');
			$table->foreign('rex_product_group_id')
						->references('id')
						->on('rex_product_groups')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('shopify_product_variants', function(Blueprint $table) {
			$table->foreign('rex_product_id')
						->references('id')
						->on('rex_products')
						->onDelete('cascade')
						->onUpdate('restrict');
			$table->foreign('shopify_product_id')
						->references('id')
						->on('shopify_products')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('api_auth', function(Blueprint $table) {
			$table->foreign('source_id')
						->references('id')
						->on('sources')
						->onDelete('cascade')
						->onUpdate('restrict');
		});

        Schema::table('sync_jobs', function(Blueprint $table) {
			$table->foreign('shopify_store_id')
						->references('id')
						->on('shopify_stores')
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
        Schema::table('rex_sales_channels', function(Blueprint $table) {
			$table->dropForeign('rex_sales_channels_client_id_foreign');
		});

        Schema::table('shopify_stores', function(Blueprint $table) {
			$table->dropForeign('shopify_stores_client_id_foreign');
			$table->dropForeign('shopify_stores_rex_sales_channel_id_foreign');
		});

        Schema::table('rex_products', function(Blueprint $table) {
			$table->dropForeign('rex_products_rex_sales_channel_id_foreign');
			$table->dropForeign('rex_products_rex_product_group_id_foreign');
		});

        Schema::table('rex_product_groups', function(Blueprint $table) {
			$table->dropForeign('rex_product_groups_rex_sales_channel_id_foreign');
		});

        Schema::table('shopify_products', function(Blueprint $table) {
			$table->dropForeign('shopify_products_shopify_store_id_foreign');
			$table->dropForeign('shopify_products_rex_product_id_foreign');
			$table->dropForeign('shopify_products_rex_product_group_id_foreign');
		});

        Schema::table('shopify_product_variants', function(Blueprint $table) {
			$table->dropForeign('shopify_product_variants_shopify_product_id_foreign');
			$table->dropForeign('shopify_product_variants_rex_product_id_foreign');
		});

        Schema::table('api_auth', function(Blueprint $table) {
			$table->dropForeign('api_auth_source_id_foreign');
		});

        Schema::table('sync_jobs', function(Blueprint $table) {
			$table->dropForeign('sync_jobs_shopify_store_id_foreign');
		});
    }
}
