<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShopifyVoucherProductIdKeyToShopifyFulfillments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_fulfillments', function (Blueprint $table) {
            $table->foreign('shopify_voucher_product_id')
                ->references('id')
                ->on('shopify_products')
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
        Schema::table('shopify_fulfillments', function (Blueprint $table) {
            $table->dropForeign('shopify_fulfillments_shopify_voucher_product_id_foreign');
        });
    }
}
