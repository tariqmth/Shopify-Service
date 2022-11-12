<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopifyFulfillmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shopify_fulfillments', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('external_id')->unsigned()->nullable();
            $table->integer('shopify_order_id')->unsigned()->index();
            $table->integer('rex_fulfillment_batch_id')->unsigned()->nullable()->index();
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
        Schema::dropIfExists('shopify_fulfillments');
    }
}
