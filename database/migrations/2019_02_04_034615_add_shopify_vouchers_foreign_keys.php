<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShopifyVouchersForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_vouchers', function(Blueprint $table) {
			$table->foreign('shopify_store_id')
                ->references('id')
                ->on('shopify_stores')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->foreign('rex_voucher_id')
                ->references('id')
                ->on('rex_vouchers')
                ->onDelete('cascade')
                ->onUpdate('restrict');
			$table->unique('external_id');
			$table->unique('rex_voucher_id');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shopify_vouchers', function(Blueprint $table) {
			$table->dropForeign('shopify_vouchers_shopify_store_id_foreign');
			$table->dropForeign('shopify_vouchers_rex_voucher_id_foreign');
			$table->dropUnique('shopify_vouchers_external_id_unique');
			$table->dropUnique('shopify_vouchers_rex_voucher_id_unique');
		});
    }
}
