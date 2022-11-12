<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyFulfillmentItemsForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shopify_fulfillment_items', function (Blueprint $table) {
            $table->foreign('shopify_fulfillment_id')
                ->references('id')
                ->on('shopify_fulfillments')
                ->onDelete('cascade')
                ->onUpdate('restrict');
            $table->foreign('shopify_order_item_id')
                ->references('id')
                ->on('shopify_order_items')
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
        Schema::table('shopify_fulfillment_items', function (Blueprint $table) {
            $table->dropForeign('shopify_fulfillment_items_shopify_fulfillment_id_foreign');
            $table->dropForeign('shopify_fulfillment_items_shopify_order_item_id_foreign');
        });
    }
}
