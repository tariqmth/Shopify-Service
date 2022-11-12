<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrderItemForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_order_products', function(Blueprint $table) {
			$table->foreign('rex_order_id')
                ->references('id')
                ->on('rex_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('rex_product_id')
                ->references('id')
                ->on('rex_products')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_order_id', 'rex_product_id'));
		});
        Schema::table('rex_order_items', function(Blueprint $table) {
			$table->foreign('rex_order_product_id')
                ->references('id')
                ->on('rex_order_products')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_order_product_id', 'external_id'));
		});
        Schema::table('shopify_order_items', function(Blueprint $table) {
			$table->foreign('shopify_order_id')
                ->references('id')
                ->on('shopify_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('rex_order_product_id')
                ->references('id')
                ->on('rex_order_products')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
			$table->unique('rex_order_product_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_order_products', function(Blueprint $table) {
			$table->dropForeign('rex_order_products_rex_order_id_foreign');
			$table->dropForeign('rex_order_products_rex_product_id_foreign');
			$table->dropUnique('rex_order_products_rex_order_id_rex_product_id_unique');
		});
        Schema::table('rex_order_items', function(Blueprint $table) {
			$table->dropForeign('rex_order_items_rex_order_product_id_foreign');
			$table->dropUnique('rex_order_items_rex_order_product_id_external_id_unique');
		});
        Schema::table('shopify_order_items', function(Blueprint $table) {
			$table->dropForeign('shopify_order_items_shopify_order_id_foreign');
			$table->dropForeign('shopify_order_items_rex_order_product_id_foreign');
			$table->dropUnique('shopify_order_items_external_id_unique');
			$table->dropUnique('shopify_order_items_rex_order_product_id_unique');
		});
    }
}
