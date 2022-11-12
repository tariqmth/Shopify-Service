<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyFulfillmentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_fulfillment_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('shopify_fulfillment_id')->unsigned()->index();
            $table->integer('shopify_order_item_id')->unsigned()->index();
            $table->integer('quantity')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shopify_fulfillment_items');
    }
}
