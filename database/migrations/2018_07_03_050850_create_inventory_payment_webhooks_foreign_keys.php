<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInventoryPaymentWebhooksForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_inventory_items', function(Blueprint $table) {
			$table->foreign('shopify_product_variant_id')
                ->references('id')
                ->on('shopify_product_variants')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('shopify_product_variant_id');
		});

        Schema::table('shopify_payment_gateways', function (Blueprint $table) {
            $table->unique('name');
        });

        Schema::table('shopify_payment_gateway_mappings', function (Blueprint $table) {
            $table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign(
                    'shopify_payment_gateway_id',
                    'shopify_payment_gateway_mappings_gateway_id_foreign'
                )
                ->references('id')
                ->on('shopify_payment_gateways')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(
                array('shopify_store_id','shopify_payment_gateway_id'),
                'shopify_payment_gateway_mappings_store_gateway_unique'
            );
        });

        Schema::table('shopify_webhooks', function (Blueprint $table) {
            $table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->unique(array('shopify_store_id','topic'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_inventory_items', function(Blueprint $table) {
			$table->dropForeign('shopify_inventory_items_shopify_product_variant_id_foreign');
			$table->dropUnique('shopify_inventory_items_shopify_product_variant_id_unique');
		});

        Schema::table('shopify_payment_gateways', function(Blueprint $table) {
			$table->dropUnique('shopify_payment_gateways_name_unique');
		});

        Schema::table('shopify_payment_gateway_mappings', function(Blueprint $table) {
			$table->dropForeign('shopify_payment_gateway_mappings_shopify_store_id_foreign');
			$table->dropForeign('shopify_payment_gateway_mappings_gateway_id_foreign');
			$table->dropUnique('shopify_payment_gateway_mappings_store_gateway_unique');
		});

        Schema::table('shopify_webhooks', function(Blueprint $table) {
			$table->dropForeign('shopify_webhooks_shopify_store_id_foreign');
			$table->dropUnique('shopify_webhooks_shopify_store_id_topic_unique');
		});
    }
}
