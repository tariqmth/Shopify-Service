<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UniqueKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_products', function(Blueprint $table) {
			$table->unique(array('rex_sales_channel_id', 'external_id'));
		});

        Schema::table('rex_product_groups', function(Blueprint $table) {
			$table->unique(array('rex_sales_channel_id', 'code'));
		});

        Schema::table('shopify_product_variants', function(Blueprint $table) {
			$table->unique('external_id');
		});

        Schema::table('shopify_products', function(Blueprint $table) {
			$table->unique('external_id');
			$table->unique('rex_product_id');
			$table->unique('rex_product_group_id');
		});

        Schema::table('rex_sales_channels', function(Blueprint $table) {
			$table->unique(array('client_id', 'external_id'));
		});

        Schema::table('shopify_stores', function(Blueprint $table) {
			$table->unique('subdomain');
		});

        Schema::table('clients', function(Blueprint $table) {
			$table->unique('external_id');
		});

        Schema::table('api_auth', function(Blueprint $table) {
			$table->unique('api_token');
		});

        Schema::table('rex_attributes', function(Blueprint $table) {
			$table->unique(array('client_id', 'name'));
		});

        Schema::table('rex_attribute_options', function(Blueprint $table) {
			$table->unique(array('rex_attribute_id', 'option_id'));
		});

        Schema::table('shopify_fulfillment_services', function(Blueprint $table) {
			$table->unique('external_id');
		});

        Schema::table('shopify_locations', function(Blueprint $table) {
			$table->unique('external_id');
			$table->unique('shopify_fulfillment_service_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_products', function(Blueprint $table) {
            $table->index('rex_sales_channel_id');
			$table->dropUnique('rex_products_rex_sales_channel_id_external_id_unique');
		});

        Schema::table('rex_product_groups', function(Blueprint $table) {
            $table->index('rex_sales_channel_id');
			$table->dropUnique('rex_product_groups_rex_sales_channel_id_code_unique');
		});

        Schema::table('shopify_product_variants', function(Blueprint $table) {
            $table->index('rex_product_id');
			$table->dropUnique('shopify_product_variants_external_id_unique');
		});

        Schema::table('shopify_products', function(Blueprint $table) {
            $table->index('rex_product_id');
            $table->index('rex_product_group_id');
			$table->dropUnique('shopify_products_external_id_unique');
			$table->dropUnique('shopify_products_rex_product_id_unique');
			$table->dropUnique('shopify_products_rex_product_group_id_unique');
		});

        Schema::table('rex_sales_channels', function(Blueprint $table) {
            $table->index('client_id');
			$table->dropUnique('rex_sales_channels_client_id_external_id_unique');
		});

        Schema::table('shopify_stores', function(Blueprint $table) {
			$table->dropUnique('shopify_stores_subdomain_unique');
		});

        Schema::table('clients', function(Blueprint $table) {
			$table->dropUnique('clients_external_id_unique');
		});

        Schema::table('api_auth', function(Blueprint $table) {
			$table->dropUnique('api_auth_api_token_unique');
		});

        Schema::table('rex_attributes', function(Blueprint $table) {
            $table->index('client_id');
			$table->dropUnique('rex_attributes_client_id_name_unique');
		});

        Schema::table('rex_attribute_options', function(Blueprint $table) {
            $table->index('rex_attribute_id');
			$table->dropUnique('rex_attribute_options_rex_attribute_id_option_id_unique');
		});

        Schema::table('shopify_fulfillment_services', function(Blueprint $table) {
			$table->dropUnique('shopify_fulfillment_services_external_id_unique');
		});

        Schema::table('shopify_locations', function(Blueprint $table) {
            $table->index('shopify_fulfillment_service_id');
			$table->dropUnique('shopify_locations_external_id_unique');
			$table->dropUnique('shopify_locations_shopify_fulfillment_service_id_unique');
		});
    }
}
