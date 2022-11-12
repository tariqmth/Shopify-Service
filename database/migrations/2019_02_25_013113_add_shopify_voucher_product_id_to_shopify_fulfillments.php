<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShopifyVoucherProductIdToShopifyFulfillments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_fulfillments', function (Blueprint $table) {
            $table->bigInteger('shopify_voucher_product_id')->nullable()->unsigned();
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
            $table->dropColumn('shopify_voucher_product_id');
        });
    }
}
