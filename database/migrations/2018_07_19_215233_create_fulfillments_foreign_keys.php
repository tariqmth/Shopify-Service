<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFulfillmentsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_fulfillments', function(Blueprint $table) {
			$table->foreign('rex_fulfillment_batch_id')
                ->references('id')
                ->on('rex_fulfillment_batches')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_fulfillment_batch_id', 'external_id'));
		});
        Schema::table('rex_fulfillment_batches', function(Blueprint $table) {
			$table->foreign('rex_order_id')
                ->references('id')
                ->on('rex_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_order_id', 'external_ids_hash'));
		});
        Schema::table('shopify_fulfillments', function(Blueprint $table) {
			$table->foreign('shopify_order_id')
                ->references('id')
                ->on('shopify_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('rex_fulfillment_batch_id')
                ->references('id')
                ->on('rex_fulfillment_batches')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
			$table->unique('rex_fulfillment_batch_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_fulfillments', function(Blueprint $table) {
			$table->dropForeign('rex_fulfillments_rex_fulfillment_batch_id_foreign');
			$table->dropUnique('rex_fulfillments_rex_fulfillment_batch_id_external_id_unique');
		});
        Schema::table('shopify_fulfillments', function(Blueprint $table) {
			$table->dropForeign('shopify_fulfillments_shopify_order_id_foreign');
			$table->dropForeign('shopify_fulfillments_rex_fulfillment_batch_id_foreign');
			$table->dropUnique('shopify_fulfillments_external_id_unique');
			$table->dropUnique('shopify_fulfillments_rex_fulfillment_batch_id_unique');
		});
    }
}
