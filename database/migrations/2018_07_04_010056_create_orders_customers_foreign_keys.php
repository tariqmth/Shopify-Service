<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersCustomersForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_orders', function(Blueprint $table) {
			$table->foreign('rex_order_id')
                ->references('id')
                ->on('rex_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('rex_order_id');
			$table->foreign('shopify_customer_id')
                ->references('id')
                ->on('shopify_customers')
                ->onDelete('set null')
                ->onUpdate('restrict');
			$table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
		});

        Schema::table('shopify_customers', function(Blueprint $table) {
			$table->foreign('rex_customer_id')
                ->references('id')
                ->on('rex_customers')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('rex_customer_id');
			$table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
		});

        Schema::table('rex_orders', function(Blueprint $table) {
			$table->foreign('rex_customer_id')
                ->references('id')
                ->on('rex_customers')
                ->onDelete('set null')
                ->onUpdate('restrict');
			$table->foreign('rex_sales_channel_id')
                ->references('id')
                ->on('rex_sales_channels')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_sales_channel_id', 'external_id'));
		});

        Schema::table('rex_customers', function(Blueprint $table) {
			$table->foreign('rex_sales_channel_id')
                ->references('id')
                ->on('rex_sales_channels')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_sales_channel_id', 'external_id'));
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_orders', function(Blueprint $table) {
			$table->dropForeign('shopify_orders_rex_order_id_foreign');
			$table->dropUnique('shopify_orders_rex_order_id_unique');
			$table->dropForeign('shopify_orders_shopify_customer_id_foreign');
			$table->dropForeign('shopify_orders_shopify_store_id_foreign');
			$table->dropUnique('shopify_orders_external_id_unique');
		});

        Schema::table('shopify_customers', function(Blueprint $table) {
			$table->dropForeign('shopify_customers_rex_customer_id_foreign');
			$table->dropForeign('shopify_customers_shopify_store_id_foreign');
			$table->dropUnique('shopify_customers_external_id_unique');
		});

        Schema::table('rex_orders', function(Blueprint $table) {
			$table->dropForeign('rex_orders_rex_customer_id_foreign');
			$table->dropForeign('rex_orders_rex_sales_channel_id_foreign');
			$table->dropUnique('rex_orders_rex_sales_channel_id_external_id_unique');
		});

        Schema::table('rex_customers', function(Blueprint $table) {
			$table->dropForeign('rex_customers_rex_sales_channel_id_foreign');
			$table->dropUnique('rex_customers_rex_sales_channel_id_external_id_unique');
		});
    }
}
