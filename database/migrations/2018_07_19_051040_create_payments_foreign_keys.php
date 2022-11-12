<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rex_payments', function(Blueprint $table) {
			$table->foreign('rex_order_id')
                ->references('id')
                ->on('rex_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique(array('rex_order_id', 'external_id'));
		});
        Schema::table('shopify_transactions', function(Blueprint $table) {
			$table->foreign('shopify_order_id')
                ->references('id')
                ->on('shopify_orders')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('shopify_payment_gateway_id')
                ->references('id')
                ->on('shopify_payment_gateways')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('rex_payment_id')
                ->references('id')
                ->on('rex_payments')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
			$table->unique('rex_payment_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rex_payments', function(Blueprint $table) {
			$table->dropForeign('rex_payments_rex_order_id_foreign');
			$table->dropUnique('rex_payments_rex_order_id_external_id_unique');
		});
        Schema::table('shopify_transactions', function(Blueprint $table) {
			$table->dropForeign('shopify_transactions_shopify_order_id_foreign');
			$table->dropForeign('shopify_transactions_shopify_payment_gateway_id_foreign');
			$table->dropForeign('shopify_transactions_rex_payment_id_foreign');
			$table->dropUnique('shopify_transactions_external_id_unique');
			$table->dropUnique('shopify_transactions_rex_payment_id_unique');
		});
    }
}
